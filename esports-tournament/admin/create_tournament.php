<?php
// =======================================================
// File: admin/create_tournament.php
// Purpose: Create Public/Private Tournaments & Set BGMI Scoring Engine Rules
// =======================================================

$pageTitle = "Create Tournament - Admin Portal";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/admin_auth.php";

$adminId = $_SESSION['user_id'];
$error = "";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['tournament_name'] ?? '');
    $game = trim($_POST['game'] ?? 'BGMI');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['tournament_type'] ?? 'public');
    $accessCode = trim($_POST['access_code'] ?? '');
    $maxTeams = (int)($_POST['max_teams'] ?? 16);
    $regStart = $_POST['registration_start'] ?? '';
    $regEnd = $_POST['registration_end'] ?? '';
    $tourneyDate = $_POST['tournament_date'] ?? '';
    $status = $_POST['status'] ?? 'upcoming';
    $rules = trim($_POST['rules'] ?? '');
    $killPointRate = (int)($_POST['kill_points_rate'] ?? 1);

    if (empty($name) || empty($regStart) || empty($regEnd) || empty($tourneyDate)) {
        $error = "Please fill in all required fields.";
    } elseif ($type === 'private' && empty($accessCode)) {
        $error = "Private tournaments require a secret Access Passcode.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insert Tournament
            $stmt = $pdo->prepare("
                INSERT INTO tournaments 
                (tournament_name, game, description, tournament_type, access_code, max_teams, registration_start, registration_end, tournament_date, status, rules, kill_points_rate, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name, $game, $description, $type, 
                ($type === 'private' ? $accessCode : null), 
                $maxTeams, $regStart, $regEnd, $tourneyDate, $status, $rules, $killPointRate, $adminId
            ]);
            $newTourneyId = $pdo->lastInsertId();

            // 2. Insert Configurable BGMI Scoring Rules (Default 15-Point System or Custom)
            $defaultPoints = [
                1 => 15, 2 => 12, 3 => 10, 4 => 8, 
                5 => 6,  6 => 4,  7 => 2,  8 => 1, 
                9 => 1,  10 => 1, 11 => 1, 12 => 0, 
                13 => 0, 14 => 0, 15 => 0, 16 => 0
            ];

            $scoreStmt = $pdo->prepare("INSERT INTO tournament_scoring_rules (tournament_id, placement, points) VALUES (?, ?, ?)");
            foreach ($defaultPoints as $placement => $pts) {
                // If custom placement points were submitted in POST, use those
                if (isset($_POST["pts_{$placement}"])) {
                    $pts = (int)$_POST["pts_{$placement}"];
                }
                $scoreStmt->execute([$newTourneyId, $placement, $pts]);
            }

            $pdo->commit();

            header("Location: tournaments.php?created=1");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Database Error: " . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="tournaments.php" class="text-cyan text-decoration-none">Tournaments</a></li>
            <li class="breadcrumb-item text-white active">Create Tournament</li>
        </ol>
    </nav>

    <div class="esports-card p-4 p-md-5 border-flame">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 mb-1">
                    <i class="fa-solid fa-plus-circle me-1"></i> Admin Setup
                </span>
                <h2 class="text-white mb-0">Create New <span class="text-flame">Championship</span></h2>
            </div>
            <a href="tournaments.php" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Tournaments
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-white py-2 small mb-4">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="create_tournament.php" method="POST">
            <!-- Section 1: Basic Information -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h5 class="text-cyan brand-font border-bottom border-secondary border-opacity-25 pb-2 mb-3">
                        <i class="fa-solid fa-trophy me-2"></i> 1. Basic Tournament Details
                    </h5>
                </div>

                <div class="col-md-8">
                    <label class="form-label-custom">Tournament Name <span class="text-danger">*</span></label>
                    <input type="text" name="tournament_name" class="form-control form-control-custom" placeholder="e.g. SIES BGMI Championship 2026" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label-custom">Game Title</label>
                    <input type="text" name="game" class="form-control form-control-custom" value="BGMI" readonly>
                </div>

                <div class="col-12">
                    <label class="form-label-custom">Tournament Description & Prize Pool</label>
                    <textarea name="description" rows="3" class="form-control form-control-custom" placeholder="Provide details regarding prize pool, eligibility, campus departments, etc..."></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label-custom">Access Format <span class="text-danger">*</span></label>
                    <select name="tournament_type" id="tournamentTypeSelect" class="form-select form-select-custom" onchange="toggleAccessCodeField()">
                        <option value="public">Public (Open Registration)</option>
                        <option value="private">Private (Passcode Protected)</option>
                    </select>
                </div>

                <div class="col-md-4" id="accessCodeContainer" style="display: none;">
                    <label class="form-label-custom">Secret Access Passcode <span class="text-danger">*</span></label>
                    <input type="text" name="access_code" id="accessCodeInput" class="form-control form-control-custom" placeholder="e.g. BGMI2026">
                </div>

                <div class="col-md-4">
                    <label class="form-label-custom">Max Squad Slots</label>
                    <select name="max_teams" class="form-select form-select-custom">
                        <option value="16">16 Teams (Standard Custom Room)</option>
                        <option value="20">20 Teams</option>
                        <option value="25">25 Teams</option>
                        <option value="12">12 Teams</option>
                    </select>
                </div>
            </div>

            <!-- Section 2: Schedules & Status -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h5 class="text-cyan brand-font border-bottom border-secondary border-opacity-25 pb-2 mb-3">
                        <i class="fa-solid fa-calendar-days me-2"></i> 2. Schedules & Initial Status
                    </h5>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="form-label-custom">Registration Start <span class="text-danger">*</span></label>
                    <input type="date" name="registration_start" class="form-control form-control-custom" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="form-label-custom">Registration End <span class="text-danger">*</span></label>
                    <input type="date" name="registration_end" class="form-control form-control-custom" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="form-label-custom">Match / Event Date <span class="text-danger">*</span></label>
                    <input type="date" name="tournament_date" class="form-control form-control-custom" value="<?php echo date('Y-m-d', strtotime('+8 days')); ?>" required>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="form-label-custom">Initial Status</label>
                    <select name="status" class="form-select form-select-custom">
                        <option value="registration_open">Registration Open</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="registration_closed">Registration Closed</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label-custom">Tournament Rules & Guidelines</label>
                    <textarea name="rules" rows="3" class="form-control form-control-custom" placeholder="e.g. Standard esports rules apply. Emulators, hacks, and iPad views are strictly prohibited."></textarea>
                </div>
            </div>

            <!-- Section 3: BGMI Configurable Scoring Engine -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-25 pb-2 mb-3">
                        <h5 class="text-cyan brand-font mb-0">
                            <i class="fa-solid fa-calculator me-2"></i> 3. Configurable BGMI Scoring Engine
                        </h5>
                        <span class="badge bg-dark border border-secondary text-cyan">Standard 15-Pt Preset</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label-custom">Kill Point Rate (Per Frag)</label>
                    <div class="input-group">
                        <input type="number" name="kill_points_rate" class="form-control form-control-custom" value="1" min="1" max="10" required>
                        <span class="input-group-text bg-dark border-secondary text-light">Pt / Kill</span>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <label class="form-label-custom mb-2">Custom Placement Points Table (Top 16):</label>
                    <div class="row g-2 text-center small">
                        <?php
                        $preset = [1=>15, 2=>12, 3=>10, 4=>8, 5=>6, 6=>4, 7=>2, 8=>1, 9=>1, 10=>1, 11=>1, 12=>0, 13=>0, 14=>0, 15=>0, 16=>0];
                        foreach ($preset as $rank => $pts):
                        ?>
                            <div class="col-3 col-md-2 col-lg-1">
                                <div class="bg-dark p-2 rounded border border-secondary border-opacity-25">
                                    <span class="text-light opacity-75 d-block" style="font-size:0.75rem;">#<?php echo $rank; ?></span>
                                    <input type="number" name="pts_<?php echo $rank; ?>" class="form-control form-control-custom form-control-sm text-center fw-bold p-1" value="<?php echo $pts; ?>" min="0">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-3 border-top border-secondary border-opacity-25 d-flex gap-3">
                <button type="submit" class="btn btn-flame px-4 py-2">
                    <i class="fa-solid fa-check-circle me-2"></i> Launch Championship
                </button>
                <a href="tournaments.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAccessCodeField() {
    const typeSelect = document.getElementById('tournamentTypeSelect');
    const container = document.getElementById('accessCodeContainer');
    const input = document.getElementById('accessCodeInput');
    
    if (typeSelect.value === 'private') {
        container.style.display = 'block';
        input.setAttribute('required', 'required');
    } else {
        container.style.display = 'none';
        input.removeAttribute('required');
    }
}
</script>

<?php require_once "../includes/footer.php"; ?>
