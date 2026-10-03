<?php
// =======================================================
// File: admin/users.php
// Purpose: Manage Registered Users, View Roles & Contact Data
// =======================================================

$pageTitle = "Manage Users - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$msg = "";
$error = "";

// Handle Role Toggle (Player <-> Admin)
if (isset($_GET['toggle_role']) && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    if ($targetId === (int)$_SESSION['user_id']) {
        $error = "You cannot modify your own administrator role.";
    } else {
        $userCheck = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $userCheck->execute([$targetId]);
        $targetUser = $userCheck->fetch();

        if ($targetUser) {
            $newRole = ($targetUser['role'] === 'admin') ? 'player' : 'admin';
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $targetId]);
            $msg = "User role updated to '" . strtoupper($newRole) . "'.";
        }
    }
}

// Fetch All Users
$users = $pdo->query("
    SELECT u.*, COUNT(t.id) AS teams_created
    FROM users u
    LEFT JOIN teams t ON u.id = t.captain_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">Manage Users</li>
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

    <div class="esports-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="text-white mb-0"><i class="fa-solid fa-users text-cyan me-2"></i>Registered Users Directory</h4>
                <p class="text-light opacity-75 small mb-0">Overview of all system accounts, captains, and administrators.</p>
            </div>
            <span class="badge bg-dark border border-secondary text-cyan">
                <?php echo count($users); ?> Total Users
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-esports align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Squads Owned</th>
                        <th>Registered On</th>
                        <th class="text-end">Role Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($users as $u): ?>
                        <tr>
                            <td class="text-muted"><?php echo $i++; ?></td>
                            <td>
                                <strong class="text-white"><?php echo htmlspecialchars($u['name']); ?></strong>
                            </td>
                            <td class="text-light opacity-75 small"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="text-light opacity-75 small"><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-dark border border-info text-cyan">Player</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-white text-center"><?php echo $u['teams_created']; ?></td>
                            <td class="small text-light opacity-75"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td class="text-end">
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <a href="users.php?toggle_role=1&id=<?php echo $u['id']; ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Change user role?');">
                                        Toggle Role
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary opacity-50">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
