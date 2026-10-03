<?php
// =======================================================
// File: player/tournaments.php
// Purpose: Player Registered Tournaments & Statuses
// =======================================================

$pageTitle = "My Tournaments - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/auth.php";

$userId = $_SESSION['user_id'];

// 1. Fetch Player's Team
$teamStmt = $pdo->prepare("SELECT * FROM teams WHERE captain_id = ? LIMIT 1");
$teamStmt->execute([$userId]);
$myTeam = $teamStmt->fetch();

if (!$myTeam) {
    $memberQuery = $pdo->prepare("SELECT t.* FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE tm.user_id = ? LIMIT 1");
    $memberQuery->execute([$userId]);
    $myTeam = $memberQuery->fetch();
}

$myTournaments = [];
if ($myTeam) {
    $stmt = $pdo->prepare("
        SELECT tr.*, t.tournament_name, t.game, t.tournament_type, t.tournament_date, t.status AS tourney_status, t.id AS tourney_id
        FROM tournament_registrations tr
        JOIN tournaments t ON tr.tournament_id = t.id
        WHERE tr.team_id = ?
        ORDER BY t.tournament_date DESC
    ");
    $stmt->execute([$myTeam['id']]);
    $myTournaments = $stmt->fetchAll();
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">My Tournaments</li>
        </ol>
    </nav>

    <div class="esports-card mb-4 border-cyan">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="text-white mb-1"><i class="fa-solid fa-trophy text-cyan me-2"></i>My Squad's Tournaments</h3>
                <p class="text-light opacity-75 small mb-0">Track application statuses, match schedules, and championship brackets.</p>
            </div>
            <a href="../tournaments/index.php" class="btn btn-neon-primary btn-sm">
                <i class="fa-solid fa-magnifying-glass me-1"></i> Browse More Tournaments
            </a>
        </div>
    </div>

    <?php if (!$myTeam): ?>
        <div class="esports-card text-center py-5">
            <i class="fa-solid fa-users text-muted fs-1 mb-3 opacity-50"></i>
            <h4 class="text-white mb-2">No Squad Found</h4>
            <p class="text-light opacity-75 small mb-3">Create a squad to start registering for BGMI tournaments.</p>
            <a href="create_team.php" class="btn btn-neon-primary btn-sm">Create Squad</a>
        </div>
    <?php elseif (empty($myTournaments)): ?>
        <div class="esports-card text-center py-5">
            <i class="fa-solid fa-trophy text-muted fs-1 mb-3 opacity-50"></i>
            <h4 class="text-white mb-2">No Tournaments Registered</h4>
            <p class="text-light opacity-75 small mb-3">Your squad has not signed up for any tournaments yet.</p>
            <a href="../tournaments/index.php" class="btn btn-neon-primary btn-sm">Explore Open Brackets</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($myTournaments as $t): ?>
                <div class="col-md-6">
                    <div class="esports-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <?php if ($t['status'] === 'approved'): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success">
                                        <i class="fa-solid fa-check me-1"></i> Approved
                                    </span>
                                <?php elseif ($t['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">
                                        <i class="fa-solid fa-xmark me-1"></i> Rejected
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning">
                                        <i class="fa-solid fa-clock me-1"></i> Pending Admin Approval
                                    </span>
                                <?php endif; ?>

                                <span class="badge bg-dark border border-secondary text-cyan">
                                    <?php echo strtoupper($t['tournament_type']); ?>
                                </span>
                            </div>

                            <h4 class="text-white mb-1"><?php echo htmlspecialchars($t['tournament_name']); ?></h4>
                            <div class="small text-light opacity-75 mb-3">
                                <div><i class="fa-solid fa-calendar me-1 text-cyan"></i>Date: <?php echo date('M d, Y', strtotime($t['tournament_date'])); ?></div>
                                <div><i class="fa-solid fa-flag me-1 text-cyan"></i>Phase: <?php echo strtoupper(str_replace('_', ' ', $t['tourney_status'])); ?></div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="../tournaments/details.php?id=<?php echo $t['tourney_id']; ?>" class="btn btn-outline-secondary w-50 btn-sm">
                                View Details
                            </a>
                            <a href="../tournaments/leaderboard.php?id=<?php echo $t['tourney_id']; ?>" class="btn btn-neon-outline w-50 btn-sm">
                                Leaderboard
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
