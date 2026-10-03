<?php
// =======================================================
// File: admin/registrations.php
// Purpose: Review, Approve & Reject Team Tournament Registrations
// =======================================================

$pageTitle = "Squad Approvals - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$msg = "";
$error = "";

// 1. Handle Approve Action
if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    $regId = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE tournament_registrations SET status = 'approved', approved_at = NOW() WHERE id = ?");
    $stmt->execute([$regId]);
    $msg = "Squad application approved successfully! The team can now participate in matches.";
}

// 2. Handle Reject Action
if (isset($_GET['action']) && $_GET['action'] === 'reject' && isset($_GET['id'])) {
    $regId = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE tournament_registrations SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$regId]);
    $msg = "Squad application rejected.";
}

// 3. Filter Parameters
$tournamentFilter = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;
$statusFilter = trim($_GET['status'] ?? '');

// Fetch Tournaments for Filter Dropdown
$tourneyList = $pdo->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_date DESC")->fetchAll();

// Build Query
$sql = "
    SELECT tr.*, t.tournament_name, t.max_teams, tm.team_name, tm.team_tag, tm.id AS team_id, u.name AS captain_name, u.email AS captain_email, u.phone AS captain_phone
    FROM tournament_registrations tr
    JOIN tournaments t ON tr.tournament_id = t.id
    JOIN teams tm ON tr.team_id = tm.id
    JOIN users u ON tm.captain_id = u.id
    WHERE 1=1
";
$params = [];

if ($tournamentFilter > 0) {
    $sql .= " AND tr.tournament_id = ?";
    $params[] = $tournamentFilter;
}

if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $sql .= " AND tr.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY (tr.status = 'pending') DESC, tr.registered_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item text-white active">Manage Registrations</li>
        </ol>
    </nav>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-success bg-success bg-opacity-25 border-success text-white py-2 small alert-auto-dismiss mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Filter Bar Card -->
    <div class="esports-card p-3 mb-4">
        <form action="registrations.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label-custom">Filter by Tournament</label>
                <select name="tournament_id" class="form-select form-select-custom" onchange="this.form.submit()">
                    <option value="">All Tournaments</option>
                    <?php foreach ($tourneyList as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo ($tournamentFilter === $t['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['tournament_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label-custom">Filter by Status</label>
                <select name="status" class="form-select form-select-custom" onchange="this.form.submit()">
                    <option value="">All Statuses (Pending, Approved, Rejected)</option>
                    <option value="pending" <?php echo ($statusFilter === 'pending') ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="approved" <?php echo ($statusFilter === 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo ($statusFilter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div class="col-md-3">
                <a href="registrations.php" class="btn btn-outline-secondary w-100">Reset Filters</a>
            </div>
        </form>
    </div>

    <!-- Registrations Table -->
    <div class="esports-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="text-white mb-0"><i class="fa-solid fa-user-check text-cyan me-2"></i>Squad Registrations</h4>
                <p class="text-light opacity-75 small mb-0">Approve or reject squad participation for competitive brackets.</p>
            </div>
            <span class="badge bg-dark border border-secondary text-cyan">
                <?php echo count($registrations); ?> Total Records
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-esports align-middle mb-0">
                <thead>
                    <tr>
                        <th>Squad Name</th>
                        <th>Championship</th>
                        <th>Captain Contact</th>
                        <th>Applied On</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No squad registrations found matching criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registrations as $r): ?>
                            <tr>
                                <td>
                                    <strong class="text-white"><?php echo htmlspecialchars($r['team_name']); ?></strong>
                                    <span class="badge bg-dark border border-secondary text-cyan small ms-1">[<?php echo htmlspecialchars($r['team_tag']); ?>]</span>
                                </td>
                                <td>
                                    <span class="text-light"><?php echo htmlspecialchars($r['tournament_name']); ?></span>
                                </td>
                                <td>
                                    <div class="text-white small fw-bold"><?php echo htmlspecialchars($r['captain_name']); ?></div>
                                    <div class="small text-light opacity-75"><i class="fa-solid fa-envelope me-1 text-cyan"></i><?php echo htmlspecialchars($r['captain_email']); ?></div>
                                    <?php if (!empty($r['captain_phone'])): ?>
                                        <div class="small text-light opacity-75"><i class="fa-solid fa-phone me-1 text-success"></i><?php echo htmlspecialchars($r['captain_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-light opacity-75">
                                    <?php echo date('M d, Y h:i A', strtotime($r['registered_at'])); ?>
                                </td>
                                <td>
                                    <?php if ($r['status'] === 'approved'): ?>
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success">Approved</span>
                                    <?php elseif ($r['status'] === 'rejected'): ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning fw-bold">Pending Review</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <a href="registrations.php?action=approve&id=<?php echo $r['id']; ?>" class="btn btn-success btn-sm me-1" title="Approve Squad">
                                            <i class="fa-solid fa-check me-1"></i> Approve
                                        </a>
                                        <a href="registrations.php?action=reject&id=<?php echo $r['id']; ?>" class="btn btn-danger btn-sm" title="Reject Squad">
                                            <i class="fa-solid fa-xmark me-1"></i> Reject
                                        </a>
                                    <?php else: ?>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($r['status'] !== 'approved'): ?>
                                                <a href="registrations.php?action=approve&id=<?php echo $r['id']; ?>" class="btn btn-outline-success" title="Switch to Approved">
                                                    <i class="fa-solid fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($r['status'] !== 'rejected'): ?>
                                                <a href="registrations.php?action=reject&id=<?php echo $r['id']; ?>" class="btn btn-outline-danger" title="Switch to Rejected">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
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
