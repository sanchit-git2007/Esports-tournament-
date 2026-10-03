<?php
// =======================================================
// File: admin/results.php
// Purpose: Match Result Entry & Automated BGMI Scoring Engine
// =======================================================

$pageTitle = "Enter Match Results - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$msg = "";
$error = "";

// 1. Fetch Tournaments for Selector
$tournaments = $pdo->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_date DESC")->fetchAll();
$validTourneyIds = array_column($tournaments, 'id');

$selectedTournamentId = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : ($tournaments[0]['id'] ?? 0);
if (!in_array($selectedTournamentId, $validTourneyIds) && !empty($validTourneyIds)) {
    $selectedTournamentId = $validTourneyIds[0];
}

// 2. Fetch Matches for Selected Tournament
$matches = [];
if ($selectedTournamentId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE tournament_id = ? ORDER BY match_number ASC");
    $stmt->execute([$selectedTournamentId]);
    $matches = $stmt->fetchAll();
}

$validMatchIds = array_column($matches, 'id');
$selectedMatchId = (isset($_GET['match_id']) && in_array((int)$_GET['match_id'], $validMatchIds)) 
    ? (int)$_GET['match_id'] 
    : ($matches[0]['id'] ?? 0);

// 3. Fetch Tournament Details & Scoring Rules
$tourney = null;
$scoringMap = [];
if ($selectedTournamentId > 0) {
    $tStmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
    $tStmt->execute([$selectedTournamentId]);
    $tourney = $tStmt->fetch();

    $rulesStmt = $pdo->prepare("SELECT placement, points FROM tournament_scoring_rules WHERE tournament_id = ?");
    $rulesStmt->execute([$selectedTournamentId]);
    $rules = $rulesStmt->fetchAll();
    foreach ($rules as $r) {
        $scoringMap[(int)$r['placement']] = (int)$r['points'];
    }
}

