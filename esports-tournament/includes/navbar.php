<?php
$base = isset($basePath) ? $basePath : '';
$currentScript = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$userName = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand" href="<?php echo $base; ?>index.php">
            <i class="fa-solid fa-gamepad text-cyan"></i>
            <span>ESPORTS<span class="text-cyan">HUB</span></span>
        </a>

        <!-- Mobile Hamburger Toggle -->
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars fs-4 text-cyan"></i>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>" href="<?php echo $base; ?>index.php">
                        <i class="fa-solid fa-house me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'tournaments.php') ? 'active' : ''; ?>" href="<?php echo $base; ?>tournaments/index.php">
                        <i class="fa-solid fa-trophy me-1"></i> Tournaments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'about.php') ? 'active' : ''; ?>" href="<?php echo $base; ?>about.php">
                        <i class="fa-solid fa-circle-info me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'contact.php') ? 'active' : ''; ?>" href="<?php echo $base; ?>contact.php">
                        <i class="fa-solid fa-headset me-1"></i> Contact
                    </a>
                </li>
            </ul>

            <!-- Auth Action Buttons -->
            <div class="d-flex align-items-center gap-2">
                <?php if (!$isLoggedIn): ?>
                    <a href="<?php echo $base; ?>login.php" class="btn btn-neon-outline btn-sm">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                    </a>
                    <a href="<?php echo $base; ?>register.php" class="btn btn-neon-primary btn-sm">
                        <i class="fa-solid fa-user-plus me-1"></i> Register
                    </a>
                <?php else: ?>
                    <?php if ($userRole === 'admin'): ?>
                        <a href="<?php echo $base; ?>admin/dashboard.php" class="btn btn-flame btn-sm">
                            <i class="fa-solid fa-shield-halved me-1"></i> Admin Panel
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $base; ?>player/dashboard.php" class="btn btn-neon-primary btn-sm">
                            <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo $base; ?>logout.php" class="btn btn-outline-danger btn-sm" title="Logout">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
