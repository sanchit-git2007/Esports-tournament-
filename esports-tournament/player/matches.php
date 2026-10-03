<?php
// =======================================================
// File: player/matches.php
// Purpose: Player Match Schedule & Custom Room Access Center
// =======================================================

$pageTitle = "My Matches & Rooms - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/auth.php";

$userId = $_SESSION['user_id'];

// 1. Fetch Player's Team (Where user is Captain or Member)
$teamStmt = $pdo->prepare("
    SELECT t.*, u.name AS captain_name 
    FROM teams t 
    JOIN users u ON t.captain_id = u.id 
    WHERE t.captain_id = ? 
    LIMIT 1
");
$teamStmt->execute([$userId]);
$myTeam = $teamStmt->fetch();

if (!$myTeam) {
    $memberQuery = $pdo->prepare("
        SELECT t.*, u.name AS captain_name 
        FROM team_members tm 
        JOIN teams t ON tm.team_id = t.id 
        JOIN users u ON t.captain_id = u.id 
        WHERE tm.user_id = ? 
        LIMIT 1
    ");
    $memberQuery->execute([$userId]);
    $myTeam = $memberQuery->fetch();
}

$matches = [];
if ($myTeam) {
    // 2. Fetch matches only for tournaments where the squad is approved
    $stmt = $pdo->prepare("
        SELECT m.*, t.tournament_name, t.game, t.id AS tourney_id
        FROM matches m
        JOIN tournaments t ON m.tournament_id = t.id
        JOIN tournament_registrations tr ON tr.tournament_id = t.id
        WHERE tr.team_id = ? AND tr.status = 'approved'
        ORDER BY m.match_date ASC, m.match_time ASC
    ");
    $stmt->execute([$myTeam['id']]);
    $matches = $stmt->fetchAll();
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">Match Schedule & Rooms</li>
        </ol>
    </nav>

    <!-- Header Banner -->
    <div class="esports-card mb-4 border-cyan">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-dark text-cyan border border-info border-opacity-25 mb-2">
                    <i class="fa-solid fa-crosshairs me-1"></i> Custom Match Portal
                </span>
                <h2 class="text-white mb-1">Squad Match <span class="text-cyan">Credentials</span></h2>
                <p class="text-light opacity-75 small mb-0">
                    Access Room IDs and Passwords for all confirmed tournaments. Join custom matches on time to avoid disqualification.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <?php if ($myTeam): ?>
                    <span class="badge bg-dark border border-secondary text-cyan p-2">
                        <i class="fa-solid fa-shield-halved me-1"></i> Squad: <?php echo htmlspecialchars($myTeam['team_name']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$myTeam): ?>
        <div class="esports-card text-center py-5">
            <i class="fa-solid fa-users text-muted fs-1 mb-3 opacity-50"></i>
            <h4 class="text-white mb-2">No Squad Found</h4>
            <p class="text-light opacity-75 small mb-3">You must create or join a squad before viewing match schedules.</p>
            <a href="create_team.php" class="btn btn-neon-primary btn-sm">Create Squad</a>
        </div>
    <?php elseif (empty($matches)): ?>
        <div class="esports-card text-center py-5">
            <i class="fa-solid fa-calendar-xmark text-muted fs-1 mb-3 opacity-50"></i>
            <h4 class="text-white mb-2">No Confirmed Matches Yet</h4>
            <p class="text-light opacity-75 small mb-3">Your squad does not have approved registrations with scheduled matches at this time.</p>
            <a href="../tournaments/index.php" class="btn btn-neon-primary btn-sm">Explore Tournaments</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($matches as $m): ?>
                <div class="col-lg-6">
                    <div class="esports-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-dark border border-secondary text-cyan small mb-1">
                                    <?php echo htmlspecialchars($m['tournament_name']); ?>
                                </span>
                                <h4 class="text-white mb-0"><?php echo htmlspecialchars($m['match_name']); ?></h4>
                            </div>
                            <?php
                            $badge = 'bg-secondary';
                            if ($m['status'] === 'scheduled') $badge = 'bg-primary';
                            elseif ($m['status'] === 'live') $badge = 'bg-danger animate-pulse';
                            elseif ($m['status'] === 'completed') $badge = 'bg-success';
                            ?>
                            <span class="badge <?php echo $badge; ?> text-uppercase small">
                                <?php echo $m['status']; ?>
                            </span>
                        </div>

                        <!-- Match Meta Details -->
                        <div class="row g-2 mb-3 small">
                            <div class="col-6">
                                <div class="bg-dark p-2 rounded border border-secondary border-opacity-25">
                                    <span class="text-light opacity-75 d-block">Map:</span>
                                    <strong class="text-cyan"><i class="fa-solid fa-map me-1"></i><?php echo htmlspecialchars($m['map']); ?></strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-dark p-2 rounded border border-secondary border-opacity-25">
                                    <span class="text-light opacity-75 d-block">Time:</span>
                                    <strong class="text-white"><i class="fa-solid fa-clock me-1 text-cyan"></i><?php echo date('M d, h:i A', strtotime($m['match_date'] . ' ' . $m['match_time'])); ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Room Credentials Box -->
                        <div class="bg-surface p-3 rounded border border-info border-opacity-25 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-light opacity-75 fw-bold text-uppercase">
                                    <i class="fa-solid fa-key text-warning me-1"></i> Custom Room Credentials
                                </span>
                                <span class="badge bg-dark text-warning border border-warning border-opacity-50">Private</span>
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <span class="small text-light opacity-75 d-block">Room ID:</span>
                                    <?php if (!empty($m['room_id'])): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <code class="text-warning fs-5 fw-bold"><?php echo htmlspecialchars($m['room_id']); ?></code>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Will be released 15 mins before match</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-6">
                                    <span class="small text-light opacity-75 d-block">Password:</span>
                                    <?php if (!empty($m['room_password'])): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <code class="text-warning fs-5 fw-bold"><?php echo htmlspecialchars($m['room_password']); ?></code>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Pending host release</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($m['status'] === 'completed'): ?>
                            <a href="../tournaments/leaderboard.php?id=<?php echo $m['tourney_id']; ?>" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fa-solid fa-ranking-star text-cyan me-1"></i> View Live Standings
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
