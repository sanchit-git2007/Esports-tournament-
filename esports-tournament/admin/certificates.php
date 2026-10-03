<?php
// =======================================================
// File: admin/certificates.php
// Purpose: Automated E-Certificate Generation & Distribution
// =======================================================

$pageTitle = "Certificate Management - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$msg = "";
$error = "";

// Fetch Tournaments
$tournaments = $pdo->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_date DESC")->fetchAll();
$selectedTournamentId = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : ($tournaments[0]['id'] ?? 0);

// 1. Fetch Tournament Standings (to know #1 Winner and #2 Runner-Up)
$leaderboard = [];
if ($selectedTournamentId > 0) {
    $lbQuery = $pdo->prepare("
        SELECT 
            tm.id AS team_id,
            tm.team_name,
            tm.team_tag,
            tm.captain_id,
            u.name AS captain_name,
            COALESCE(SUM(mr.total_points), 0) AS total_points
        FROM tournament_registrations tr
        JOIN teams tm ON tr.team_id = tm.id
        JOIN users u ON tm.captain_id = u.id
        LEFT JOIN matches m ON m.tournament_id = tr.tournament_id AND m.status = 'completed'
        LEFT JOIN match_results mr ON mr.match_id = m.id AND mr.team_id = tm.id
        WHERE tr.tournament_id = ? AND tr.status = 'approved'
        GROUP BY tm.id
        ORDER BY total_points DESC
    ");
    $lbQuery->execute([$selectedTournamentId]);
    $leaderboard = $lbQuery->fetchAll();
}

// 2. Handle Action: Single Certificate Issuance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'issue_single') {
    $tId = (int)$_POST['tournament_id'];
    $teamId = (int)$_POST['team_id'];
    $userId = (int)$_POST['user_id'];
    $type = $_POST['certificate_type']; // 'winner', 'runner_up', 'participation'

    // Generate unique Certificate Number
    $certNumber = "CERT-" . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $tournaments[0]['tournament_name'] ?? 'BGMI'), 0, 4)) . "-" . date('Y') . "-" . str_pad($teamId, 3, '0', STR_PAD_LEFT) . "-" . strtoupper(substr($type, 0, 4));

    try {
        $insert = $pdo->prepare("
            INSERT INTO certificates (tournament_id, user_id, team_id, certificate_type, certificate_number, issue_date)
            VALUES (?, ?, ?, ?, ?, CURDATE())
            ON DUPLICATE KEY UPDATE certificate_type = VALUES(certificate_type), issue_date = CURDATE()
        ");
        $insert->execute([$tId, $userId, $teamId, $type, $certNumber]);
        $msg = "Certificate issued successfully! (ID: {$certNumber})";
    } catch (PDOException $e) {
        $error = "Error issuing certificate: " . $e->getMessage();
    }
}

