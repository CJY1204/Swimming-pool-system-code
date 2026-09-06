<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_init.php';

unset($_SESSION['admin_id'], $_SESSION['admin_display_name'], $_SESSION['admin_login_id']);

header('Location: ' . BASE_URL . '/login.php');
exit;
