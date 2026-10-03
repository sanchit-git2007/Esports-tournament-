<?php
// =======================================================
// File: player/create_team.php
// Purpose: Allows a logged-in player to create a new BGMI Squad
// =======================================================

$pageTitle = "Create Squad - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/auth.php";

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$error = "";

// Step 1: Check if the player already owns a team
$checkStmt = $pdo->prepare("SELECT id FROM teams WHERE captain_id = ?");
$checkStmt->execute([$userId]);
if ($checkStmt->rowCount() > 0) {
    // If they already have a team, redirect them to manage it
    header("Location: team.php");
    exit;
}

// Step 2: Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamName = trim($_POST['team_name'] ?? '');
    $teamTag = strtoupper(trim($_POST['team_tag'] ?? ''));
    $ign = trim($_POST['in_game_name'] ?? '');
    $characterId = trim($_POST['in_game_id'] ?? '');

    // Validation
    if (empty($teamName) || empty($teamTag) || empty($ign) || empty($characterId)) {
        $error = "Please fill in all required fields.";
    } elseif (strlen($teamTag) < 2 || strlen($teamTag) > 6) {
        $error = "Team Tag must be between 2 and 6 characters (e.g. SOUL, GODL).";
    } else {
        try {
            // Check for duplicate team name
            $dupStmt = $pdo->prepare("SELECT id FROM teams WHERE team_name = ?");
            $dupStmt->execute([$teamName]);
            if ($dupStmt->rowCount() > 0) {
                $error = "A team with this name already exists. Please choose a unique name.";
            } else {
                // Begin Transaction for atomic creation
                $pdo->beginTransaction();

                // 1. Insert Team
                $insertTeam = $pdo->prepare("INSERT INTO teams (team_name, team_tag, captain_id) VALUES (?, ?, ?)");
                $insertTeam->execute([$teamName, $teamTag, $userId]);
                $newTeamId = $pdo->lastInsertId();

                // 2. Automatically add the Captain to the squad roster
                $insertMember = $pdo->prepare("
                    INSERT INTO team_members (team_id, user_id, in_game_name, in_game_id, player_role) 
                    VALUES (?, ?, ?, ?, 'Captain / IGL')
                ");
                $insertMember->execute([$newTeamId, $userId, $ign, $characterId]);

                $pdo->commit();

                // Redirect to team management page with success notification
                header("Location: team.php?created=1");
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item text-muted active">Create Squad</li>
                </ol>
            </nav>

            <div class="esports-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="feature-icon-box mx-auto mb-2">
                        <i class="fa-solid fa-people-group"></i>
                    </div>
                    <h3 class="text-white mb-1">Create Your BGMI Squad</h3>
                    <p class="text-muted small">As the squad creator, you will be the Team Captain with full authority to register for tournaments.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-white py-2 small" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="create_team.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label-custom">Squad / Team Name <span class="text-danger">*</span></label>
                            <input type="text" name="team_name" class="form-control form-control-custom" placeholder="e.g. GodLike Esports" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-custom">Team Tag <span class="text-danger">*</span></label>
                            <input type="text" name="team_tag" class="form-control form-control-custom" placeholder="e.g. GODL" maxlength="6" required>
                            <small class="text-muted" style="font-size: 0.75rem;">Max 6 letters</small>
                        </div>

                        <div class="col-12 pt-3 border-top border-secondary border-opacity-25">
                            <h6 class="text-cyan brand-font mb-2"><i class="fa-solid fa-id-card me-1"></i> Captain's BGMI In-Game Info</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-custom">In-Game Name (IGN) <span class="text-danger">*</span></label>
                            <input type="text" name="in_game_name" class="form-control form-control-custom" placeholder="e.g. SOUL_Mortal" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">BGMI Character ID <span class="text-danger">*</span></label>
                            <input type="text" name="in_game_id" class="form-control form-control-custom" placeholder="e.g. 5123456789" required>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-neon-primary w-100 py-2">
                                <i class="fa-solid fa-shield-halved me-2"></i> Register Squad & Proceed
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
