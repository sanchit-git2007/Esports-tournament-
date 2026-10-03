<?php
// =======================================================
// File: tournaments/index.php
// Purpose: Public Tournament Directory with Search & Filtering
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Browse Tournaments - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";

// Fetch Search and Filter Parameters
$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

// Build Dynamic SQL Query
$sql = "
    SELECT t.*, 
           COUNT(CASE WHEN tr.status = 'approved' THEN 1 END) AS approved_teams_count,
           COUNT(tr.id) AS total_applied_count
    FROM tournaments t
    LEFT JOIN tournament_registrations tr ON t.id = tr.tournament_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (t.tournament_name LIKE ? OR t.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($typeFilter) && in_array($typeFilter, ['public', 'private'])) {
    $sql .= " AND t.tournament_type = ?";
    $params[] = $typeFilter;
}

if (!empty($statusFilter) && in_array($statusFilter, ['upcoming', 'registration_open', 'registration_closed', 'ongoing', 'completed'])) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

$sql .= " GROUP BY t.id ORDER BY t.tournament_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tournaments = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">
    <!-- Header Section -->
    <div class="text-center mb-5">
        <span class="text-cyan text-uppercase fw-bold small letter-spacing">Esports Arena</span>
        <h1 class="text-white hero-title mb-2">Explore <span class="text-cyan">Tournaments</span></h1>
        <p class="text-light opacity-75 mx-auto" style="max-width: 600px;">
            Browse upcoming BGMI championships, view rules, inspect prize pools, and register your squad for open brackets.
        </p>
    </div>

    <!-- Search & Filter Controls -->
    <div class="esports-card p-4 mb-5 border-cyan">
        <form action="index.php" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5 col-md-6">
                    <label class="form-label-custom"><i class="fa-solid fa-magnifying-glass text-cyan me-1"></i> Search Tournament</label>
                    <input type="text" name="search" class="form-control form-control-custom" placeholder="Search by championship name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="col-lg-3 col-md-3 col-sm-6">
                    <label class="form-label-custom"><i class="fa-solid fa-lock text-cyan me-1"></i> Access Type</label>
                    <select name="type" class="form-select form-select-custom">
                        <option value="">All Types (Public & Private)</option>
                        <option value="public" <?php echo ($typeFilter === 'public') ? 'selected' : ''; ?>>Public (Open)</option>
                        <option value="private" <?php echo ($typeFilter === 'private') ? 'selected' : ''; ?>>Private (Passcode Protected)</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label class="form-label-custom"><i class="fa-solid fa-filter text-cyan me-1"></i> Status</label>
                    <select name="status" class="form-select form-select-custom">
                        <option value="">All Statuses</option>
                        <option value="registration_open" <?php echo ($statusFilter === 'registration_open') ? 'selected' : ''; ?>>Registration Open</option>
                        <option value="upcoming" <?php echo ($statusFilter === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="ongoing" <?php echo ($statusFilter === 'ongoing') ? 'selected' : ''; ?>>Live / Ongoing</option>
                        <option value="completed" <?php echo ($statusFilter === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-neon-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($typeFilter) || !empty($statusFilter)): ?>
                        <a href="index.php" class="btn btn-outline-secondary" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Tournament Results Grid -->
    <?php if (empty($tournaments)): ?>
        <div class="esports-card text-center py-5">
            <i class="fa-solid fa-trophy text-muted fs-1 mb-3 opacity-50"></i>
            <h4 class="text-white mb-2">No Tournaments Found</h4>
            <p class="text-light opacity-75 small mb-3">No tournaments matched your search criteria. Try adjusting the filters above.</p>
            <a href="index.php" class="btn btn-neon-outline btn-sm">Clear All Filters</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($tournaments as $t): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="esports-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <!-- Header Badges -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <?php
                                $statusBadgeClass = 'badge-upcoming';
                                if ($t['status'] === 'registration_open') $statusBadgeClass = 'badge-open';
                                elseif ($t['status'] === 'ongoing') $statusBadgeClass = 'badge-live';
                                elseif ($t['status'] === 'completed') $statusBadgeClass = 'badge-completed';
                                ?>
                                <span class="badge-custom <?php echo $statusBadgeClass; ?>">
                                    <?php echo str_replace('_', ' ', $t['status']); ?>
                                </span>

                                <?php if ($t['tournament_type'] === 'private'): ?>
                                    <span class="badge bg-dark text-warning border border-warning border-opacity-50">
                                        <i class="fa-solid fa-lock me-1"></i> Private
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-dark text-cyan border border-info border-opacity-50">
                                        <i class="fa-solid fa-globe me-1"></i> Public
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Tournament Title & Description -->
                            <h4 class="text-white mb-2"><?php echo htmlspecialchars($t['tournament_name']); ?></h4>
                            <p class="text-light opacity-75 small mb-3 text-truncate-2">
                                <?php echo htmlspecialchars($t['description'] ?: 'Official BGMI squad championship.'); ?>
                            </p>

                            <!-- Key Specs Grid -->
                            <div class="bg-dark p-3 rounded mb-3 border border-secondary border-opacity-25 small">
                                <div class="d-flex justify-content-between text-light opacity-75 mb-2">
                                    <span><i class="fa-solid fa-gamepad text-cyan me-2"></i>Game:</span>
                                    <strong class="text-white"><?php echo htmlspecialchars($t['game']); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between text-light opacity-75 mb-2">
                                    <span><i class="fa-solid fa-users text-cyan me-2"></i>Slots:</span>
                                    <strong class="text-white"><?php echo $t['approved_teams_count']; ?> / <?php echo $t['max_teams']; ?> Teams</strong>
                                </div>
                                <div class="d-flex justify-content-between text-light opacity-75 mb-2">
                                    <span><i class="fa-solid fa-calendar-days text-cyan me-2"></i>Event Date:</span>
                                    <strong class="text-white"><?php echo date('M d, Y', strtotime($t['tournament_date'])); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between text-light opacity-75">
                                    <span><i class="fa-solid fa-gun text-flame me-2"></i>Kill Points:</span>
                                    <strong class="text-flame"><?php echo $t['kill_points_rate']; ?> Pt / Kill</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div>
                            <a href="details.php?id=<?php echo $t['id']; ?>" class="btn <?php echo ($t['status'] === 'registration_open') ? 'btn-neon-primary' : 'btn-neon-outline'; ?> w-100 btn-sm">
                                <i class="fa-solid fa-circle-info me-1"></i> View Details & Register
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
