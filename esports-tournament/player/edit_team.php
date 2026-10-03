<?php
// =======================================================
// File: player/edit_team.php
// Purpose: Allows Team Captain to edit squad name and tag
// =======================================================

$pageTitle = "Edit Squad - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/auth.php";

$userId = $_SESSION['user_id'];
$error = "";
$msg = "";

// 1. Fetch the Team owned by the captain
$stmt = $pdo->prepare("SELECT * FROM teams WHERE captain_id = ?");
$stmt->execute([$userId]);
$team = $stmt->fetch();

if (!$team) {
    header("Location: dashboard.php");
    exit;
}

// 2. Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamName = trim($_POST['team_name'] ?? '');
    $teamTag = strtoupper(trim($_POST['team_tag'] ?? ''));

    if (empty($teamName) || empty($teamTag)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($teamTag) < 2 || strlen($teamTag) > 6) {
        $error = "Team Tag must be between 2 and 6 characters.";
    } else {
        try {
            // Check for duplicate name if changed
            $dupStmt = $pdo->prepare("SELECT id FROM teams WHERE team_name = ? AND id != ?");
            $dupStmt->execute([$teamName, $team['id']]);

            if ($dupStmt->rowCount() > 0) {
                $error = "Another team already uses this name.";
            } else {
                $updateStmt = $pdo->prepare("UPDATE teams SET team_name = ?, team_tag = ? WHERE id = ?");
                $updateStmt->execute([$teamName, $teamTag, $team['id']]);
                
                // Refresh team info
                $team['team_name'] = $teamName;
                $team['team_tag'] = $teamTag;
                $msg = "Squad details updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-cyan text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="team.php" class="text-cyan text-decoration-none">My Squad</a></li>
                    <li class="breadcrumb-item text-muted active">Edit Squad</li>
                </ol>
            </nav>

            <div class="esports-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="feature-icon-box mx-auto mb-2">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <h3 class="text-white mb-1">Edit Squad Details</h3>
                    <p class="text-muted small">Update your official BGMI team name and tag.</p>
                </div>

                <?php if (!empty($msg)): ?>
                    <div class="alert alert-success bg-success bg-opacity-25 border-success text-white py-2 small alert-auto-dismiss mb-3">
                        <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($msg); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-white py-2 small mb-3">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="edit_team.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label-custom">Squad Name</label>
                        <input type="text" name="team_name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Team Tag</label>
                        <input type="text" name="team_tag" class="form-control form-control-custom" value="<?php echo htmlspecialchars($team['team_tag']); ?>" maxlength="6" required>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-neon-primary w-100">Save Changes</button>
                        <a href="team.php" class="btn btn-outline-secondary w-50">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
