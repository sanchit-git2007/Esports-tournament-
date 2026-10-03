<?php
// =======================================================
// File: player/team.php
// Purpose: Manage Squad Roster, Add/Remove Players, View Team Stats
// =======================================================

$pageTitle = "My Squad - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/auth.php";

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$msg = "";
$error = "";

// 1. Fetch the Player's Team (Where user is Captain or Member)
$teamStmt = $pdo->prepare("
    SELECT t.*, u.name AS captain_name, u.id AS captain_user_id 
    FROM teams t 
    JOIN users u ON t.captain_id = u.id 
    WHERE t.captain_id = ?
    LIMIT 1
");
$teamStmt->execute([$userId]);
$myTeam = $teamStmt->fetch();

// If not captain, check if they are a registered member
$isCaptain = true;
if (!$myTeam) {
    $isCaptain = false;
    $memberQuery = $pdo->prepare("
        SELECT t.*, u.name AS captain_name, u.id AS captain_user_id 
        FROM team_members tm
        JOIN teams t ON tm.team_id = t.id
        JOIN users u ON t.captain_id = u.id
        WHERE tm.user_id = ?
        LIMIT 1
    ");
    $memberQuery->execute([$userId]);
    $myTeam = $memberQuery->fetch();
}

// If user has no team at all, redirect to create_team.php
if (!$myTeam) {
    header("Location: create_team.php");
    exit;
}

$teamId = $myTeam['id'];

// 2. Handle Action: Add New Squad Member (Captain Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_member') {
    if (!$isCaptain) {
        $error = "Only the Team Captain can add members.";
    } else {
        $ign = trim($_POST['in_game_name'] ?? '');
        $characterId = trim($_POST['in_game_id'] ?? '');
        $role = trim($_POST['player_role'] ?? 'Assaulter');

        if (empty($ign) || empty($characterId)) {
            $error = "Please enter both In-Game Name and Character ID.";
        } else {
            // Check current squad size (BGMI squads max 5 players: 4 active + 1 sub)
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = ?");
            $countStmt->execute([$teamId]);
            $currentMembers = $countStmt->fetchColumn();

            if ($currentMembers >= 5) {
                $error = "Squad is full (Maximum 5 players: 4 Active + 1 Substitute).";
            } else {
                $addStmt = $pdo->prepare("
                    INSERT INTO team_members (team_id, user_id, in_game_name, in_game_id, player_role) 
                    VALUES (?, NULL, ?, ?, ?)
                ");
                $addStmt->execute([$teamId, $ign, $characterId, $role]);
                $msg = "Player '{$ign}' added to squad successfully!";
            }
        }
    }
}

// 3. Handle Action: Remove Member (Captain Only)
if (isset($_GET['remove_member'])) {
    if (!$isCaptain) {
        $error = "Only the Team Captain can remove members.";
    } else {
        $memberId = (int)$_GET['remove_member'];
        // Ensure captain cannot remove themselves
        $memCheck = $pdo->prepare("SELECT user_id FROM team_members WHERE id = ? AND team_id = ?");
        $memCheck->execute([$memberId, $teamId]);
        $member = $memCheck->fetch();

        if ($member && $member['user_id'] == $userId) {
            $error = "The Team Captain cannot be removed from the squad.";
        } else {
            $delStmt = $pdo->prepare("DELETE FROM team_members WHERE id = ? AND team_id = ?");
            $delStmt->execute([$memberId, $teamId]);
            $msg = "Squad member removed successfully.";
        }
    }
}

// 4. Fetch All Squad Members
$membersStmt = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY id ASC");
$membersStmt->execute([$teamId]);
$squadMembers = $membersStmt->fetchAll();

// 5. Fetch Team Tournament Performance Stats
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT mr.match_id) AS total_matches,
        COALESCE(SUM(mr.kills), 0) AS total_kills,
        COALESCE(SUM(mr.placement_points), 0) AS total_placement_pts,
        COALESCE(SUM(mr.total_points), 0) AS total_points,
        COALESCE(SUM(CASE WHEN mr.placement = 1 THEN 1 ELSE 0 END), 0) AS wwcd_wins
    FROM match_results mr
    WHERE mr.team_id = ?
");
$statsStmt->execute([$teamId]);
$teamStats = $statsStmt->fetch();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-muted active">My Squad</li>
        </ol>
    </nav>

    <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success bg-success bg-opacity-25 border-success text-white py-2 small alert-auto-dismiss mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> Congratulations! Your squad has been successfully registered. You can now add teammates and register for tournaments.
        </div>
    <?php endif; ?>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-success bg-success bg-opacity-25 border-success text-white py-2 small alert-auto-dismiss mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-white py-2 small mb-4">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Team Header Card -->
    <div class="esports-card mb-4 border-cyan">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="feature-icon-box mb-0 text-cyan" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="fa-solid fa-shield-cat"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="text-white mb-0"><?php echo htmlspecialchars($myTeam['team_name']); ?></h2>
                            <span class="badge bg-dark text-cyan border border-info border-opacity-25">[<?php echo htmlspecialchars($myTeam['team_tag']); ?>]</span>
                        </div>
                        <p class="text-muted mb-0 small">
                            <strong>Captain:</strong> <?php echo htmlspecialchars($myTeam['captain_name']); ?> 
                            <?php if ($isCaptain): ?>
                                <span class="badge bg-warning text-dark ms-1">You are Captain</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
                <?php if ($isCaptain): ?>
                    <button type="button" class="btn btn-neon-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                        <i class="fa-solid fa-user-plus me-1"></i> Add Teammate
                    </button>
                    <a href="edit_team.php" class="btn btn-neon-outline btn-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Squad
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Squad Overall Stats Counters -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="esports-card text-center p-3">
                <span class="text-muted small text-uppercase">Matches Played</span>
                <h3 class="text-white mb-0 mt-1"><?php echo $teamStats['total_matches']; ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="esports-card text-center p-3">
                <span class="text-muted small text-uppercase">Winner Winner (WWCD)</span>
                <h3 class="text-gold mb-0 mt-1"><i class="fa-solid fa-trophy text-gold me-1"></i><?php echo $teamStats['wwcd_wins']; ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="esports-card text-center p-3">
                <span class="text-muted small text-uppercase">Total Frags (Kills)</span>
                <h3 class="text-flame mb-0 mt-1"><?php echo $teamStats['total_kills']; ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="esports-card text-center p-3">
                <span class="text-muted small text-uppercase">Total Points</span>
                <h3 class="text-cyan mb-0 mt-1"><?php echo $teamStats['total_points']; ?></h3>
            </div>
        </div>
    </div>

    <!-- Squad Roster Table -->
    <div class="esports-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="text-white mb-0"><i class="fa-solid fa-users text-cyan me-2"></i>Official Squad Roster</h5>
                <small class="text-muted">4 Active Players + 1 Substitute maximum</small>
            </div>
            <span class="badge bg-dark border border-secondary text-cyan">
                <?php echo count($squadMembers); ?> / 5 Slots
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-esports align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>In-Game Name (IGN)</th>
                        <th>BGMI Character ID</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <?php if ($isCaptain): ?>
                            <th class="text-end">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($squadMembers)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No squad members found.</td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($squadMembers as $mem): ?>
                            <tr>
                                <td class="text-muted"><?php echo $i++; ?></td>
                                <td>
                                    <strong class="text-white"><?php echo htmlspecialchars($mem['in_game_name']); ?></strong>
                                    <?php if ($mem['user_id'] == $myTeam['captain_user_id']): ?>
                                        <span class="badge bg-warning text-dark ms-1 small">Captain</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="text-cyan bg-dark px-2 py-1 rounded"><?php echo htmlspecialchars($mem['in_game_id']); ?></code>
                                </td>
                                <td>
                                    <span class="badge bg-surface border border-secondary border-opacity-50 text-white">
                                        <?php echo htmlspecialchars($mem['player_role']); ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?php echo date('M d, Y', strtotime($mem['joined_at'])); ?></td>
                                <?php if ($isCaptain): ?>
                                    <td class="text-end">
                                        <?php if ($mem['user_id'] != $myTeam['captain_user_id']): ?>
                                            <a href="team.php?remove_member=<?php echo $mem['id']; ?>" 
                                               class="btn btn-outline-danger btn-sm py-0 px-2" 
                                               onclick="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($mem['in_game_name']); ?> from the squad?');"
                                               title="Remove Member">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary opacity-50">Locked</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<?php if ($isCaptain): ?>
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border border-secondary border-opacity-50">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title brand-font text-cyan" id="addMemberModalLabel">
                    <i class="fa-solid fa-user-plus me-2"></i> Add Squad Teammate
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <form action="team.php" method="POST">
                <input type="hidden" name="action" value="add_member">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-custom">In-Game Name (IGN) <span class="text-danger">*</span></label>
                        <input type="text" name="in_game_name" class="form-control form-control-custom" placeholder="e.g. SOUL_Viper" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-custom">BGMI Character ID <span class="text-danger">*</span></label>
                        <input type="text" name="in_game_id" class="form-control form-control-custom" placeholder="e.g. 5123456790" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-custom">Player Role</label>
                        <select name="player_role" class="form-select form-select-custom">
                            <option value="Assaulter">Assaulter (Entry Fragger)</option>
                            <option value="Sniper">Sniper (DMR / Long Range)</option>
                            <option value="Support">Support (Healer / Cover)</option>
                            <option value="IGL">In-Game Leader (IGL)</option>
                            <option value="Substitute">Substitute (5th Player)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-neon-primary btn-sm">Add to Roster</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once "../includes/footer.php"; ?>
