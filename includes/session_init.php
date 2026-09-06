<?php
/**
 * Central session bootstrap.
 *
 * Do NOT call session_start() directly anywhere else in the app —
 * always go through this file (or includes/functions.php, which
 * requires this automatically), so every page consistently uses the
 * MySQL-backed session handler instead of PHP's local-disk default.
 *
 * Requires $pdo to already exist — config/db.php must be required
 * before this file.
 */
require_once __DIR__ . '/db_session_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    $handler = new DbSessionHandler($pdo);
    session_set_save_handler($handler, true);
    session_start();
}
