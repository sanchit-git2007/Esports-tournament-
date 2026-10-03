<?php
// =======================================================
// File: player/profile.php
// Purpose: Player Profile Management & Password Change
// =======================================================

$pageTitle = "My Profile - Esports Tournament Hub";
$basePath = "../";

require_once "../config/database.php";
require_once "../includes/auth.php";

$userId = $_SESSION['user_id'];
$msg = "";
$error = "";

// Fetch User Info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $error = "Name cannot be empty.";
    } else {
        try {
            if (!empty($newPass)) {
                if (strlen($newPass) < 6) {
                    $error = "New password must be at least 6 characters.";
                } elseif ($newPass !== $confirmPass) {
                    $error = "Passwords do not match.";
                } else {
                    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                    $uStmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, password = ? WHERE id = ?");
                    $uStmt->execute([$name, $phone, $hashed, $userId]);
                    $_SESSION['name'] = $name;
                    $msg = "Profile and password updated successfully!";
                }
            } else {
                $uStmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $uStmt->execute([$name, $phone, $userId]);
                $_SESSION['name'] = $name;
                $msg = "Profile updated successfully!";
            }
            $user['name'] = $name;
            $user['phone'] = $phone;
        } catch (PDOException $e) {
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
            <li class="breadcrumb-item text-white active">My Profile</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="esports-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="feature-icon-box mx-auto mb-2 text-cyan">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <h3 class="text-white mb-1">Account Profile</h3>
                    <p class="text-light opacity-75 small">Manage your player credentials and security settings.</p>
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

                <form action="profile.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label-custom">Email Address</label>
                        <input type="email" class="form-control form-control-custom bg-dark opacity-75" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        <small class="text-muted">Email address cannot be modified</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Phone Number</label>
                        <input type="tel" name="phone" class="form-control form-control-custom" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="10-digit mobile number">
                    </div>

                    <div class="pt-3 border-top border-secondary border-opacity-25 mb-3">
                        <h6 class="text-cyan brand-font mb-2"><i class="fa-solid fa-key me-1"></i> Change Password (Optional)</h6>
                        <small class="text-light opacity-75 d-block mb-3">Leave blank if you do not wish to change your password.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">New Password</label>
                        <input type="password" name="new_password" class="form-control form-control-custom" placeholder="Min. 6 characters">
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control form-control-custom" placeholder="Re-type new password">
                    </div>

                    <button type="submit" class="btn btn-neon-primary w-100">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Profile Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
