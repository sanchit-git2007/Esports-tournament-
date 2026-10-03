<?php
// =======================================================
// File: admin/matches.php
// Purpose: Match Scheduling, Map Selection & Custom Room Management
// =======================================================

$pageTitle = "Match Management - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$msg = "";
$error = "";

// 1. Fetch Tournaments for Selector
$tournaments = $pdo->query("SELECT id, tournament_name FROM tournaments ORDER BY tournament_date DESC")->fetchAll();
$selectedTournamentId = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : ($tournaments[0]['id'] ?? 0);

// 2. Handle Action: Create New Match
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_match') {
    $tId = (int)$_POST['tournament_id'];
    $matchNum = (int)$_POST['match_number'];
    $matchName = trim($_POST['match_name'] ?? '');
    $matchDate = $_POST['match_date'] ?? '';
    $matchTime = $_POST['match_time'] ?? '';
    $map = $_POST['map'] ?? 'Erangel';
    $roomId = trim($_POST['room_id'] ?? '');
    $roomPass = trim($_POST['room_password'] ?? '');

    if (empty($matchName) || empty($matchDate) || empty($matchTime)) {
        $error = "Please fill in all required match details.";
    } else {
        $insert = $pdo->prepare("
            INSERT INTO matches (tournament_id, match_number, match_name, match_date, match_time, map, room_id, room_password, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        $insert->execute([$tId, $matchNum, $matchName, $matchDate, $matchTime, $map, $roomId, $roomPass]);
        $msg = "Match successfully scheduled!";
        $selectedTournamentId = $tId;
    }
}

// 3. Handle Action: Update Room ID & Password or Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_room') {
    $mId = (int)$_POST['match_id'];
    $roomId = trim($_POST['room_id'] ?? '');
    $roomPass = trim($_POST['room_password'] ?? '');
    $status = $_POST['status'] ?? 'scheduled';

    $update = $pdo->prepare("UPDATE matches SET room_id = ?, room_password = ?, status = ? WHERE id = ?");
    $update->execute([$roomId, $roomPass, $status, $mId]);
    $msg = "Room credentials and match status updated successfully!";
}

// 4. Handle Action: Delete Match
if (isset($_GET['delete_match'])) {
    $delId = (int)$_GET['delete_match'];
    $pdo->prepare("DELETE FROM matches WHERE id = ?")->execute([$delId]);
    $msg = "Match deleted successfully.";
}

