<?php
// =======================================================
// File: admin/tournaments.php
// Purpose: List, Filter, Update Status & Manage All Tournaments
// =======================================================

$pageTitle = "Manage Tournaments - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$msg = "";
$error = "";

// 1. Handle Status Change Action
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $tId = (int)$_POST['tournament_id'];
    $newStatus = $_POST['new_status'];
    
    if (in_array($newStatus, ['upcoming', 'registration_open', 'registration_closed', 'ongoing', 'completed', 'cancelled'])) {
        $update = $pdo->prepare("UPDATE tournaments SET status = ? WHERE id = ?");
        $update->execute([$newStatus, $tId]);
        $msg = "Tournament status updated to '" . strtoupper(str_replace('_', ' ', $newStatus)) . "'.";
    }
}

// 2. Handle Delete Tournament Action
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $delStmt = $pdo->prepare("DELETE FROM tournaments WHERE id = ?");
    $delStmt->execute([$delId]);
    $msg = "Tournament deleted successfully.";
}

// 3. Fetch All Tournaments with Registration Counts
$stmt = $pdo->query("
    SELECT t.*, 
           COUNT(CASE WHEN tr.status = 'approved' THEN 1 END) AS approved_count,
           COUNT(CASE WHEN tr.status = 'pending' THEN 1 END) AS pending_count
    FROM tournaments t
    LEFT JOIN tournament_registrations tr ON t.id = tr.tournament_id
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$tournaments = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">Manage Tournaments</li>
        </ol>
    </nav>

    <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success bg-success bg-opacity-25 border-success text-white py-2 small alert-auto-dismiss mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> Championship successfully created and configured with custom scoring rules!
        </div>
    <?php endif; ?>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-success bg-success bg-opacity-25 border-success text-white py-2 small alert-auto-dismiss mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Main Card -->
    <div class="esports-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="text-white mb-0"><i class="fa-solid fa-trophy text-cyan me-2"></i>All Tournaments</h4>
                <p class="text-light opacity-75 small mb-0">Oversee registrations, update event phases, and manage brackets.</p>
            </div>
            <a href="create_tournament.php" class="btn btn-flame btn-sm">
                <i class="fa-solid fa-plus-circle me-1"></i> Add New Championship
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-esports align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Championship Name</th>
                        <th>Type</th>
                        <th>Event Date</th>
                        <th>Squads</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tournaments)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No tournaments found. Create your first one!</td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($tournaments as $t): ?>
                            <tr>
                                <td class="text-muted"><?php echo $i++; ?></td>
                                <td>
                                    <strong class="text-white"><?php echo htmlspecialchars($t['tournament_name']); ?></strong>
                                    <div class="small text-cyan"><?php echo htmlspecialchars($t['game']); ?> (<?php echo $t['kill_points_rate']; ?> Pt/Kill)</div>
                                </td>
                                <td>
                                    <?php if ($t['tournament_type'] === 'private'): ?>
                                        <span class="badge bg-dark text-warning border border-warning border-opacity-50" title="Passcode: <?php echo htmlspecialchars($t['access_code']); ?>">
                                            <i class="fa-solid fa-lock me-1"></i> Private (<code><?php echo htmlspecialchars($t['access_code']); ?></code>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-dark text-cyan border border-info border-opacity-50">
                                            <i class="fa-solid fa-globe me-1"></i> Public
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-light opacity-75">
                                    <?php echo date('M d, Y', strtotime($t['tournament_date'])); ?>
                                </td>
                                <td>
                                    <span class="text-white fw-bold"><?php echo $t['approved_count']; ?> / <?php echo $t['max_teams']; ?></span>
                                    <?php if ($t['pending_count'] > 0): ?>
                                        <a href="registrations.php?tournament_id=<?php echo $t['id']; ?>" class="badge bg-warning text-dark text-decoration-none ms-1">
                                            +<?php echo $t['pending_count']; ?> Pending
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="tournaments.php" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="tournament_id" value="<?php echo $t['id']; ?>">
                                        <select name="new_status" class="form-select form-select-custom form-select-sm py-0 px-2" style="font-size: 0.75rem; width: auto;" onchange="this.form.submit()">
                                            <option value="upcoming" <?php echo ($t['status'] === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                                            <option value="registration_open" <?php echo ($t['status'] === 'registration_open') ? 'selected' : ''; ?>>Registration Open</option>
                                            <option value="registration_closed" <?php echo ($t['status'] === 'registration_closed') ? 'selected' : ''; ?>>Registration Closed</option>
                                            <option value="ongoing" <?php echo ($t['status'] === 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                                            <option value="completed" <?php echo ($t['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo ($t['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="registrations.php?tournament_id=<?php echo $t['id']; ?>" class="btn btn-outline-secondary" title="Manage Registrations">
                                            <i class="fa-solid fa-user-check text-cyan"></i>
                                        </a>
                                        <a href="matches.php?tournament_id=<?php echo $t['id']; ?>" class="btn btn-outline-secondary" title="Matches & Rooms">
                                            <i class="fa-solid fa-door-open text-warning"></i>
                                        </a>
                                        <a href="results.php?tournament_id=<?php echo $t['id']; ?>" class="btn btn-outline-secondary" title="Enter Scores">
                                            <i class="fa-solid fa-calculator text-success"></i>
                                        </a>
                                        <a href="tournaments.php?delete=<?php echo $t['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this tournament? This will remove all attached registrations and matches.');" title="Delete Tournament">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
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