// 4. Fetch Approved Participating Teams
$approvedTeams = [];
if ($selectedTournamentId > 0) {
    $teamStmt = $pdo->prepare("
        SELECT tm.id, tm.team_name, tm.team_tag, u.name AS captain_name
        FROM tournament_registrations tr
        JOIN teams tm ON tr.team_id = tm.id
        JOIN users u ON tm.captain_id = u.id
        WHERE tr.tournament_id = ? AND tr.status = 'approved'
        ORDER BY tm.team_name ASC
    ");
    $teamStmt->execute([$selectedTournamentId]);
    $approvedTeams = $teamStmt->fetchAll();
}

// 5. Fetch Existing Results for this match (if already entered)
$existingResults = [];
if ($selectedMatchId > 0) {
    $resStmt = $pdo->prepare("SELECT * FROM match_results WHERE match_id = ?");
    $resStmt->execute([$selectedMatchId]);
    $rawRes = $resStmt->fetchAll();
    foreach ($rawRes as $row) {
        $existingResults[$row['team_id']] = $row;
    }
}

// 6. Handle POST Submission: Calculate Points & Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_results') {
    $postMatchId = (int)($_POST['match_id'] ?? 0);
    $postTourneyId = (int)($_POST['tournament_id'] ?? 0);
    $teamData = $_POST['teams'] ?? [];

    if ($postMatchId <= 0 || $postTourneyId <= 0) {
        $error = "Invalid match or championship selected.";
    } elseif (empty($teamData)) {
        $error = "No squad results submitted.";
    } else {
        try {
            // Verify Match Exists and Belongs to Tournament
            $matchVerify = $pdo->prepare("SELECT id FROM matches WHERE id = ? AND tournament_id = ?");
            $matchVerify->execute([$postMatchId, $postTourneyId]);
            if (!$matchVerify->fetch()) {
                throw new Exception("The selected match (ID: {$postMatchId}) does not exist in this championship. Please refresh and select a valid match round.");
            }

            // Verify Tournament & Scoring Rules
            $tCheck = $pdo->prepare("SELECT kill_points_rate FROM tournaments WHERE id = ?");
            $tCheck->execute([$postTourneyId]);
            $tObj = $tCheck->fetch();
            $killRate = $tObj ? (int)$tObj['kill_points_rate'] : 1;

            $rulesCheck = $pdo->prepare("SELECT placement, points FROM tournament_scoring_rules WHERE tournament_id = ?");
            $rulesCheck->execute([$postTourneyId]);
            $currentRules = $rulesCheck->fetchAll();
            $currentScoringMap = [];
            foreach ($currentRules as $cr) {
                $currentScoringMap[(int)$cr['placement']] = (int)$cr['points'];
            }

            $pdo->beginTransaction();

            // Prepare Insert/Update Statement
            $saveStmt = $pdo->prepare("
                INSERT INTO match_results (match_id, team_id, placement, kills, placement_points, kill_points, total_points, entered_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    placement = VALUES(placement),
                    kills = VALUES(kills),
                    placement_points = VALUES(placement_points),
                    kill_points = VALUES(kill_points),
                    total_points = VALUES(total_points),
                    entered_at = NOW()
            ");

            foreach ($teamData as $teamId => $data) {
                $teamId = (int)$teamId;
                $placement = (int)($data['placement'] ?? 16);
                $kills = (int)($data['kills'] ?? 0);

                // AUTOMATED SCORING ENGINE:
                // 1. Placement points from rule map (default to 0 if not mapped)
                $placementPoints = $currentScoringMap[$placement] ?? 0;
                
                // 2. Kill points = kills * multiplier
                $killPoints = $kills * $killRate;

                // 3. Total Points = placement points + kill points
                $totalPoints = $placementPoints + $killPoints;

                $saveStmt->execute([$postMatchId, $teamId, $placement, $kills, $placementPoints, $killPoints, $totalPoints]);
            }

            // Automatically set match status to 'completed'
            $pdo->prepare("UPDATE matches SET status = 'completed' WHERE id = ?")->execute([$postMatchId]);

            $pdo->commit();

            // Redirect to leaderboard with success notification
            header("Location: leaderboard.php?tournament_id={$postTourneyId}&scored=1");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Database Scoring Error: " . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="matches.php?tournament_id=<?php echo $selectedTournamentId; ?>" class="text-cyan text-decoration-none">Matches</a></li>
            <li class="breadcrumb-item text-white active">Enter Match Results</li>
        </ol>
    </nav>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-white py-2 small mb-4">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Selection Card -->
    <div class="esports-card p-4 mb-4 border-cyan">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label-custom"><i class="fa-solid fa-trophy text-cyan me-1"></i> 1. Select Championship</label>
                <select name="tournament_id" class="form-select form-select-custom" onchange="window.location.href='results.php?tournament_id=' + this.value">
                    <?php foreach ($tournaments as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo ($selectedTournamentId === $t['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['tournament_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label-custom"><i class="fa-solid fa-crosshairs text-flame me-1"></i> 2. Select Match Round</label>
                <select name="match_id" class="form-select form-select-custom" onchange="window.location.href='results.php?tournament_id=<?php echo $selectedTournamentId; ?>&match_id=' + this.value">
                    <?php if (empty($matches)): ?>
                        <option value="">No matches scheduled</option>
                    <?php else: ?>
                        <?php foreach ($matches as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo ($selectedMatchId === $m['id']) ? 'selected' : ''; ?>>
                                Round <?php echo $m['match_number']; ?>: <?php echo htmlspecialchars($m['match_name']); ?> (<?php echo $m['map']; ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
    </div>

    <?php if (empty($matches)): ?>
        <div class="esports-card text-center py-5">
            <i class="fa-solid fa-calendar-plus text-muted fs-1 mb-3 opacity-50"></i>
            <h4 class="text-white mb-2">No Matches Scheduled</h4>
            <p class="text-light opacity-75 small mb-3">Please schedule match rounds for this tournament first before entering scores.</p>
            <a href="matches.php?tournament_id=<?php echo $selectedTournamentId; ?>" class="btn btn-flame btn-sm">Schedule Match Round</a>
        </div>
    <?php elseif (empty($approvedTeams)): ?>
        <div class="esports-card text-center py-5">
            <i class="fa-solid fa-users text-muted fs-1 mb-3 opacity-50"></i>
            <h4 class="text-white mb-2">No Approved Squads</h4>
            <p class="text-light opacity-75 small mb-3">There are no approved teams registered for this championship yet.</p>
            <a href="registrations.php?tournament_id=<?php echo $selectedTournamentId; ?>" class="btn btn-neon-primary btn-sm">Review Registrations</a>
        </div>
    <?php else: ?>
        <!-- Automated Scoring Input Matrix -->
        <div class="esports-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h4 class="text-white mb-0"><i class="fa-solid fa-calculator text-cyan me-2"></i>Automated Scoring Matrix</h4>
                    <small class="text-light opacity-75">
                        Enter each squad's <strong>Placement</strong> and <strong>Kills</strong>. Total points & standings will calculate automatically.
                    </small>
                </div>
                <span class="badge bg-dark border border-secondary text-cyan">
                    Rate: <?php echo $tourney['kill_points_rate'] ?? 1; ?> Pt / Frag
                </span>
            </div>

            <form action="results.php?tournament_id=<?php echo $selectedTournamentId; ?>&match_id=<?php echo $selectedMatchId; ?>" method="POST">
                <input type="hidden" name="action" value="save_results">
                <input type="hidden" name="tournament_id" value="<?php echo $selectedTournamentId; ?>">
                <input type="hidden" name="match_id" value="<?php echo $selectedMatchId; ?>">

                <div class="table-responsive">
                    <table class="table table-esports align-middle mb-4">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Squad Name</th>
                                <th>Captain</th>
                                <th style="width: 170px;">Placement (Rank)</th>
                                <th style="width: 140px;">Kills (Frags)</th>
                                <th class="text-end">Placement Pts</th>
                                <th class="text-end">Kill Pts</th>
                                <th class="text-end">Total Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($approvedTeams as $team): 
                                $tId = $team['id'];
                                $prevRes = $existingResults[$tId] ?? null;
                                $curPlacement = $prevRes ? $prevRes['placement'] : $i;
                                $curKills = $prevRes ? $prevRes['kills'] : 0;
                                $prevPlacePts = $prevRes ? $prevRes['placement_points'] : ($scoringMap[$curPlacement] ?? 0);
                                $prevKillPts = $prevRes ? $prevRes['kill_points'] : ($curKills * ($tourney['kill_points_rate'] ?? 1));
                                $prevTotal = $prevRes ? $prevRes['total_points'] : ($prevPlacePts + $prevKillPts);
                            ?>
                                <tr>
                                    <td class="text-muted"><?php echo $i++; ?></td>
                                    <td>
                                        <strong class="text-white"><?php echo htmlspecialchars($team['team_name']); ?></strong>
                                        <span class="badge bg-dark border border-secondary text-cyan small ms-1">[<?php echo htmlspecialchars($team['team_tag']); ?>]</span>
                                    </td>
                                    <td class="small text-light opacity-75"><?php echo htmlspecialchars($team['captain_name']); ?></td>
                                    <td>
                                        <select name="teams[<?php echo $tId; ?>][placement]" class="form-select form-select-custom form-select-sm fw-bold placement-select" data-team="<?php echo $tId; ?>" onchange="calculateRow(<?php echo $tId; ?>)">
                                            <?php for ($p = 1; $p <= 16; $p++): ?>
                                                <option value="<?php echo $p; ?>" <?php echo ($curPlacement == $p) ? 'selected' : ''; ?>>
                                                    <?php echo $p; ?><?php echo ($p==1)?'st (WWCD)':(($p==2)?'nd':(($p==3)?'rd':'th')); ?> Place (+<?php echo $scoringMap[$p] ?? 0; ?>)
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="teams[<?php echo $tId; ?>][kills]" class="form-control form-control-custom form-control-sm text-center fw-bold kills-input" id="kills_<?php echo $tId; ?>" value="<?php echo $curKills; ?>" min="0" max="60" oninput="calculateRow(<?php echo $tId; ?>)" required>
                                    </td>
                                    <td class="text-end fw-bold text-cyan" id="place_pts_<?php echo $tId; ?>">
                                        +<?php echo $prevPlacePts; ?>
                                    </td>
                                    <td class="text-end fw-bold text-flame" id="kill_pts_<?php echo $tId; ?>">
                                        +<?php echo $prevKillPts; ?>
                                    </td>
                                    <td class="text-end fw-bold text-gold fs-6" id="total_pts_<?php echo $tId; ?>">
                                        <?php echo $prevTotal; ?> Pts
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-25 flex-wrap gap-2">
                    <div class="small text-light opacity-75">
                        <i class="fa-solid fa-circle-info text-cyan me-1"></i> Submitting scores will instantly recalculate the tournament championship leaderboard.
                    </div>
                    <button type="submit" class="btn btn-neon-primary px-4 py-2">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Results & Generate Leaderboard
                    </button>
                </div>
            </form>
        </div>

        <script>
        // Live client-side preview calculator
        const scoringMap = <?php echo json_encode($scoringMap); ?>;
        const killRate = <?php echo (int)($tourney['kill_points_rate'] ?? 1); ?>;

        function calculateRow(teamId) {
            const placementSelect = document.querySelector(`select[name="teams[${teamId}][placement]"]`);
            const killsInput = document.getElementById(`kills_${teamId}`);
            
            const placement = parseInt(placementSelect.value) || 16;
            const kills = parseInt(killsInput.value) || 0;

            const placePts = scoringMap[placement] || 0;
            const killPts = kills * killRate;
            const totalPts = placePts + killPts;

            document.getElementById(`place_pts_${teamId}`).innerText = `+${placePts}`;
            document.getElementById(`kill_pts_${teamId}`).innerText = `+${killPts}`;
            document.getElementById(`total_pts_${teamId}`).innerText = `${totalPts} Pts`;
        }
        </script>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
