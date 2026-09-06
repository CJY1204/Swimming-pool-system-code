<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>SplashPoint Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/tokens.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/site.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/admin.css">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a href="<?php echo BASE_URL; ?>/index.php" class="brand"><span class="brand-mark">🌊</span> SplashPoint</a>
    <nav class="side-nav">
      <button onclick="location.href='dashboard.php'" class="<?php echo ($activeNav ?? '') === 'dashboard' ? 'active' : ''; ?>"><span class="ic">📊</span> Dashboard</button>
      <button onclick="location.href='pools.php'" class="<?php echo ($activeNav ?? '') === 'pools' ? 'active' : ''; ?>"><span class="ic">🏊</span> Pools</button>
      <button onclick="location.href='schedules.php'" class="<?php echo ($activeNav ?? '') === 'schedules' ? 'active' : ''; ?>"><span class="ic">🕒</span> Schedules</button>
      <button onclick="location.href='bookings.php'" class="<?php echo ($activeNav ?? '') === 'bookings' ? 'active' : ''; ?>"><span class="ic">📋</span> Bookings</button>
    </nav>
    <div class="side-foot">
      <div class="who">Signed in as <strong><?php echo htmlspecialchars($_SESSION['admin_display_name'] ?? ''); ?></strong></div>
      <button class="btn btn-outline btn-block btn-sm" onclick="location.href='logout.php'">Log out</button>
    </div>
  </aside>
  <main class="admin-main">
