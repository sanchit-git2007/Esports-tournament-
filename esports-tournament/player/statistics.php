<?php
// =======================================================
// File: player/statistics.php
// Purpose: Player & Squad Analytics, Win Rates & Frag Stats
// =======================================================

$pageTitle = "Squad Statistics - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/auth.php";

$userId = $_SESSION['user_id'];

// 1. Fetch Player's Team
$teamStmt = $pdo->prepare("
    SELECT t.* 
    FROM teams t 
    WHERE t.captain_id = ? 
    LIMIT 1
");
$teamStmt->execute([$userId]);
$myTeam = $teamStmt->fetch();

if (!$myTeam) {
    $memberQuery = $pdo->prepare("
        SELECT t.* 
        FROM team_members tm 
        JOIN teams t ON tm.team_id = t.id 
        WHERE tm.user_id = ? 
        LIMIT 1
    ");
    $memberQuery->execute([$userId]);
    $myTeam = $memberQuery->fetch();
}

$teamStats = [
    'total_matches' => 0,
    'wwcd_wins' => 0,
    'total_kills' => 0,
    'total_points' => 0,
    'avg_kills' => 0,
];

$matchHistory = [];

if ($myTeam) {
    // Aggregated Squad Stats
    $statsQuery = $pdo->prepare("
        SELECT 
            COUNT(mr.id) AS total_matches,
            COALESCE(SUM(CASE WHEN mr.placement = 1 THEN 1 ELSE 0 END), 0) AS wwcd_wins,
            COALESCE(SUM(mr.kills), 0) AS total_kills,
            COALESCE(SUM(mr.total_points), 0) AS total_points
        FROM match_results mr
        WHERE mr.team_id = ?
    ");
    $statsQuery->execute([$myTeam['id']]);
    $res = $statsQuery->fetch();

    if ($res) {
        $teamStats['total_matches'] = (int)$res['total_matches'];
        $teamStats['wwcd_wins'] = (int)$res['wwcd_wins'];
        $teamStats['total_kills'] = (int)$res['total_kills'];
        $teamStats['total_points'] = (int)$res['total_points'];
        if ($teamStats['total_matches'] > 0) {
            $teamStats['avg_kills'] = round($teamStats['total_kills'] / $teamStats['total_matches'], 1);
        }
    }

    // Match-by-Match History
    $historyStmt = $pdo->prepare("
        SELECT mr.*, m.match_name, m.map, m.match_date, t.tournament_name
        FROM match_results mr
        JOIN matches m ON mr.match_id = m.id
        JOIN tournaments t ON m.tournament_id = t.id
        WHERE mr.team_id = ?
        ORDER BY mr.entered_at DESC
    ");
    $historyStmt->execute([$myTeam['id']]);
    $matchHistory = $historyStmt->fetchAll();
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">Squad Statistics</li>
        </ol>
    </nav>

    <!-- Header Banner -->
    <div class="esports-card mb-4 border-cyan">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-dark text-cyan border border-info border-opacity-25 mb-2">
                    <i class="fa-solid fa-chart-line me-1"></i> Combat Analytics
                </span>
                <h2 class="text-white mb-1">Squad Combat <span class="text-cyan">Telemetry</span></h2>
                <p class="text-light opacity-75 small mb-0">
                    Track overall win rate, average frags per match, placement efficiency, and match-by-match score telemetry.
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
            <p class="text-light opacity-75 small mb-3">Create or join a squad to record performance statistics.</p>
            <a href="create_team.php" class="btn btn-neon-primary btn-sm">Create Squad</a>
        </div>
    <?php else: ?>
        <!-- 4 Key Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="esports-card text-center p-3">
                    <span class="text-light opacity-75 small text-uppercase">Matches Played</span>
                    <h3 class="text-white mb-0 mt-1"><?php echo $teamStats['total_matches']; ?></h3>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="esports-card text-center p-3">
                    <span class="text-light opacity-75 small text-uppercase">WWCD (Wins)</span>
                    <h3 class="text-gold mb-0 mt-1"><i class="fa-solid fa-trophy text-gold me-1"></i><?php echo $teamStats['wwcd_wins']; ?></h3>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="esports-card text-center p-3">
                    <span class="text-light opacity-75 small text-uppercase">Total Frags (Kills)</span>
                    <h3 class="text-flame mb-0 mt-1"><?php echo $teamStats['total_kills']; ?></h3>
                    <small class="text-light opacity-75 d-block mt-1">Avg: <?php echo $teamStats['avg_kills']; ?> / match</small>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="esports-card text-center p-3">
                    <span class="text-light opacity-75 small text-uppercase">Cumulative Points</span>
                    <h3 class="text-cyan mb-0 mt-1"><?php echo $teamStats['total_points']; ?></h3>
                </div>
            </div>
        </div>

        <!-- Match History Table -->
        <div class="esports-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="text-white mb-0"><i class="fa-solid fa-clock-rotate-left text-cyan me-2"></i>Match-by-Match Telemetry</h4>
                <span class="badge bg-dark border border-secondary text-cyan"><?php echo count($matchHistory); ?> Matches Recorded</span>
            </div>

            <div class="table-responsive">
                <table class="table table-esports align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tournament</th>
                            <th>Match Round</th>
                            <th>Map</th>
                            <th>Placement</th>
                            <th>Kills</th>
                            <th class="text-end">Placement Pts</th>
                            <th class="text-end">Kill Pts</th>
                            <th class="text-end">Total Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($matchHistory)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No completed match results found for your squad yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($matchHistory as $hist): ?>
                                <tr>
                                    <td>
                                        <strong class="text-white"><?php echo htmlspecialchars($hist['tournament_name']); ?></strong>
                                    </td>
                                    <td class="text-light opacity-75"><?php echo htmlspecialchars($hist['match_name']); ?></td>
                                    <td>
                                        <span class="badge bg-dark border border-secondary text-cyan"><?php echo htmlspecialchars($hist['map']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($hist['placement'] == 1): ?>
                                            <span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-trophy me-1"></i>#1 WWCD</span>
                                        <?php elseif ($hist['placement'] == 2): ?>
                                            <span class="badge bg-secondary text-white fw-bold">#2</span>
                                        <?php elseif ($hist['placement'] == 3): ?>
                                            <span class="badge bg-dark border border-warning text-warning fw-bold">#3</span>
                                        <?php else: ?>
                                            <span class="text-light opacity-75 fw-bold">#<?php echo $hist['placement']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-flame"><?php echo $hist['kills']; ?></td>
                                    <td class="text-end text-cyan">+<?php echo $hist['placement_points']; ?></td>
                                    <td class="text-end text-flame">+<?php echo $hist['kill_points']; ?></td>
                                    <td class="text-end fw-bold text-gold fs-6"><?php echo $hist['total_points']; ?> Pts</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
