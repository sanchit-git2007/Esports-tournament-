<?php
$pageTitle = "Player Registration - Esports Tournament Hub";
$basePath = "";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: player/dashboard.php");
    }
    exit;
}

require_once "config/database.php";

$error = "";
$success = "";
$name = "";
$email = "";
$phone = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";
    } else {
        try {
            // Check for duplicate email
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);

            if ($checkStmt->rowCount() > 0) {
                $error = "An account with this email address is already registered.";
            } else {
                // Hash password securely
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert into database
                $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'player')");
                $insertStmt->execute([$name, $email, $hashedPassword, $phone]);

                $newUserId = $pdo->lastInsertId();

                // Start session automatically
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'player';

                header("Location: player/dashboard.php?welcome=1");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

require_once "includes/header.php";
require_once "includes/navbar.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="esports-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="feature-icon-box mx-auto mb-2">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="text-white mb-1">Create Player Account</h3>
                    <p class="text-muted small">Join esports tournaments, build squads, and compete for victory.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-white py-2 small" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fa-solid fa-user"></i></span>
                            <input type="text" name="name" class="form-control form-control-custom" placeholder="e.g. Naman Mathur" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control form-control-custom" placeholder="player@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Phone Number (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fa-solid fa-phone"></i></span>
                            <input type="tel" name="phone" class="form-control form-control-custom" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" class="form-control form-control-custom" placeholder="Min. 6 characters" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fa-solid fa-shield-halved"></i></span>
                            <input type="password" name="confirm_password" class="form-control form-control-custom" placeholder="Re-type password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-neon-primary w-100 mb-3">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Register Account
                    </button>
                </form>

                <div class="text-center mt-3 pt-3 border-top border-secondary border-opacity-25">
                    <p class="text-muted small mb-0">
                        Already have an account? <a href="login.php" class="text-cyan fw-bold text-decoration-none">Sign In Here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
