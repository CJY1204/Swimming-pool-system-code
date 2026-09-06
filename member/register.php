<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Create Account';
$errors = [];
$username = $name = $email = $phone = '';

if (isMemberLoggedIn()) {
    header('Location: ' . BASE_URL . '/member/profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) $errors[] = 'Username must be 3-30 characters: letters, numbers, and underscores only.';
    if ($name === '') $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address (used for account verification, not login).';
    if ($phone === '' || !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors[] = 'Please enter a valid phone number.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT username, email FROM members WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        $existing = $check->fetch();
        if ($existing) {
            if ($existing['username'] === $username) $errors[] = 'That username is already taken.';
            if ($existing['email'] === $email) $errors[] = 'An account with that email already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO members (username, full_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $name, $email, $phone, $hash]);

        $_SESSION['member_id'] = $pdo->lastInsertId();
        $_SESSION['member_name'] = $name;
        $_SESSION['member_email'] = $email;
        $_SESSION['member_username'] = $username;

        $next = $_GET['next'] ?? (BASE_URL . '/member/profile.php');
        header('Location: ' . $next);
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-card">
  <h2>Create your account</h2>
  <p class="form-hint" style="margin-bottom:18px;">Free to join — you'll need this to book a pool session. You'll log in with your <strong>username</strong>; your email is only used for account verification.</p>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div>
  <?php endif; ?>

  <form method="POST" action="<?php echo BASE_URL; ?>/member/register.php<?php echo isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : ''; ?>">
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="letters, numbers, underscores" required>
    </div>
    <div class="form-group">
      <label for="full_name">Full name</label>
      <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($name); ?>" required>
    </div>
    <div class="form-group">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
    </div>
    <div class="form-group">
      <label for="phone">Phone number</label>
      <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required minlength="6">
    </div>
    <div class="form-group">
      <label for="confirm_password">Confirm password</label>
      <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
    </div>
    <button type="submit" class="btn btn-primary btn-block" style="margin-top:10px;">Create account</button>
  </form>

  <p class="form-hint" style="margin-top:18px; text-align:center;">
    Already have an account? <a href="<?php echo BASE_URL; ?>/login.php<?php echo isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : ''; ?>" style="color:var(--c-tide-dark); font-weight:700;">Log in</a>
  </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
