<?php
// =======================================================
// File: player/certificates.php
// Purpose: Player Certificate Hub & Downloads
// =======================================================

$pageTitle = "My Certificates - Esports Tournament Hub";
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

$certificates = [];
if ($myTeam) {
    $stmt = $pdo->prepare("
        SELECT c.*, t.tournament_name, t.game, tm.team_name, tm.team_tag
        FROM certificates c
        JOIN tournaments t ON c.tournament_id = t.id
        JOIN teams tm ON c.team_id = tm.id
        WHERE c.team_id = ?
        ORDER BY c.issue_date DESC
    ");
    $stmt->execute([$myTeam['id']]);
    $certificates = $stmt->fetchAll();
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">My Certificates</li>
        </ol>
    </nav>

    <!-- Header Banner -->
    <div class="esports-card mb-4 border-gold">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-dark text-gold border border-warning border-opacity-25 mb-2">
                    <i class="fa-solid fa-award me-1"></i> Verified Credentials
                </span>
                <h2 class="text-white mb-1">Squad E-Certificates & <span class="text-gold">Trophies</span></h2>
                <p class="text-light opacity-75 small mb-0">
                    Official digital credentials for championship victories and verified tournament participation. Download or print anytime.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <?php if ($myTeam): ?>
                    <span class="badge bg-dark border border-secondary text-cyan p-2">
                        <i class="fa-solid fa-shield-halved me-1"></i> <?php echo htmlspecialchars($myTeam['team_name']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$myTeam): ?>
        <div class="esports-card text-center py-5">
            <i class="fa-solid fa-users text-muted fs-1 mb-3 opacity-50"></i>
            <h4 class="text-white mb-2">No Squad Found</h4>
            <p class="text-light opacity-75 small mb-3">You must create a squad and participate in tournaments to earn certificates.</p>
            <a href="create_team.php" class="btn btn-neon-primary btn-sm">Create Squad</a>
        </div>
    <?php elseif (empty($certificates)): ?>
        <div class="esports-card text-center py-5">
            <i class="fa-solid fa-certificate text-muted fs-1 mb-3 opacity-50"></i>
            <h4 class="text-white mb-2">No Certificates Issued Yet</h4>
            <p class="text-light opacity-75 small mb-3">Certificates will appear here once the tournament host finalizes the leaderboard and issues awards.</p>
            <a href="../tournaments/index.php" class="btn btn-neon-outline btn-sm">Browse Active Tournaments</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($certificates as $cert): ?>
                <div class="col-md-6">
                    <div class="esports-card h-100 d-flex flex-column justify-content-between <?php echo ($cert['certificate_type'] === 'winner') ? 'border-warning' : 'border-cyan'; ?>">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <?php if ($cert['certificate_type'] === 'winner'): ?>
                                    <span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-crown me-1"></i>CHAMPIONSHIP WINNER</span>
                                <?php elseif ($cert['certificate_type'] === 'runner_up'): ?>
                                    <span class="badge bg-secondary text-white fw-bold">RUNNER UP</span>
                                <?php else: ?>
                                    <span class="badge bg-dark border border-info text-cyan">OFFICIAL PARTICIPATION</span>
                                <?php endif; ?>

                                <small class="text-light opacity-75">Issued: <?php echo date('M d, Y', strtotime($cert['issue_date'])); ?></small>
                            </div>

                            <h4 class="text-white mb-1"><?php echo htmlspecialchars($cert['tournament_name']); ?></h4>
                            <p class="text-light opacity-75 small mb-3">
                                Awarded to squad <strong><?php echo htmlspecialchars($cert['team_name']); ?> [<?php echo htmlspecialchars($cert['team_tag']); ?>]</strong> for verified competition in BGMI esports.
                            </p>

                            <div class="bg-dark p-2 rounded small text-light opacity-75 mb-3 border border-secondary border-opacity-25">
                                Verification ID: <code class="text-warning"><?php echo htmlspecialchars($cert['certificate_number']); ?></code>
                            </div>
                        </div>

                        <div>
                            <a href="../view_certificate.php?num=<?php echo urlencode($cert['certificate_number']); ?>" target="_blank" class="btn btn-neon-primary w-100 btn-sm">
                                <i class="fa-solid fa-eye me-1"></i> View & Print E-Certificate
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
