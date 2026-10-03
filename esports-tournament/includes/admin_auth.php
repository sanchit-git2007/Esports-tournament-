<?php
// =======================================================
// Admin Authorization Check Middleware
// Protects admin management pages from non-admin users
// =======================================================

require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Determine path back to home with forbidden warning
    $homeUrl = (isset($basePath) ? $basePath : '') . 'index.php?error=forbidden';
    header("Location: " . $homeUrl);
    exit;
}
