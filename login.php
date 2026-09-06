<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Log In';
$error = '';
$username = '';

// Already logged in? Send them where they'd expect to go.
if (isMemberLoggedIn()) {
    header('Location: ' . BASE_URL . '/member/profile.php');
    exit;
}
if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $next = $_POST['next'] ?? (BASE_URL . '/member/profile.php');

    // Check staff accounts first (matched by Admin ID)
    $adminStmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $adminStmt->execute([$username]);
    $admin = $adminStmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_display_name'] = $admin['display_name'];
        $_SESSION['admin_login_id'] = $admin['admin_id'];
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }

    // Then check member accounts (matched by username, not email)
    $memberStmt = $pdo->prepare("SELECT * FROM members WHERE username = ?");
    $memberStmt->execute([$username]);
    $member = $memberStmt->fetch();

    if ($member && password_verify($password, $member['password_hash'])) {
        $_SESSION['member_id'] = $member['id'];
        $_SESSION['member_name'] = $member['full_name'];
        $_SESSION['member_email'] = $member['email'];
        $_SESSION['member_username'] = $member['username'];
        header('Location: ' . $next);
        exit;
    }

    // Deliberately generic — doesn't reveal whether the username exists
    // or which type of account (member/staff) it belongs to.
    $error = 'Incorrect username or password.';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-card">
  <h2>Log in</h2>
  <p class="form-hint" style="margin-bottom:18px;">Members and staff both log in here.</p>

  <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" action="<?php echo BASE_URL; ?>/login.php">
    <input type="hidden" name="next" value="<?php echo htmlspecialchars($_GET['next'] ?? (BASE_URL . '/member/profile.php')); ?>">
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block" style="margin-top:10px;">Log in</button>
  </form>

  <p class="form-hint" style="margin-top:18px; text-align:center;">
    New here? <a href="<?php echo BASE_URL; ?>/member/register.php<?php echo isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : ''; ?>" style="color:var(--c-tide-dark); font-weight:700;">Create an account</a>
  </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