// 3. Handle Action: Bulk Issue Participation Certificates to all approved teams
if (isset($_POST['action']) && $_POST['action'] === 'bulk_participation') {
    $tId = (int)$_POST['tournament_id'];
    $issuedCount = 0;

    foreach ($leaderboard as $row) {
        $teamId = $row['team_id'];
        $captainUserId = $row['captain_id'];
        $certNum = "CERT-PART-" . $tId . "-" . $teamId . "-" . rand(1000, 9999);

        // Check if certificate already exists
        $check = $pdo->prepare("SELECT id FROM certificates WHERE tournament_id = ? AND team_id = ?");
        $check->execute([$tId, $teamId]);
        if ($check->rowCount() === 0) {
            $ins = $pdo->prepare("
                INSERT INTO certificates (tournament_id, user_id, team_id, certificate_type, certificate_number, issue_date)
                VALUES (?, ?, ?, 'participation', ?, CURDATE())
            ");
            $ins->execute([$tId, $captainUserId, $teamId, $certNum]);
            $issuedCount++;
        }
    }
    $msg = "Successfully generated {$issuedCount} Participation Certificates!";
}

// 4. Fetch All Issued Certificates for this tournament
$issuedCerts = [];
if ($selectedTournamentId > 0) {
    $stmt = $pdo->prepare("
        SELECT c.*, tm.team_name, tm.team_tag, u.name AS captain_name, t.tournament_name
        FROM certificates c
        JOIN teams tm ON c.team_id = tm.id
        JOIN users u ON tm.captain_id = u.id
        JOIN tournaments t ON c.tournament_id = t.id
        WHERE c.tournament_id = ?
        ORDER BY (c.certificate_type = 'winner') DESC, (c.certificate_type = 'runner_up') DESC, c.id ASC
    ");
    $stmt->execute([$selectedTournamentId]);
    $issuedCerts = $stmt->fetchAll();
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">Certificate Management</li>
        </ol>
    </nav>

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

    <!-- Tournament Selector Card -->
    <div class="esports-card p-4 mb-4 border-flame">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <h3 class="text-white mb-1"><i class="fa-solid fa-award text-gold me-2"></i>Automated E-Certificates</h3>
                <p class="text-light opacity-75 small mb-0">Generate digital verification certificates for Champions, Runners-Up, and Participating squads.</p>
            </div>
            <div class="col-lg-5">
                <form action="certificates.php" method="GET">
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

    <!-- Quick Issue Actions -->
    <?php if (!empty($leaderboard)): ?>
        <div class="row g-3 mb-4">
            <!-- 1st Place Winner Card -->
            <?php if (isset($leaderboard[0])): $win = $leaderboard[0]; ?>
                <div class="col-md-4">
                    <div class="esports-card p-3 border-warning h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge bg-warning text-dark mb-2 fw-bold"><i class="fa-solid fa-crown me-1"></i>1st Place Champion</span>
                            <h5 class="text-white mb-1"><?php echo htmlspecialchars($win['team_name']); ?></h5>
                            <div class="small text-light opacity-75 mb-3">Captain: <?php echo htmlspecialchars($win['captain_name']); ?> (<?php echo $win['total_points']; ?> Pts)</div>
                        </div>
                        <form action="certificates.php?tournament_id=<?php echo $selectedTournamentId; ?>" method="POST">
                            <input type="hidden" name="action" value="issue_single">
                            <input type="hidden" name="tournament_id" value="<?php echo $selectedTournamentId; ?>">
                            <input type="hidden" name="team_id" value="<?php echo $win['team_id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $win['captain_id']; ?>">
                            <input type="hidden" name="certificate_type" value="winner">
                            <button type="submit" class="btn btn-flame w-100 btn-sm">
                                <i class="fa-solid fa-trophy me-1"></i> Issue Winner Certificate
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 2nd Place Runner Up Card -->
            <?php if (isset($leaderboard[1])): $run = $leaderboard[1]; ?>
                <div class="col-md-4">
                    <div class="esports-card p-3 border-secondary h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge bg-secondary text-white mb-2 fw-bold">#2 Runner-Up</span>
                            <h5 class="text-white mb-1"><?php echo htmlspecialchars($run['team_name']); ?></h5>
                            <div class="small text-light opacity-75 mb-3">Captain: <?php echo htmlspecialchars($run['captain_name']); ?> (<?php echo $run['total_points']; ?> Pts)</div>
                        </div>
                        <form action="certificates.php?tournament_id=<?php echo $selectedTournamentId; ?>" method="POST">
                            <input type="hidden" name="action" value="issue_single">
                            <input type="hidden" name="tournament_id" value="<?php echo $selectedTournamentId; ?>">
                            <input type="hidden" name="team_id" value="<?php echo $run['team_id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $run['captain_id']; ?>">
                            <input type="hidden" name="certificate_type" value="runner_up">
                            <button type="submit" class="btn btn-neon-outline w-100 btn-sm">
                                <i class="fa-solid fa-medal me-1"></i> Issue Runner-Up Certificate
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Bulk Participation Card -->
            <div class="col-md-4">
                <div class="esports-card p-3 border-cyan h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="badge bg-dark border border-info text-cyan mb-2 fw-bold">All Approved Squads</span>
                        <h5 class="text-white mb-1">Participation E-Certs</h5>
                        <div class="small text-light opacity-75 mb-3">Issue certificates to all <?php echo count($leaderboard); ?> registered squads.</div>
                    </div>
                    <form action="certificates.php?tournament_id=<?php echo $selectedTournamentId; ?>" method="POST">
                        <input type="hidden" name="action" value="bulk_participation">
                        <input type="hidden" name="tournament_id" value="<?php echo $selectedTournamentId; ?>">
                        <button type="submit" class="btn btn-neon-primary w-100 btn-sm" onclick="return confirm('Generate participation certificates for all registered teams?');">
                            <i class="fa-solid fa-certificate me-1"></i> Bulk Issue Participation E-Certs
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Issued Certificates Table -->
    <div class="esports-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h4 class="text-white mb-0"><i class="fa-solid fa-certificate text-cyan me-2"></i>Issued Certificates</h4>
            <span class="badge bg-dark border border-secondary text-cyan">
                <?php echo count($issuedCerts); ?> Total Issued
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-esports align-middle mb-0">
                <thead>
                    <tr>
                        <th>Certificate #</th>
                        <th>Type</th>
                        <th>Awarded Squad</th>
                        <th>Captain</th>
                        <th>Date of Issue</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($issuedCerts)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No certificates issued yet for this tournament.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($issuedCerts as $cert): ?>
                            <tr>
                                <td>
                                    <code class="text-warning bg-dark px-2 py-1 rounded"><?php echo htmlspecialchars($cert['certificate_number']); ?></code>
                                </td>
                                <td>
                                    <?php if ($cert['certificate_type'] === 'winner'): ?>
                                        <span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-trophy me-1"></i>CHAMPION</span>
                                    <?php elseif ($cert['certificate_type'] === 'runner_up'): ?>
                                        <span class="badge bg-secondary text-white fw-bold">RUNNER UP</span>
                                    <?php else: ?>
                                        <span class="badge bg-dark border border-info text-cyan">PARTICIPATION</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-white"><?php echo htmlspecialchars($cert['team_name']); ?></strong>
                                </td>
                                <td class="small text-light opacity-75"><?php echo htmlspecialchars($cert['captain_name']); ?></td>
                                <td class="small text-light opacity-75"><?php echo date('M d, Y', strtotime($cert['issue_date'])); ?></td>
                                <td class="text-end">
                                    <a href="../view_certificate.php?num=<?php echo urlencode($cert['certificate_number']); ?>" target="_blank" class="btn btn-outline-info btn-sm">
                                        <i class="fa-solid fa-eye me-1"></i> View / Print PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
