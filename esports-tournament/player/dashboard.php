<?php
$pageTitle = "Player Dashboard - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/auth.php";

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];

// 1. Fetch Player's Team (if Captain or Member)
$teamStmt = $pdo->prepare("
    SELECT t.*, u.name AS captain_name 
    FROM teams t 
    JOIN users u ON t.captain_id = u.id 
    WHERE t.captain_id = ? 
    LIMIT 1
");
$teamStmt->execute([$userId]);
$myTeam = $teamStmt->fetch();

// If not captain, check if member of a team
if (!$myTeam) {
    $memberStmt = $pdo->prepare("
        SELECT t.*, u.name AS captain_name 
        FROM team_members tm 
        JOIN teams t ON tm.team_id = t.id 
        JOIN users u ON t.captain_id = u.id 
        WHERE tm.user_id = ? 
        LIMIT 1
    ");
    $memberStmt->execute([$userId]);
    $myTeam = $memberStmt->fetch();
}

// 2. Fetch Team Member Count & Details if team exists
$teamMembers = [];
if ($myTeam) {
    $memStmt = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY id ASC");
    $memStmt->execute([$myTeam['id']]);
    $teamMembers = $memStmt->fetchAll();
}

// 3. Fetch Registered Tournaments for this team
$myTournaments = [];
if ($myTeam) {
    $tourneyStmt = $pdo->prepare("
        SELECT tr.status AS reg_status, tr.registered_at, t.* 
        FROM tournament_registrations tr 
        JOIN tournaments t ON tr.tournament_id = t.id 
        WHERE tr.team_id = ? 
        ORDER BY t.tournament_date DESC
    ");
    $tourneyStmt->execute([$myTeam['id']]);
    $myTournaments = $tourneyStmt->fetchAll();
}

// 4. Fetch Upcoming Matches with Room details (only for approved tournaments)
$myMatches = [];
if ($myTeam) {
    $matchStmt = $pdo->prepare("
        SELECT m.*, t.tournament_name 
        FROM matches m 
        JOIN tournaments t ON m.tournament_id = t.id 
        JOIN tournament_registrations tr ON tr.tournament_id = t.id 
        WHERE tr.team_id = ? AND tr.status = 'approved' AND m.status != 'cancelled'
        ORDER BY m.match_date ASC, m.match_time ASC
    ");
    $matchStmt->execute([$myTeam['id']]);
    $myMatches = $matchStmt->fetchAll();
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Player Welcome Banner -->
    <div class="esports-card mb-4 border-cyan">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-dark text-cyan border border-info border-opacity-25 mb-2">
                    <i class="fa-solid fa-gamepad me-1"></i> Player Portal
                </span>
                <h2 class="text-white mb-1">Welcome back, <span class="text-cyan"><?php echo htmlspecialchars($userName); ?></span>!</h2>
                <p class="text-muted mb-0 small">
                    Manage your squad, check tournament approval status, view room credentials, and download match certificates.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <?php if ($myTeam): ?>
                    <a href="team.php" class="btn btn-neon-outline btn-sm">
                        <i class="fa-solid fa-users me-1"></i> Manage Squad (<?php echo htmlspecialchars($myTeam['team_tag']); ?>)
                    </a>
                <?php else: ?>
                    <a href="create_team.php" class="btn btn-neon-primary btn-sm">
                        <i class="fa-solid fa-plus-circle me-1"></i> Create Squad
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="esports-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase">My Squad</span>
                        <h4 class="text-white mb-0 mt-1">
                            <?php echo $myTeam ? htmlspecialchars($myTeam['team_name']) : '<span class="text-muted">No Team</span>'; ?>
                        </h4>
                    </div>
                    <div class="feature-icon-box mb-0">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-secondary border-opacity-25 small">
                    <?php if ($myTeam): ?>
                        <span class="text-cyan"><i class="fa-solid fa-users me-1"></i> <?php echo count($teamMembers); ?> Roster Members</span>
                    <?php else: ?>
                        <a href="create_team.php" class="text-cyan text-decoration-none">+ Create Squad to Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="esports-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase">Tournaments Joined</span>
                        <h4 class="text-cyan mb-0 mt-1"><?php echo count($myTournaments); ?> Events</h4>
                    </div>
                    <div class="feature-icon-box mb-0 text-cyan">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-secondary border-opacity-25 small">
                    <a href="../tournaments/index.php" class="text-muted text-decoration-none hover-cyan">Browse More Tournaments &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="esports-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase">Upcoming Matches</span>
                        <h4 class="text-flame mb-0 mt-1"><?php echo count($myMatches); ?> Scheduled</h4>
                    </div>
                    <div class="feature-icon-box mb-0 text-flame">
                        <i class="fa-solid fa-crosshairs"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-secondary border-opacity-25 small">
                    <a href="matches.php" class="text-muted text-decoration-none hover-cyan">View Room ID & Passwords &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Matches & Tournaments Row -->
    <div class="row g-4">
        <!-- Upcoming Matches Card with Room Credentials -->
        <div class="col-lg-6">
            <div class="esports-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white mb-0"><i class="fa-solid fa-door-open text-cyan me-2"></i>Match Schedule & Custom Rooms</h5>
                    <span class="badge bg-dark border border-secondary text-muted">BGMI Custom</span>
                </div>

                <?php if (empty($myMatches)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-calendar-xmark fs-3 mb-2 d-block opacity-50"></i>
                        <p class="small mb-0">No upcoming matches scheduled for your team yet.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush bg-transparent">
                        <?php foreach ($myMatches as $match): ?>
                            <div class="list-group-item bg-dark border-secondary border-opacity-25 rounded mb-2 text-white p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-0 text-cyan"><?php echo htmlspecialchars($match['match_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($match['tournament_name']); ?> (<?php echo htmlspecialchars($match['map']); ?>)</small>
                                    </div>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">
                                        <?php echo strtoupper($match['status']); ?>
                                    </span>
                                </div>
                                <div class="bg-surface p-2 rounded small d-flex justify-content-between align-items-center mt-2 border border-secondary border-opacity-25">
                                    <div>
                                        <span class="text-muted">Room ID:</span> 
                                        <strong class="text-warning"><?php echo !empty($match['room_id']) ? htmlspecialchars($match['room_id']) : 'Pending Release'; ?></strong>
                                    </div>
                                    <div>
                                        <span class="text-muted">Password:</span> 
                                        <strong class="text-warning"><?php echo !empty($match['room_password']) ? htmlspecialchars($match['room_password']) : 'Pending'; ?></strong>
                                    </div>
                                    <div>
                                        <span class="text-muted"><i class="fa-solid fa-clock text-cyan me-1"></i><?php echo date('h:i A', strtotime($match['match_time'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Tournaments Registration Status -->
        <div class="col-lg-6">
            <div class="esports-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white mb-0"><i class="fa-solid fa-list-check text-cyan me-2"></i>My Tournament Registrations</h5>
                    <a href="../tournaments/index.php" class="btn btn-sm btn-outline-info">Join More</a>
                </div>

                <?php if (empty($myTournaments)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-trophy fs-3 mb-2 d-block opacity-50"></i>
                        <p class="small mb-0">Your team has not registered for any tournaments yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-esports table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tournament</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myTournaments as $t): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-white"><?php echo htmlspecialchars($t['tournament_name']); ?></strong>
                                            <div class="small text-muted"><?php echo htmlspecialchars($t['game']); ?></div>
                                        </td>
                                        <td class="small text-muted"><?php echo htmlspecialchars($t['tournament_date']); ?></td>
                                        <td>
                                            <?php if ($t['reg_status'] === 'approved'): ?>
                                                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">Approved</span>
                                            <?php elseif ($t['reg_status'] === 'rejected'): ?>
                                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25">Rejected</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25">Pending Approval</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