// 5. Fetch Matches for Selected Tournament
$matches = [];
if ($selectedTournamentId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE tournament_id = ? ORDER BY match_number ASC");
    $stmt->execute([$selectedTournamentId]);
    $matches = $stmt->fetchAll();
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="tournaments.php" class="text-cyan text-decoration-none">Tournaments</a></li>
            <li class="breadcrumb-item text-white active">Match Scheduling & Rooms</li>
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

    <!-- Header and Tournament Selector -->
    <div class="esports-card p-4 mb-4 border-flame">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <h3 class="text-white mb-1"><i class="fa-solid fa-door-open text-flame me-2"></i>Match & Room Credentials</h3>
                <p class="text-light opacity-75 small mb-0">Schedule rounds, designate maps, and securely assign custom Room IDs and Passwords.</p>
            </div>
            <div class="col-lg-5">
                <form action="matches.php" method="GET">
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

    <!-- Match List & Actions -->
    <div class="esports-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="text-white mb-0">Scheduled Rounds</h4>
                <small class="text-light opacity-75">Room details are automatically displayed to approved teams on their player dashboard.</small>
            </div>
            <button type="button" class="btn btn-flame btn-sm" data-bs-toggle="modal" data-bs-target="#addMatchModal">
                <i class="fa-solid fa-plus-circle me-1"></i> Add Match Round
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-esports align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Match Round</th>
                        <th>Map</th>
                        <th>Schedule</th>
                        <th>Room Credentials</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($matches)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No matches scheduled for this tournament yet. Click 'Add Match Round' to create one!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($matches as $m): ?>
                            <tr>
                                <td class="text-muted fw-bold">R<?php echo $m['match_number']; ?></td>
                                <td>
                                    <strong class="text-white"><?php echo htmlspecialchars($m['match_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-cyan">
                                        <?php echo htmlspecialchars($m['map']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-white small"><i class="fa-solid fa-calendar me-1 text-cyan"></i><?php echo date('M d, Y', strtotime($m['match_date'])); ?></div>
                                    <div class="text-light opacity-75 small"><i class="fa-solid fa-clock me-1 text-cyan"></i><?php echo date('h:i A', strtotime($m['match_time'])); ?></div>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="text-light opacity-75">ID:</span> 
                                        <code class="text-warning bg-dark px-1 rounded"><?php echo $m['room_id'] ? htmlspecialchars($m['room_id']) : 'Pending'; ?></code>
                                    </div>
                                    <div class="small mt-1">
                                        <span class="text-light opacity-75">Pass:</span> 
                                        <code class="text-warning bg-dark px-1 rounded"><?php echo $m['room_password'] ? htmlspecialchars($m['room_password']) : 'Pending'; ?></code>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $badge = 'bg-secondary';
                                    if ($m['status'] === 'scheduled') $badge = 'bg-primary';
                                    elseif ($m['status'] === 'live') $badge = 'bg-danger animate-pulse';
                                    elseif ($m['status'] === 'completed') $badge = 'bg-success';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> text-uppercase small" style="font-size:0.7rem;">
                                        <?php echo $m['status']; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Edit Room Modal Trigger -->
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editRoomModal<?php echo $m['id']; ?>" title="Update Room ID & Password">
                                            <i class="fa-solid fa-key text-warning"></i>
                                        </button>
                                        <!-- Enter Results Button -->
                                        <a href="results.php?tournament_id=<?php echo $selectedTournamentId; ?>&match_id=<?php echo $m['id']; ?>" class="btn btn-outline-secondary" title="Enter Scores / Results">
                                            <i class="fa-solid fa-calculator text-success"></i>
                                        </a>
                                        <!-- Delete Match -->
                                        <a href="matches.php?tournament_id=<?php echo $selectedTournamentId; ?>&delete_match=<?php echo $m['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this match round?');" title="Delete Match">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Room Modal -->
                            <div class="modal fade" id="editRoomModal<?php echo $m['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content bg-dark text-white border border-secondary">
                                        <div class="modal-header border-secondary border-opacity-25">
                                            <h5 class="modal-title brand-font text-warning">
                                                <i class="fa-solid fa-key me-2"></i> Update Room Details - <?php echo htmlspecialchars($m['match_name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="matches.php?tournament_id=<?php echo $selectedTournamentId; ?>" method="POST">
                                            <input type="hidden" name="action" value="update_room">
                                            <input type="hidden" name="match_id" value="<?php echo $m['id']; ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label-custom">BGMI Custom Room ID</label>
                                                    <input type="text" name="room_id" class="form-control form-control-custom" value="<?php echo htmlspecialchars($m['room_id']); ?>" placeholder="e.g. 9845123">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label-custom">Room Password</label>
                                                    <input type="text" name="room_password" class="form-control form-control-custom" value="<?php echo htmlspecialchars($m['room_password']); ?>" placeholder="e.g. bgmi2026">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label-custom">Match Status</label>
                                                    <select name="status" class="form-select form-select-custom">
                                                        <option value="scheduled" <?php echo ($m['status'] === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                                        <option value="live" <?php echo ($m['status'] === 'live') ? 'selected' : ''; ?>>Live</option>
                                                        <option value="completed" <?php echo ($m['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="cancelled" <?php echo ($m['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-secondary border-opacity-25">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-flame btn-sm">Save & Release Credentials</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Match Modal -->
<div class="modal fade" id="addMatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border border-secondary">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title brand-font text-cyan">
                    <i class="fa-solid fa-plus-circle me-2"></i> Schedule New Match Round
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="matches.php?tournament_id=<?php echo $selectedTournamentId; ?>" method="POST">
                <input type="hidden" name="action" value="create_match">
                <input type="hidden" name="tournament_id" value="<?php echo $selectedTournamentId; ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label-custom">Round #</label>
                            <input type="number" name="match_number" class="form-control form-control-custom" value="<?php echo count($matches) + 1; ?>" min="1" required>
                        </div>
                        <div class="col-8">
                            <label class="form-label-custom">Match Name <span class="text-danger">*</span></label>
                            <input type="text" name="match_name" class="form-control form-control-custom" placeholder="e.g. Match 1 - Erangel" value="Match <?php echo count($matches) + 1; ?> - Erangel" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-custom">Map <span class="text-danger">*</span></label>
                            <select name="map" class="form-select form-select-custom">
                                <option value="Erangel">Erangel (Classic 8x8)</option>
                                <option value="Miramar">Miramar (Desert 8x8)</option>
                                <option value="Sanhok">Sanhok (Jungle 4x4)</option>
                                <option value="Vikendi">Vikendi (Snow 6x6)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label-custom">Match Date <span class="text-danger">*</span></label>
                            <input type="date" name="match_date" class="form-control form-control-custom" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-custom">Match Time <span class="text-danger">*</span></label>
                            <input type="time" name="match_time" class="form-control form-control-custom" value="18:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-custom">Room ID (Optional)</label>
                            <input type="text" name="room_id" class="form-control form-control-custom" placeholder="e.g. 9845123">
                        </div>
                        <div class="col-12">
                            <label class="form-label-custom">Room Password (Optional)</label>
                            <input type="text" name="room_password" class="form-control form-control-custom" placeholder="e.g. bgmi2026">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-neon-primary btn-sm">Schedule Round</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
