<?php
$pageTitle = "Login - Esports Tournament Hub";
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
$email = "";

// Check for URL query notices
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = "Please sign in to access that page.";
} elseif (isset($_GET['logged_out'])) {
    $successMsg = "You have successfully logged out.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, initialize session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Role-based routing
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: player/dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid email or password. Please check your credentials.";
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
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </div>
                    <h3 class="text-white mb-1">Sign In</h3>
                    <p class="text-muted small">Access your player portal or administrator dashboard.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-white py-2 small" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($successMsg)): ?>
                    <div class="alert alert-success bg-success bg-opacity-25 border-success text-white py-2 small alert-auto-dismiss" role="alert">
                        <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($successMsg); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label-custom">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" id="loginEmail" name="email" class="form-control form-control-custom" placeholder="name@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" id="loginPassword" name="password" class="form-control form-control-custom" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-neon-primary w-100 mb-3">
                        <i class="fa-solid fa-shield-halved me-2"></i> Log In to Portal
                    </button>
                </form>

                <!-- Demo Credentials Helper -->
                <div class="bg-dark p-3 rounded border border-secondary border-opacity-25 mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted fw-bold text-uppercase"><i class="fa-solid fa-key text-cyan me-1"></i> Demo Credentials</span>
                        <span class="badge bg-secondary small">One-Click Fill</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-warning w-50" onclick="fillCredentials('admin@esportshub.com', 'admin123')">
                            <i class="fa-solid fa-user-shield me-1"></i> Admin
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info w-50" onclick="fillCredentials('captain@soul.com', 'player123')">
                            <i class="fa-solid fa-gamepad me-1"></i> Captain
                        </button>
                    </div>
                </div>

                <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-25">
                    <p class="text-muted small mb-0">
                        Don't have an account? <a href="register.php" class="text-cyan fw-bold text-decoration-none">Register New Player</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillCredentials(email, pass) {
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPassword').value = pass;
}
</script>

<?php require_once "includes/footer.php"; ?>
