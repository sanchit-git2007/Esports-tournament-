<?php
// =======================================================
// Database Configuration File (PDO Connection)
// Project: Esports Tournament Management System
// =======================================================

// Database credentials for local XAMPP environment
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'esports_tournament');
define('DB_PORT', '3306');

/**
 * Establish a secure PDO Database Connection
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements for security
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Friendly error message for college demonstration
            die("<div style='font-family:sans-serif; background:#1e1e2f; color:#ff6b6b; padding:20px; margin:20px; border-radius:8px; border:1px solid #ff4757;'>
                <h3 style='margin-top:0;'>⚠️ Database Connection Failed</h3>
                <p>Could not connect to MySQL database <strong>" . DB_NAME . "</strong>.</p>
                <p><strong>Error Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p><strong>Troubleshooting Steps:</strong></p>
                <ol>
                    <li>Make sure MySQL is started in your <strong>XAMPP Control Panel</strong>.</li>
                    <li>Ensure you imported <code>database/database.sql</code> into phpMyAdmin.</li>
                </ol>
            </div>");
        }
    }

    return $pdo;
}

// Global PDO instance for straightforward scripts
$pdo = getDBConnection();
