<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>SplashPoint</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/tokens.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/site.css">
</head>
<body>

<nav class="site-nav">
  <div class="container">
    <a href="<?php echo BASE_URL; ?>/index.php" class="brand">
      <span class="brand-mark">🌊</span> SplashPoint
    </a>
    <div class="nav-links">
      <a href="<?php echo BASE_URL; ?>/index.php#pools">Pools</a>
      <?php if (isMemberLoggedIn()): ?>
        <a href="<?php echo BASE_URL; ?>/member/profile.php">My Profile</a>
        <a href="<?php echo BASE_URL; ?>/member/logout.php">Log out</a>
      <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/login.php">Log in</a>
        <a href="<?php echo BASE_URL; ?>/member/register.php">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container" style="padding-top:40px; padding-bottom:60px;">
