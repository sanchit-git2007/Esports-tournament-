<?php
// =======================================================
// Player Authentication Check Middleware
// Protects player routes from unauthenticated access
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Determine path back to root login.php
    $loginUrl = (isset($basePath) ? $basePath : '') . 'login.php?error=unauthorized';
    header("Location: " . $loginUrl);
    exit;
}
