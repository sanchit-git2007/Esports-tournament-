<?php
// =======================================================
// File: tournaments/leaderboard.php
// Purpose: Public Live Championship Leaderboard & Match Breakdown
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Championship Leaderboard - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";

$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0);

// Fetch Tournaments for Selector
$tournaments = $pdo->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_date DESC")->fetchAll();
if ($tournamentId <= 0 && !empty($tournaments)) {
    $tournamentId = $tournaments[0]['id'];
}

// 1. Fetch Tournament Info
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->execute([$tournamentId]);
$tourney = $stmt->fetch();

// 2. Fetch Leaderboard Aggregation Query with Tie-Breaker Ordering
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
            total_points DESC,          -- 1. Highest Total Points
            wwcd_count DESC,            -- 2. Tie-Breaker 1: Most Wins / Chicken Dinners
            total_place_pts DESC,       -- 3. Tie-Breaker 2: Most Placement Points
            total_kills DESC,           -- 4. Tie-Breaker 3: Most Total Kills
            tm.team_name ASC
    ");
    $lbQuery->execute([$tournamentId]);
    $leaderboard = $lbQuery->fetchAll();
}

// 3. Fetch Completed Matches for Breakdown
$completedMatches = [];
if ($tourney) {
    $mStmt = $pdo->prepare("SELECT * FROM matches WHERE tournament_id = ? AND status = 'completed' ORDER BY match_number ASC");
    $mStmt->execute([$tournamentId]);
    $completedMatches = $mStmt->fetchAll();
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-cyan text-decoration-none">Tournaments</a></li>
            <li class="breadcrumb-item"><a href="details.php?id=<?php echo $tournamentId; ?>" class="text-cyan text-decoration-none"><?php echo htmlspecialchars($tourney['tournament_name'] ?? 'Championship'); ?></a></li>
            <li class="breadcrumb-item text-white active">Live Leaderboard</li>
        </ol>
    </nav>

    <!-- Header Banner & Tournament Selector -->
    <div class="esports-card p-4 p-md-5 mb-5 border-cyan">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <span class="badge bg-dark text-gold border border-warning border-opacity-25 mb-2">
                    <i class="fa-solid fa-ranking-star me-1"></i> Live Official Standings
                </span>
                <h1 class="hero-title text-white mb-2">Championship <span class="text-gold">Leaderboard</span></h1>
                <p class="text-light opacity-75 small mb-0">
                    Real-time rankings computed automatically based on BGMI placement points and kill frags.
                </p>
            </div>
            <div class="col-lg-5">
                <form action="leaderboard.php" method="GET">
                    <label class="form-label-custom">Select Tournament</label>
                    <select name="id" class="form-select form-select-custom" onchange="this.form.submit()">
                        <?php foreach ($tournaments as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo ($tournamentId === $t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['tournament_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- Podium Cards for Top 3 (if results exist) -->
    <?php if (!empty($leaderboard) && $leaderboard[0]['total_points'] > 0): ?>
        <div class="row g-4 mb-5 justify-content-center align-items-end">
            <!-- 2nd Place -->
            <?php if (isset($leaderboard[1])): $s = $leaderboard[1]; ?>
                <div class="col-md-4 col-sm-6 order-2 order-md-1">
                    <div class="esports-card text-center p-4 border-secondary border-opacity-50" style="background: linear-gradient(180deg, rgba(203, 213, 225, 0.08) 0%, rgba(18, 24, 38, 1) 100%);">
                        <div class="badge bg-secondary text-white rounded-pill px-3 py-1 mb-2 fw-bold">#2 RUNNER UP</div>
                        <h4 class="text-white mb-1"><?php echo htmlspecialchars($s['team_name']); ?></h4>
                        <span class="badge bg-dark border border-secondary text-cyan small mb-3">[<?php echo htmlspecialchars($s['team_tag']); ?>]</span>
                        
                        <div class="d-flex justify-content-around bg-dark p-2 rounded small text-light opacity-75">
                            <div><strong class="text-white d-block"><?php echo $s['total_kills']; ?></strong> Kills</div>
                            <div><strong class="text-gold d-block"><?php echo $s['wwcd_count']; ?></strong> WWCD</div>
                            <div><strong class="text-cyan d-block"><?php echo $s['total_points']; ?></strong> Pts</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 1st Place (Champion) -->
            <?php if (isset($leaderboard[0])): $c = $leaderboard[0]; ?>
                <div class="col-md-4 col-sm-6 order-1 order-md-2">
                    <div class="esports-card text-center p-4 border-warning" style="background: linear-gradient(180deg, rgba(255, 184, 0, 0.15) 0%, rgba(18, 24, 38, 1) 100%); transform: translateY(-10px);">
                        <i class="fa-solid fa-crown text-gold fs-1 mb-2 d-block"></i>
                        <div class="badge bg-warning text-dark rounded-pill px-3 py-1 mb-2 fw-bold">#1 CHAMPION</div>
                        <h3 class="text-white mb-1"><?php echo htmlspecialchars($c['team_name']); ?></h3>
                        <span class="badge bg-dark border border-warning text-gold small mb-3">[<?php echo htmlspecialchars($c['team_tag']); ?>]</span>
                        
                        <div class="d-flex justify-content-around bg-dark p-3 rounded text-light opacity-75">
                            <div><strong class="text-white fs-5 d-block"><?php echo $c['total_kills']; ?></strong> Kills</div>
                            <div><strong class="text-gold fs-5 d-block"><?php echo $c['wwcd_count']; ?></strong> WWCD</div>
                            <div><strong class="text-gold fs-4 fw-bold d-block"><?php echo $c['total_points']; ?></strong> Total Pts</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 3rd Place -->
            <?php if (isset($leaderboard[2])): $t = $leaderboard[2]; ?>
                <div class="col-md-4 col-sm-6 order-3 order-md-3">
                    <div class="esports-card text-center p-4 border-secondary border-opacity-50" style="background: linear-gradient(180deg, rgba(205, 127, 50, 0.08) 0%, rgba(18, 24, 38, 1) 100%);">
                        <div class="badge bg-dark text-warning border border-warning rounded-pill px-3 py-1 mb-2 fw-bold">#3 THIRD PLACE</div>
                        <h4 class="text-white mb-1"><?php echo htmlspecialchars($t['team_name']); ?></h4>
                        <span class="badge bg-dark border border-secondary text-cyan small mb-3">[<?php echo htmlspecialchars($t['team_tag']); ?>]</span>
                        
                        <div class="d-flex justify-content-around bg-dark p-2 rounded small text-light opacity-75">
                            <div><strong class="text-white d-block"><?php echo $t['total_kills']; ?></strong> Kills</div>
                            <div><strong class="text-gold d-block"><?php echo $t['wwcd_count']; ?></strong> WWCD</div>
                            <div><strong class="text-cyan d-block"><?php echo $t['total_points']; ?></strong> Pts</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Full Leaderboard Table -->
    <div class="esports-card mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="text-white mb-0"><i class="fa-solid fa-list-ol text-cyan me-2"></i>Overall Standings Table</h4>
                <small class="text-light opacity-75">
                    Completed Matches: <strong class="text-cyan"><?php echo count($completedMatches); ?></strong> | Kill Multiplier: <strong class="text-flame"><?php echo $tourney['kill_points_rate'] ?? 1; ?> Pt/Kill</strong>
                </small>
            </div>
            <div class="small text-light opacity-75">
                <i class="fa-solid fa-circle-info text-cyan me-1"></i> Sorted by Total Points &bull; WWCD Wins &bull; Placement Pts
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-esports align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 70px;">Rank</th>
                        <th>Squad Name</th>
                        <th>Captain</th>
                        <th class="text-center">Played</th>
                        <th class="text-center">WWCD (Wins)</th>
                        <th class="text-center">Kills</th>
                        <th class="text-end">Placement Pts</th>
                        <th class="text-end">Kill Pts</th>
                        <th class="text-end">Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaderboard)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No teams or match scores recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($leaderboard as $row): 
                            $isTop1 = ($rank === 1 && $row['total_points'] > 0);
                            $isTop3 = ($rank <= 3 && $row['total_points'] > 0);
                        ?>
                            <tr class="<?php echo $isTop1 ? 'table-warning bg-opacity-10' : ''; ?>">
                                <td>
                                    <?php if ($rank === 1 && $row['total_points'] > 0): ?>
                                        <span class="badge bg-warning text-dark fw-bold fs-6"><i class="fa-solid fa-trophy me-1"></i>#1</span>
                                    <?php elseif ($rank === 2 && $row['total_points'] > 0): ?>
                                        <span class="badge bg-secondary text-white fw-bold fs-6">#2</span>
                                    <?php elseif ($rank === 3 && $row['total_points'] > 0): ?>
                                        <span class="badge bg-dark border border-warning text-warning fw-bold fs-6">#3</span>
                                    <?php else: ?>
                                        <span class="text-muted fw-bold ms-2">#<?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-white fs-6"><?php echo htmlspecialchars($row['team_name']); ?></strong>
                                    <span class="badge bg-dark border border-secondary text-cyan small ms-1">[<?php echo htmlspecialchars($row['team_tag']); ?>]</span>
                                </td>
                                <td class="small text-light opacity-75"><?php echo htmlspecialchars($row['captain_name']); ?></td>
                                <td class="text-center text-light"><?php echo $row['matches_played']; ?></td>
                                <td class="text-center fw-bold text-gold">
                                    <?php if ($row['wwcd_count'] > 0): ?>
                                        <i class="fa-solid fa-trophy text-gold me-1"></i><?php echo $row['wwcd_count']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold text-flame"><?php echo $row['total_kills']; ?></td>
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

    <!-- Tie-Breaker Rule Explanation for College Presentation -->
    <div class="esports-card p-4">
        <h5 class="text-white brand-font mb-2"><i class="fa-solid fa-scale-balanced text-cyan me-2"></i>Official Tie-Breaker Algorithm (Explained for College Project Viva)</h5>
        <p class="text-light opacity-75 small mb-3">
            In competitive BGMI esports tournaments, when two or more squads finish with identical Total Points, the system executes an automated 4-tier tie-breaking sequence:
        </p>
        <div class="row g-3 small">
            <div class="col-md-3">
                <div class="bg-dark p-3 rounded border border-secondary border-opacity-25 h-100">
                    <span class="text-cyan fw-bold d-block mb-1">Tier 1: Total Points</span>
                    <span class="text-light opacity-75">Highest aggregate sum of Placement Points and Kill Points.</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark p-3 rounded border border-secondary border-opacity-25 h-100">
                    <span class="text-gold fw-bold d-block mb-1">Tier 2: WWCD Wins</span>
                    <span class="text-light opacity-75">Team with greater Chicken Dinners (1st place match finishes) wins the tie.</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark p-3 rounded border border-secondary border-opacity-25 h-100">
                    <span class="text-cyan fw-bold d-block mb-1">Tier 3: Placement Points</span>
                    <span class="text-light opacity-75">Squad that accrued higher cumulative placement points breaks the tie.</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark p-3 rounded border border-secondary border-opacity-25 h-100">
                    <span class="text-flame fw-bold d-block mb-1">Tier 4: Total Frags (Kills)</span>
                    <span class="text-light opacity-75">Total cumulative squad kills serve as the final decisive decider.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
