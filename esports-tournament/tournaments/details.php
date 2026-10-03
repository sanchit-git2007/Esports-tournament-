<?php
// =======================================================
// File: tournaments/details.php
// Purpose: Full Tournament Overview, Scoring Rules, Rosters & Registration
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Tournament Details - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";

$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tournamentId <= 0) {
    header("Location: index.php");
    exit;
}

// 1. Fetch Tournament Info
$stmt = $pdo->prepare("
    SELECT t.*, u.name AS organizer_name 
    FROM tournaments t 
    JOIN users u ON t.created_by = u.id 
    WHERE t.id = ?
");
$stmt->execute([$tournamentId]);
$tourney = $stmt->fetch();

if (!$tourney) {
    die("<div class='container py-5 text-center text-white'><h2>Tournament Not Found</h2><a href='index.php' class='btn btn-neon-primary mt-3'>Back to Tournaments</a></div>");
}

// 2. Fetch Configurable Scoring Rules
$scoringStmt = $pdo->prepare("SELECT * FROM tournament_scoring_rules WHERE tournament_id = ? ORDER BY placement ASC");
$scoringStmt->execute([$tournamentId]);
$scoringRules = $scoringStmt->fetchAll();

// 3. Fetch Approved Teams
$teamsStmt = $pdo->prepare("
    SELECT tr.*, tm.team_name, tm.team_tag, tm.logo, u.name AS captain_name, tm.id AS team_id
    FROM tournament_registrations tr
    JOIN teams tm ON tr.team_id = tm.id
    JOIN users u ON tm.captain_id = u.id
    WHERE tr.tournament_id = ? AND tr.status = 'approved'
    ORDER BY tr.approved_at ASC
");
$teamsStmt->execute([$tournamentId]);
$approvedTeams = $teamsStmt->fetchAll();

// 4. Fetch Scheduled Matches
$matchesStmt = $pdo->prepare("SELECT * FROM matches WHERE tournament_id = ? ORDER BY match_number ASC");
$matchesStmt->execute([$tournamentId]);
$matches = $matchesStmt->fetchAll();

// 5. Check Logged-in Player's Team & Registration Status
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;
$myTeam = null;
$myRegistration = null;
$isCaptain = false;

if ($userId) {
    // Check if user is Captain of any team
    $teamCheck = $pdo->prepare("SELECT * FROM teams WHERE captain_id = ?");
    $teamCheck->execute([$userId]);
    $myTeam = $teamCheck->fetch();

    if ($myTeam) {
        $isCaptain = true;
        // Check if this team is registered for this tournament
        $regCheck = $pdo->prepare("SELECT * FROM tournament_registrations WHERE tournament_id = ? AND team_id = ?");
        $regCheck->execute([$tournamentId, $myTeam['id']]);
        $myRegistration = $regCheck->fetch();
    }
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-cyan text-decoration-none">Tournaments</a></li>
            <li class="breadcrumb-item text-white active"><?php echo htmlspecialchars($tourney['tournament_name']); ?></li>
        </ol>
    </nav>

    <!-- Success & Error Alerts from Register Script -->
    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success bg-success bg-opacity-25 border-success text-white py-3 mb-4 alert-auto-dismiss">
            <i class="fa-solid fa-circle-check fs-5 me-2"></i> 
            <strong>Squad Registration Submitted!</strong> Your application is now pending administrator review. You can track approval status on your dashboard.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-white py-3 mb-4">
            <i class="fa-solid fa-circle-exclamation fs-5 me-2"></i> 
            <?php 
            $err = $_GET['error'];
            if ($err === 'invalid_code') echo "Incorrect Access Passcode! Please verify the private tournament code.";
            elseif ($err === 'full') echo "Tournament is already full! Maximum team limit reached.";
            elseif ($err === 'closed') echo "Registration is currently closed for this tournament.";
            elseif ($err === 'already_registered') echo "Your squad is already registered for this championship.";
            elseif ($err === 'not_captain') echo "Only the designated Team Captain can register a squad.";
            else echo "Registration failed. Please try again.";
            ?>
        </div>
    <?php endif; ?>

    <!-- Main Tournament Hero Card -->
    <div class="esports-card p-4 p-lg-5 mb-5 border-cyan">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <?php
                    $badgeClass = 'badge-upcoming';
                    if ($tourney['status'] === 'registration_open') $badgeClass = 'badge-open';
                    elseif ($tourney['status'] === 'ongoing') $badgeClass = 'badge-live';
                    elseif ($tourney['status'] === 'completed') $badgeClass = 'badge-completed';
                    ?>
                    <span class="badge-custom <?php echo $badgeClass; ?>">
                        <?php echo str_replace('_', ' ', $tourney['status']); ?>
                    </span>

                    <?php if ($tourney['tournament_type'] === 'private'): ?>
                        <span class="badge bg-dark text-warning border border-warning border-opacity-50">
                            <i class="fa-solid fa-lock me-1"></i> Private Tournament
                        </span>
                    <?php else: ?>
                        <span class="badge bg-dark text-cyan border border-info border-opacity-50">
                            <i class="fa-solid fa-globe me-1"></i> Public Tournament
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="hero-title text-white mb-3"><?php echo htmlspecialchars($tourney['tournament_name']); ?></h1>
                <p class="text-light opacity-75 lead mb-4">
                    <?php echo nl2br(htmlspecialchars($tourney['description'])); ?>
                </p>

                <!-- Key Meta Chips -->
                <div class="d-flex flex-wrap gap-3 text-light opacity-75 small">
                    <div><i class="fa-solid fa-gamepad text-cyan me-1"></i><strong>Game:</strong> <?php echo htmlspecialchars($tourney['game']); ?></div>
                    <div><i class="fa-solid fa-users text-cyan me-1"></i><strong>Slots:</strong> <?php echo count($approvedTeams); ?> / <?php echo $tourney['max_teams']; ?> Teams</div>
                    <div><i class="fa-solid fa-calendar-days text-cyan me-1"></i><strong>Date:</strong> <?php echo date('M d, Y', strtotime($tourney['tournament_date'])); ?></div>
                    <div><i class="fa-solid fa-user-shield text-cyan me-1"></i><strong>Organizer:</strong> <?php echo htmlspecialchars($tourney['organizer_name']); ?></div>
                </div>
            </div>

            <!-- Registration Action Column -->
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <?php if ($tourney['status'] === 'registration_open'): ?>
                    <?php if (!$userId): ?>
                        <!-- Case 1: Visitor not logged in -->
                        <div class="bg-dark p-4 rounded border border-secondary border-opacity-25 text-center">
                            <h5 class="text-white mb-2">Want to Compete?</h5>
                            <p class="text-light opacity-75 small mb-3">Sign in or create a player account to register your squad.</p>
                            <a href="../login.php" class="btn btn-neon-primary w-100">Log In to Register</a>
                        </div>
                    <?php elseif ($userRole === 'admin'): ?>
                        <!-- Case 2: Logged in as Admin -->
                        <div class="bg-dark p-4 rounded border border-danger border-opacity-25 text-center">
                            <span class="badge bg-danger mb-2">Administrator Mode</span>
                            <h6 class="text-white mb-2">Tournament Management</h6>
                            <p class="text-light opacity-75 small mb-3">You are logged in as admin. Manage registrations and matches from the control panel.</p>
                            <a href="../admin/tournaments.php" class="btn btn-flame w-100 btn-sm">Manage in Admin Panel</a>
                        </div>
                    <?php elseif ($isCaptain): ?>
                        <!-- Case 3: Logged in Player who IS a Captain -->
                        <?php if ($myRegistration): ?>
                            <div class="bg-dark p-4 rounded border border-secondary border-opacity-25 text-center">
                                <span class="text-muted small text-uppercase">Your Squad Status</span>
                                <h5 class="text-white mt-1 mb-2"><?php echo htmlspecialchars($myTeam['team_name']); ?></h5>
                                <?php if ($myRegistration['status'] === 'approved'): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success p-2 w-100 d-block">
                                        <i class="fa-solid fa-check-circle me-1"></i> Approved & Confirmed
                                    </span>
                                <?php elseif ($myRegistration['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger p-2 w-100 d-block">
                                        <i class="fa-solid fa-times-circle me-1"></i> Registration Rejected
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning p-2 w-100 d-block">
                                        <i class="fa-solid fa-clock me-1"></i> Pending Admin Approval
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Registration Action Form -->
                            <div class="bg-dark p-4 rounded border border-cyan border-opacity-25 text-center">
                                <h5 class="text-white mb-1">Squad: <span class="text-cyan"><?php echo htmlspecialchars($myTeam['team_name']); ?></span></h5>
                                <p class="text-light opacity-75 small mb-3">Slots are available. Register your squad now!</p>
                                
                                <?php if ($tourney['tournament_type'] === 'private'): ?>
                                    <button type="button" class="btn btn-flame w-100" data-bs-toggle="modal" data-bs-target="#privatePasscodeModal">
                                        <i class="fa-solid fa-key me-1"></i> Enter Passcode & Join
                                    </button>
                                <?php else: ?>
                                    <form action="register.php" method="POST">
                                        <input type="hidden" name="tournament_id" value="<?php echo $tourney['id']; ?>">
                                        <input type="hidden" name="team_id" value="<?php echo $myTeam['id']; ?>">
                                        <button type="submit" class="btn btn-neon-primary w-100">
                                            <i class="fa-solid fa-shield-halved me-1"></i> Register Squad Now
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Case 4: Logged in Player who does NOT have a squad yet -->
                        <div class="bg-dark p-4 rounded border border-info border-opacity-25 text-center">
                            <i class="fa-solid fa-people-group text-cyan fs-3 mb-2 d-block"></i>
                            <h5 class="text-white mb-1">Create Squad First</h5>
                            <p class="text-light opacity-75 small mb-3">To join BGMI tournaments, you must create a squad and become Team Captain.</p>
                            <a href="../player/create_team.php" class="btn btn-neon-primary w-100">
                                <i class="fa-solid fa-plus-circle me-1"></i> Create Squad & Compete
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="bg-dark p-4 rounded border border-secondary border-opacity-25 text-center">
                        <span class="badge bg-secondary p-2 w-100 d-block">
                            Registration <?php echo strtoupper(str_replace('_', ' ', $tourney['status'])); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 3-Column Content Tabs / Sections -->
    <div class="row g-4">
        <!-- Column 1: Configurable BGMI Scoring Rules -->
        <div class="col-lg-4">
            <div class="esports-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white mb-0"><i class="fa-solid fa-calculator text-cyan me-2"></i>Scoring System</h5>
                    <span class="badge bg-dark border border-secondary text-cyan">BGMI Official</span>
                </div>
                
                <div class="bg-dark p-3 rounded mb-3 border border-secondary border-opacity-25 text-center">
                    <span class="text-light opacity-75 small text-uppercase">Kill Point Multiplier</span>
                    <h4 class="text-flame mb-0 mt-1"><?php echo $tourney['kill_points_rate']; ?> Point Per Kill</h4>
                </div>

                <h6 class="text-light opacity-75 small text-uppercase fw-bold mb-2">Placement Point Breakdown</h6>
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-esports table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Placement</th>
                                <th class="text-end">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($scoringRules)): ?>
                                <tr><td colspan="2" class="text-center text-muted">Standard BGMI scoring active</td></tr>
                            <?php else: ?>
                                <?php foreach ($scoringRules as $rule): ?>
                                    <tr>
                                        <td>
                                            <?php if ($rule['placement'] == 1): ?>
                                                <strong class="text-gold"><i class="fa-solid fa-trophy text-gold me-1"></i>1st (WWCD)</strong>
                                            <?php elseif ($rule['placement'] == 2): ?>
                                                <strong class="text-white">2nd Place</strong>
                                            <?php elseif ($rule['placement'] == 3): ?>
                                                <strong class="text-white">3rd Place</strong>
                                            <?php else: ?>
                                                <span class="text-light opacity-75"><?php echo $rule['placement']; ?>th Place</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold text-cyan">+<?php echo $rule['points']; ?> Pts</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Column 2: Approved Participating Squads -->
        <div class="col-lg-4">
            <div class="esports-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white mb-0"><i class="fa-solid fa-users text-cyan me-2"></i>Confirmed Squads</h5>
                    <span class="badge bg-dark border border-secondary text-cyan"><?php echo count($approvedTeams); ?> / <?php echo $tourney['max_teams']; ?></span>
                </div>

                <?php if (empty($approvedTeams)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-people-group text-muted fs-2 mb-2 d-block opacity-50"></i>
                        <p class="small text-light opacity-75 mb-0">No teams confirmed yet. Be the first squad to register!</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush bg-transparent">
                        <?php $rank = 1; foreach ($approvedTeams as $team): ?>
                            <div class="list-group-item bg-dark border-secondary border-opacity-25 rounded mb-2 text-white p-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted me-2 small fw-bold">#<?php echo $rank++; ?></span>
                                        <strong class="text-white"><?php echo htmlspecialchars($team['team_name']); ?></strong>
                                        <span class="badge bg-dark border border-secondary text-cyan small ms-1">[<?php echo htmlspecialchars($team['team_tag']); ?>]</span>
                                    </div>
                                    <small class="text-light opacity-75">Capt: <?php echo htmlspecialchars($team['captain_name']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Column 3: Match Schedule & Rules -->
        <div class="col-lg-4">
            <div class="esports-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white mb-0"><i class="fa-solid fa-crosshairs text-flame me-2"></i>Match Schedule</h5>
                    <span class="badge bg-dark border border-secondary text-flame"><?php echo count($matches); ?> Matches</span>
                </div>

                <?php if (empty($matches)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-calendar-xmark text-muted fs-2 mb-2 d-block opacity-50"></i>
                        <p class="small text-light opacity-75 mb-0">Matches will be scheduled soon by the organizer.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush bg-transparent mb-3">
                        <?php foreach ($matches as $m): ?>
                            <div class="list-group-item bg-dark border-secondary border-opacity-25 rounded mb-2 text-white p-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-white"><?php echo htmlspecialchars($m['match_name']); ?></strong>
                                        <div class="small text-cyan"><?php echo htmlspecialchars($m['map']); ?></div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-light opacity-75 d-block"><i class="fa-solid fa-clock me-1 text-cyan"></i><?php echo date('h:i A', strtotime($m['match_time'])); ?></small>
                                        <span class="badge bg-dark border border-secondary text-uppercase small" style="font-size: 0.65rem;">
                                            <?php echo $m['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($tourney['rules'])): ?>
                    <div class="pt-2 border-top border-secondary border-opacity-25">
                        <h6 class="text-white brand-font mb-1"><i class="fa-solid fa-scroll text-cyan me-1"></i> Tournament Rules</h6>
                        <p class="text-light opacity-75 small mb-0"><?php echo nl2br(htmlspecialchars($tourney['rules'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Private Tournament Access Passcode Modal -->
<?php if ($isCaptain && $tourney['tournament_type'] === 'private'): ?>
<div class="modal fade" id="privatePasscodeModal" tabindex="-1" aria-labelledby="passcodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border border-warning border-opacity-50">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title brand-font text-warning" id="passcodeModalLabel">
                    <i class="fa-solid fa-lock me-2"></i> Private Tournament Verification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="register.php" method="POST">
                <input type="hidden" name="tournament_id" value="<?php echo $tourney['id']; ?>">
                <input type="hidden" name="team_id" value="<?php echo $myTeam['id']; ?>">
                <div class="modal-body">
                    <p class="text-light opacity-75 small mb-3">
                        This tournament is private. Please enter the official access code provided by your college coordinator or tournament host.
                    </p>
                    <div class="mb-3">
                        <label class="form-label-custom">Access Passcode <span class="text-danger">*</span></label>
                        <input type="text" name="access_code" class="form-control form-control-custom text-center fw-bold" placeholder="e.g. BGMI2026" required autofocus>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-flame btn-sm">Verify Passcode & Register</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once "../includes/footer.php"; ?>
