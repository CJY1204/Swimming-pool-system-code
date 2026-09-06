<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_init.php';

unset($_SESSION['member_id'], $_SESSION['member_name'], $_SESSION['member_email'], $_SESSION['member_username']);

header('Location: ' . BASE_URL . '/index.php');
exit;
