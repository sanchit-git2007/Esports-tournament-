<?php
$pageTitle = "Admin Dashboard - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$adminName = $_SESSION['name'];

// 1. Fetch Key System Statistics
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'teams' => $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn(),
    'tournaments' => $pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn(),
    'matches' => $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn(),
];

// 2. Fetch Pending Tournament Registrations for immediate review
$pendingStmt = $pdo->query("
    SELECT tr.id AS reg_id, tr.registered_at, t.tournament_name, t.tournament_type, 
           tm.team_name, tm.team_tag, u.name AS captain_name
    FROM tournament_registrations tr
    JOIN tournaments t ON tr.tournament_id = t.id
    JOIN teams tm ON tr.team_id = tm.id
    JOIN users u ON tm.captain_id = u.id
    WHERE tr.status = 'pending'
    ORDER BY tr.registered_at DESC
    LIMIT 5
");
$pendingRegistrations = $pendingStmt->fetchAll();

// 3. Fetch Recent Tournaments
$tourneyStmt = $pdo->query("
    SELECT t.*, COUNT(tr.id) AS registered_teams_count
    FROM tournaments t
    LEFT JOIN tournament_registrations tr ON t.id = tr.tournament_id AND tr.status = 'approved'
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT 4
");
$recentTournaments = $tourneyStmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Admin Header Banner -->
    <div class="esports-card mb-4 border-flame">
        <div class="row align-items-center">
            <div class="col-md-7">
                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 mb-2">
                    <i class="fa-solid fa-shield-halved me-1"></i> Administrator Control Center
                </span>
                <h2 class="text-white mb-1">Welcome, <span class="text-flame"><?php echo htmlspecialchars($adminName); ?></span></h2>
                <p class="text-light mb-0 small opacity-75">
                    Oversee tournaments, approve squads, configure BGMI points, manage custom room credentials, and enter scores.
                </p>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                <a href="create_tournament.php" class="btn btn-flame btn-sm">
                    <i class="fa-solid fa-plus-circle me-1"></i> New Tournament
                </a>
                <a href="results.php" class="btn btn-neon-primary btn-sm">
                    <i class="fa-solid fa-calculator me-1"></i> Enter Results
                </a>
            </div>
        </div>
    </div>

    <!-- 4 High-Level Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="esports-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-light small text-uppercase fw-bold opacity-75">Total Users</span>
                        <h3 class="text-white mb-0 mt-1"><?php echo $stats['users']; ?></h3>
                    </div>
                    <div class="feature-icon-box mb-0 text-cyan">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="mt-2 small text-light opacity-75">Registered players & admins</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="esports-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-light small text-uppercase fw-bold opacity-75">Total Squads</span>
                        <h3 class="text-cyan mb-0 mt-1"><?php echo $stats['teams']; ?></h3>
                    </div>
                    <div class="feature-icon-box mb-0 text-cyan">
                        <i class="fa-solid fa-people-group"></i>
                    </div>
                </div>
                <div class="mt-2 small text-light opacity-75">Created teams</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="esports-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-light small text-uppercase fw-bold opacity-75">Tournaments</span>
                        <h3 class="text-gold mb-0 mt-1"><?php echo $stats['tournaments']; ?></h3>
                    </div>
                    <div class="feature-icon-box mb-0 text-gold">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                </div>
                <div class="mt-2 small text-light opacity-75">Public & Private events</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="esports-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-light small text-uppercase fw-bold opacity-75">Matches</span>
                        <h3 class="text-flame mb-0 mt-1"><?php echo $stats['matches']; ?></h3>
                    </div>
                    <div class="feature-icon-box mb-0 text-flame">
                        <i class="fa-solid fa-crosshairs"></i>
                    </div>
                </div>
                <div class="mt-2 small text-light opacity-75">Scheduled custom matches</div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Action Bar -->
    <div class="esports-card mb-4 p-3 bg-dark">
        <div class="row g-2 text-center">
            <div class="col-6 col-md-2">
                <a href="tournaments.php" class="btn btn-outline-secondary w-100 btn-sm text-truncate">
                    <i class="fa-solid fa-trophy text-cyan me-1"></i> Tournaments
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="registrations.php" class="btn btn-outline-secondary w-100 btn-sm text-truncate">
                    <i class="fa-solid fa-user-check text-cyan me-1"></i> Approvals
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="matches.php" class="btn btn-outline-secondary w-100 btn-sm text-truncate">
                    <i class="fa-solid fa-door-open text-cyan me-1"></i> Room IDs
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="results.php" class="btn btn-outline-secondary w-100 btn-sm text-truncate">
                    <i class="fa-solid fa-calculator text-cyan me-1"></i> Scoring
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="teams.php" class="btn btn-outline-secondary w-100 btn-sm text-truncate">
                    <i class="fa-solid fa-shield-cat text-cyan me-1"></i> Squads
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="certificates.php" class="btn btn-outline-secondary w-100 btn-sm text-truncate">
                    <i class="fa-solid fa-award text-cyan me-1"></i> Certificates
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Pending Approvals Section -->
        <div class="col-lg-6">
            <div class="esports-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white mb-0">
                        <i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>Pending Team Approvals
                    </h5>
                    <span class="badge bg-warning text-dark fw-bold"><?php echo count($pendingRegistrations); ?> Pending</span>
                </div>

                <?php if (empty($pendingRegistrations)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-check-double text-success fs-3 mb-2 d-block"></i>
                        <p class="small text-light mb-0">All tournament registrations are reviewed and up to date!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-esports table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Team</th>
                                    <th>Tournament</th>
                                    <th>Captain</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRegistrations as $p): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-white"><?php echo htmlspecialchars($p['team_name']); ?></strong>
                                            <span class="badge bg-dark border border-secondary text-cyan small">[<?php echo htmlspecialchars($p['team_tag']); ?>]</span>
                                        </td>
                                        <td class="small text-light opacity-75"><?php echo htmlspecialchars($p['tournament_name']); ?></td>
                                        <td class="small text-white"><?php echo htmlspecialchars($p['captain_name']); ?></td>
                                        <td>
                                            <a href="registrations.php?action=approve&id=<?php echo $p['reg_id']; ?>" class="btn btn-sm btn-success py-0 px-2" title="Approve">
                                                <i class="fa-solid fa-check"></i>
                                            </a>
                                            <a href="registrations.php?action=reject&id=<?php echo $p['reg_id']; ?>" class="btn btn-sm btn-danger py-0 px-2" title="Reject">
                                                <i class="fa-solid fa-xmark"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Tournaments Overview -->
        <div class="col-lg-6">
            <div class="esports-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white mb-0">
                        <i class="fa-solid fa-trophy text-cyan me-2"></i>Recent Tournaments
                    </h5>
                    <a href="tournaments.php" class="btn btn-sm btn-outline-info">Manage All</a>
                </div>

                <div class="list-group list-group-flush bg-transparent">
                    <?php foreach ($recentTournaments as $t): ?>
                        <div class="list-group-item bg-dark border-secondary border-opacity-25 rounded mb-2 text-white p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 text-white fw-bold"><?php echo htmlspecialchars($t['tournament_name']); ?></h6>
                                <span class="badge <?php echo ($t['status'] === 'registration_open') ? 'bg-success' : 'bg-secondary'; ?> text-uppercase small">
                                    <?php echo str_replace('_', ' ', $t['status']); ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between text-light opacity-75 small mt-2">
                                <span><i class="fa-solid fa-calendar me-1 text-cyan"></i><?php echo htmlspecialchars($t['tournament_date']); ?></span>
                                <span><i class="fa-solid fa-users me-1 text-cyan"></i><?php echo $t['registered_teams_count']; ?> / <?php echo $t['max_teams']; ?> Teams</span>
                                <a href="matches.php?tournament_id=<?php echo $t['id']; ?>" class="text-cyan text-decoration-none fw-bold">
                                    Matches <i class="fa-solid fa-arrow-right small"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
