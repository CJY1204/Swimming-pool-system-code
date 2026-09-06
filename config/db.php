<?php
/**
 * Database connection configuration.
 *
 * WAMPP DEFAULTS are set below. When you deploy to EC2 + RDS later,
 * change DB_HOST to your RDS endpoint and update DB_USER / DB_PASS
 * to the master credentials you set when creating the RDS instance.
 * Nothing else in the codebase needs to change.
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');  // <-- replace with RDS endpoint on deployment
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'pool_booking'); 
define('DB_USER', getenv('DB_USER') ?: 'root');       // <-- WAMPP default; replace with RDS master username
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');    // <-- WAMPP default; replace with RDS master password

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

