<?php
/**
 * IMPORTANT: Run this file ONCE in your browser
 * (e.g. http://localhost/pool-booking-php/admin/setup_admin.php)
 * to create your admin account, then DELETE this file from your server.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$message = '';
$done = false;

$existing = $pdo->query("SELECT COUNT(*) AS c FROM admins")->fetch();

if ($existing['c'] > 0) {
    $message = 'An admin account already exists. For security, this setup script is now disabled. Delete admin/setup_admin.php from your server.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminId = trim($_POST['admin_id'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($adminId === '' || $displayName === '' || strlen($password) < 6) {
        $message = 'Please fill in Admin ID, display name, and a password of at least 6 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (admin_id, display_name, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$adminId, $displayName, $hash]);
        $done = true;
        $message = 'Admin account created! You can now delete this file and log in at the main Log In page using this Admin ID.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Setup</title>
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/site.css">
</head>
<body>
<main class="container">
    <div class="form-card">
        <h2>Create Admin Account</h2>
        <?php if ($message): ?>
            <div class="alert <?php echo $done ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!$done && $existing['c'] == 0): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="admin_id">Admin ID</label>
                    <input type="text" id="admin_id" name="admin_id" placeholder="e.g. admin01" required>
                </div>
                <div class="form-group">
                    <label for="display_name">Display name</label>
                    <input type="text" id="display_name" name="display_name" placeholder="e.g. Front Desk" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;">Create Admin Account</button>
            </form>
        <?php elseif ($done): ?>
            <a class="btn btn-primary btn-block" href="<?php echo BASE_URL; ?>/login.php">Go to Log In</a>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
