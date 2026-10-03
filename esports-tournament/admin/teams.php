<?php
// =======================================================
// File: admin/teams.php
// Purpose: Manage Squads, View Rosters & Captain Info
// =======================================================

$pageTitle = "Manage Squads - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$msg = "";
$error = "";

// Fetch All Squads with Captain Info and Member Counts
$teams = $pdo->query("
    SELECT tm.*, u.name AS captain_name, u.email AS captain_email, u.phone AS captain_phone,
           COUNT(DISTINCT mem.id) AS member_count,
           COUNT(DISTINCT tr.id) AS tourneys_joined
    FROM teams tm
    JOIN users u ON tm.captain_id = u.id
    LEFT JOIN team_members mem ON tm.id = mem.team_id
    LEFT JOIN tournament_registrations tr ON tm.id = tr.team_id
    GROUP BY tm.id
    ORDER BY tm.created_at DESC
")->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">Manage Squads</li>
        </ol>
    </nav>

    <div class="esports-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="text-white mb-0"><i class="fa-solid fa-people-group text-cyan me-2"></i>Registered Esports Squads</h4>
                <p class="text-light opacity-75 small mb-0">Rosters, team tags, and captain contact information.</p>
            </div>
            <span class="badge bg-dark border border-secondary text-cyan">
                <?php echo count($teams); ?> Squads
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-esports align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Squad Name</th>
                        <th>Tag</th>
                        <th>Captain</th>
                        <th>Contact</th>
                        <th>Roster Size</th>
                        <th>Tournaments Joined</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teams)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No squads registered yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($teams as $t): ?>
                            <tr>
                                <td class="text-muted"><?php echo $i++; ?></td>
                                <td>
                                    <strong class="text-white"><?php echo htmlspecialchars($t['team_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-cyan">[<?php echo htmlspecialchars($t['team_tag']); ?>]</span>
                                </td>
                                <td>
                                    <strong class="text-white"><?php echo htmlspecialchars($t['captain_name']); ?></strong>
                                </td>
                                <td class="small text-light opacity-75">
                                    <div><i class="fa-solid fa-envelope me-1 text-cyan"></i><?php echo htmlspecialchars($t['captain_email']); ?></div>
                                    <?php if (!empty($t['captain_phone'])): ?>
                                        <div><i class="fa-solid fa-phone me-1 text-success"></i><?php echo htmlspecialchars($t['captain_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-white"><?php echo $t['member_count']; ?> Players</span>
                                </td>
                                <td class="text-center text-cyan fw-bold"><?php echo $t['tourneys_joined']; ?></td>
                                <td class="small text-light opacity-75"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
