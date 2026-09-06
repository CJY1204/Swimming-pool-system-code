<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/session_init.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
