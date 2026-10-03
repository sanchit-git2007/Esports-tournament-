<?php
// =======================================================
// File: admin/leaderboard.php
// Purpose: Admin Tournament Standings & Action Center
// =======================================================

$pageTitle = "Tournament Leaderboard - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$tournamentId = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

// Fetch Tournaments for Selector
$tournaments = $pdo->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_date DESC")->fetchAll();
if ($tournamentId <= 0 && !empty($tournaments)) {
    $tournamentId = $tournaments[0]['id'];
}

// 1. Fetch Tournament Info
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->execute([$tournamentId]);
$tourney = $stmt->fetch();

// 2. Fetch Leaderboard Aggregation
$leaderboard = [];
if ($tourney) {
    $lbQuery = $pdo->prepare("
        SELECT 
            tm.id AS team_id,
            tm.team_name,
            tm.team_tag,
            u.name AS captain_name,
            COUNT(mr.id) AS matches_played,
            COALESCE(SUM(CASE WHEN mr.placement = 1 THEN 1 ELSE 0 END), 0) AS wwcd_count,
            COALESCE(SUM(mr.kills), 0) AS total_kills,
            COALESCE(SUM(mr.placement_points), 0) AS total_place_pts,
            COALESCE(SUM(mr.kill_points), 0) AS total_kill_pts,
            COALESCE(SUM(mr.total_points), 0) AS total_points
        FROM tournament_registrations tr
        JOIN teams tm ON tr.team_id = tm.id
        JOIN users u ON tm.captain_id = u.id
        LEFT JOIN matches m ON m.tournament_id = tr.tournament_id AND m.status = 'completed'
        LEFT JOIN match_results mr ON mr.match_id = m.id AND mr.team_id = tm.id
        WHERE tr.tournament_id = ? AND tr.status = 'approved'
        GROUP BY tm.id
        ORDER BY 
            total_points DESC,
            wwcd_count DESC,
            total_place_pts DESC,
            total_kills DESC,
            tm.team_name ASC
    ");
    $lbQuery->execute([$tournamentId]);
    $leaderboard = $lbQuery->fetchAll();
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="tournaments.php" class="text-cyan text-decoration-none">Tournaments</a></li>
            <li class="breadcrumb-item text-white active">Leaderboard</li>
        </ol>
    </nav>

    <?php if (isset($_GET['scored'])): ?>
        <div class="alert alert-success bg-success bg-opacity-25 border-success text-white py-2 small alert-auto-dismiss mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> Match scores entered and total points recalculated automatically!
        </div>
    <?php endif; ?>

    <!-- Header Card -->
    <div class="esports-card p-4 mb-4 border-flame">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <h3 class="text-white mb-1"><i class="fa-solid fa-ranking-star text-gold me-2"></i>Championship Standings</h3>
                <p class="text-light opacity-75 small mb-0">Review live points, generate certificates for podium finishers, and inspect tie-breakers.</p>
            </div>
            <div class="col-lg-6">
                <form action="leaderboard.php" method="GET" class="d-flex gap-2">
                    <select name="tournament_id" class="form-select form-select-custom" onchange="this.form.submit()">
                        <?php foreach ($tournaments as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo ($tournamentId === $t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['tournament_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="results.php?tournament_id=<?php echo $tournamentId; ?>" class="btn btn-neon-primary text-nowrap btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> Enter Scores
                    </a>
                </form>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="esports-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h4 class="text-white mb-0">Official Leaderboard - <?php echo htmlspecialchars($tourney['tournament_name'] ?? ''); ?></h4>
            <a href="certificates.php?tournament_id=<?php echo $tournamentId; ?>" class="btn btn-flame btn-sm">
                <i class="fa-solid fa-award me-1"></i> Issue Certificates
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-esports align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 70px;">Rank</th>
                        <th>Squad</th>
                        <th>Captain</th>
                        <th class="text-center">Matches</th>
                        <th class="text-center">WWCD (Wins)</th>
                        <th class="text-center">Frags (Kills)</th>
                        <th class="text-end">Placement Pts</th>
                        <th class="text-end">Kill Pts</th>
                        <th class="text-end">Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaderboard)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No match results entered for this tournament yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($leaderboard as $row): ?>
                            <tr>
                                <td>
                                    <?php if ($rank === 1 && $row['total_points'] > 0): ?>
                                        <span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-crown me-1"></i>#1</span>
                                    <?php elseif ($rank === 2 && $row['total_points'] > 0): ?>
                                        <span class="badge bg-secondary text-white fw-bold">#2</span>
                                    <?php elseif ($rank === 3 && $row['total_points'] > 0): ?>
                                        <span class="badge bg-dark border border-warning text-warning fw-bold">#3</span>
                                    <?php else: ?>
                                        <span class="text-muted fw-bold ms-2">#<?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-white"><?php echo htmlspecialchars($row['team_name']); ?></strong>
                                    <span class="badge bg-dark border border-secondary text-cyan small ms-1">[<?php echo htmlspecialchars($row['team_tag']); ?>]</span>
                                </td>
                                <td class="small text-light opacity-75"><?php echo htmlspecialchars($row['captain_name']); ?></td>
                                <td class="text-center text-white"><?php echo $row['matches_played']; ?></td>
                                <td class="text-center text-gold fw-bold"><?php echo $row['wwcd_count']; ?></td>
                                <td class="text-center text-flame fw-bold"><?php echo $row['total_kills']; ?></td>
                                <td class="text-end text-cyan">+<?php echo $row['total_place_pts']; ?></td>
                                <td class="text-end text-flame">+<?php echo $row['total_kill_pts']; ?></td>
                                <td class="text-end fw-bold text-gold fs-5">
                                    <?php echo $row['total_points']; ?> <small style="font-size:0.75rem;">Pts</small>
                                </td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
