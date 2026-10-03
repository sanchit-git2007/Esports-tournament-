<?php
// =======================================================
// File: admin/statistics.php
// Purpose: Tournament Analytics & Visual Performance Charts
// =======================================================

$pageTitle = "Tournament Statistics - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

// Fetch Tournaments for Selector
$tournaments = $pdo->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_date DESC")->fetchAll();
$selectedTournamentId = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : ($tournaments[0]['id'] ?? 0);

// 1. Fetch High-Level Metrics
$metrics = [
    'teams_count' => 0,
    'matches_completed' => 0,
    'total_kills' => 0,
    'total_points' => 0,
    'mvp_team' => 'N/A',
    'highest_kills_team' => 'N/A',
];

$teamNames = [];
$teamPoints = [];
$teamKills = [];

if ($selectedTournamentId > 0) {
    // Teams Count
    $tStmt = $pdo->prepare("SELECT COUNT(*) FROM tournament_registrations WHERE tournament_id = ? AND status = 'approved'");
    $tStmt->execute([$selectedTournamentId]);
    $metrics['teams_count'] = $tStmt->fetchColumn();

    // Matches Completed
    $mStmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id = ? AND status = 'completed'");
    $mStmt->execute([$selectedTournamentId]);
    $metrics['matches_completed'] = $mStmt->fetchColumn();

    // Team Standings & Aggregates
    $stmt = $pdo->prepare("
        SELECT 
            tm.team_name,
            tm.team_tag,
            COALESCE(SUM(mr.kills), 0) AS kills,
            COALESCE(SUM(mr.placement_points), 0) AS place_pts,
            COALESCE(SUM(mr.total_points), 0) AS total_pts,
            COALESCE(SUM(CASE WHEN mr.placement = 1 THEN 1 ELSE 0 END), 0) AS wins
        FROM tournament_registrations tr
        JOIN teams tm ON tr.team_id = tm.id
        LEFT JOIN matches m ON m.tournament_id = tr.tournament_id AND m.status = 'completed'
        LEFT JOIN match_results mr ON mr.match_id = m.id AND mr.team_id = tm.id
        WHERE tr.tournament_id = ? AND tr.status = 'approved'
        GROUP BY tm.id
        ORDER BY total_pts DESC
    ");
    $stmt->execute([$selectedTournamentId]);
    $standings = $stmt->fetchAll();

    if (!empty($standings)) {
        $metrics['total_points'] = array_sum(array_column($standings, 'total_pts'));
        $metrics['total_kills'] = array_sum(array_column($standings, 'kills'));
        
        // Top Points Team (Champion)
        if ($standings[0]['total_pts'] > 0) {
            $metrics['mvp_team'] = $standings[0]['team_name'] . " (" . $standings[0]['total_pts'] . " Pts)";
        }

        // Top Kills Team
        usort($standings, function($a, $b) { return $b['kills'] <=> $a['kills']; });
        if ($standings[0]['kills'] > 0) {
            $metrics['highest_kills_team'] = $standings[0]['team_name'] . " (" . $standings[0]['kills'] . " Frags)";
        }

        // Prepare Data for Charts
        foreach ($standings as $st) {
            $teamNames[] = $st['team_name'] . " [" . $st['team_tag'] . "]";
            $teamPoints[] = (int)$st['total_pts'];
            $teamKills[] = (int)$st['kills'];
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">Tournament Statistics</li>
        </ol>
    </nav>

    <!-- Header & Tournament Selector -->
    <div class="esports-card p-4 mb-4 border-cyan">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <h3 class="text-white mb-1"><i class="fa-solid fa-chart-pie text-cyan me-2"></i>Tournament Analytics & Charts</h3>
                <p class="text-light opacity-75 small mb-0">Visual performance breakdowns, kill share distributions, and championship metrics.</p>
            </div>
            <div class="col-lg-6">
                <form action="statistics.php" method="GET">
                    <label class="form-label-custom">Select Tournament</label>
                    <select name="tournament_id" class="form-select form-select-custom" onchange="this.form.submit()">
                        <?php foreach ($tournaments as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo ($selectedTournamentId === $t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['tournament_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- 4 KPI Stat Counters -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="esports-card">
                <span class="text-light opacity-75 small text-uppercase">Confirmed Teams</span>
                <h3 class="text-white mb-0 mt-1"><?php echo $metrics['teams_count']; ?> Squads</h3>
                <div class="small text-cyan mt-1"><i class="fa-solid fa-shield-halved me-1"></i>Approved Roster</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="esports-card">
                <span class="text-light opacity-75 small text-uppercase">Rounds Played</span>
                <h3 class="text-cyan mb-0 mt-1"><?php echo $metrics['matches_completed']; ?> Matches</h3>
                <div class="small text-light opacity-75 mt-1"><i class="fa-solid fa-check-circle text-success me-1"></i>Scored & Completed</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="esports-card">
                <span class="text-light opacity-75 small text-uppercase">Total Frags (Kills)</span>
                <h3 class="text-flame mb-0 mt-1"><?php echo $metrics['total_kills']; ?> Frags</h3>
                <div class="small text-light opacity-75 mt-1">Top Frag: <strong class="text-white"><?php echo $metrics['highest_kills_team']; ?></strong></div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="esports-card">
                <span class="text-light opacity-75 small text-uppercase">Total Points Awarded</span>
                <h3 class="text-gold mb-0 mt-1"><?php echo $metrics['total_points']; ?> Pts</h3>
                <div class="small text-light opacity-75 mt-1">Leader: <strong class="text-gold"><?php echo $metrics['mvp_team']; ?></strong></div>
            </div>
        </div>
    </div>

    <!-- Visual Charts (Chart.js) -->
    <div class="row g-4 mb-4">
        <!-- Bar Chart: Points Comparison -->
        <div class="col-lg-7">
            <div class="esports-card h-100">
                <h5 class="text-white mb-3"><i class="fa-solid fa-chart-column text-cyan me-2"></i>Squad Points Comparison</h5>
                <div style="height: 300px;">
                    <canvas id="pointsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Doughnut Chart: Kill Share -->
        <div class="col-lg-5">
            <div class="esports-card h-100">
                <h5 class="text-white mb-3"><i class="fa-solid fa-chart-pie text-flame me-2"></i>Kill Distribution (Frag Share)</h5>
                <div style="height: 300px;">
                    <canvas id="killsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const teamLabels = <?php echo json_encode($teamNames); ?>;
    const pointsData = <?php echo json_encode($teamPoints); ?>;
    const killsData = <?php echo json_encode($teamKills); ?>;

    // 1. Points Bar Chart
    const ctxPoints = document.getElementById('pointsChart').getContext('2d');
    new Chart(ctxPoints, {
        type: 'bar',
        data: {
            labels: teamLabels,
            datasets: [{
                label: 'Total Points',
                data: pointsData,
                backgroundColor: 'rgba(0, 240, 255, 0.7)',
                borderColor: '#00f0ff',
                borderWidth: 1.5,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.08)' },
                    ticks: { color: '#cbd5e1' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#cbd5e1' }
                }
            }
        }
    });

    // 2. Kills Doughnut Chart
    const ctxKills = document.getElementById('killsChart').getContext('2d');
    new Chart(ctxKills, {
        type: 'doughnut',
        data: {
            labels: teamLabels,
            datasets: [{
                data: killsData,
                backgroundColor: [
                    '#ff4655', '#00f0ff', '#ffb800', '#10b981', '#8b5cf6', 
                    '#ec4899', '#3b82f6', '#f59e0b', '#14b8a6', '#6366f1'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#cbd5e1', font: { size: 11 } }
                }
            }
        }
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>
