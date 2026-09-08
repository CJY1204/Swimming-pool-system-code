<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'My Profile';
requireMemberLogin();

$memberId = (int) $_SESSION['member_id'];
$message = '';
$errors = [];

// Update profile (name, email, phone; username stays fixed as the login identifier)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($phone === '' || !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors[] = 'Please enter a valid phone number.';

    if (empty($errors)) {
        $dupe = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
        $dupe->execute([$email, $memberId]);
        if ($dupe->fetch()) {
            $errors[] = 'Another account is already using that email.';
        }
    }

    if (empty($errors)) {
        $pdo->prepare("UPDATE members SET full_name = ?, email = ?, phone = ? WHERE id = ?")->execute([$name, $email, $phone, $memberId]);
        $_SESSION['member_name'] = $name;
        $_SESSION['member_email'] = $email;
        $message = 'Profile updated.';
    }
}

// Cancel a booking (only your own, only if still confirmed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $bookingId = (int) $_POST['cancel_booking_id'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND member_id = ? AND booking_status = 'confirmed' FOR UPDATE");
        $stmt->execute([$bookingId, $memberId]);
        $booking = $stmt->fetch();

        if ($booking) {
            $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?")->execute([$bookingId]);
            $pdo->prepare("UPDATE sessions SET booked = GREATEST(0, booked - ?) WHERE id = ?")
                ->execute([$booking['quantity'], $booking['session_id']]);
            $message = 'Booking cancelled.';
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Could not cancel that booking.';
    }
}

$member = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$member->execute([$memberId]);
$member = $member->fetch();

$bookings = $pdo->prepare("
    SELECT b.*, s.session_date, s.start_time, s.end_time, p.name AS pool_name
    FROM bookings b
    JOIN sessions s ON s.id = b.session_id
    JOIN pools p ON p.id = s.pool_id
    WHERE b.member_id = ?
    ORDER BY b.created_at DESC
");
$bookings->execute([$memberId]);
$bookings = $bookings->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-head">
  <div class="eyebrow">MY ACCOUNT</div>
  <h2>Hi, <?php echo htmlspecialchars($member['full_name']); ?></h2>
</div>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div>
<?php endif; ?>

<div class="form-card" style="margin-left:0;">
  <h3 style="margin-bottom:16px;">Your details</h3>
  <form method="POST">
    <input type="hidden" name="update_profile" value="1">
    <div class="form-group">
      <label for="full_name">Full name</label>
      <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
    </div>
    <div class="form-group">
      <label for="username">Username (used to log in)</label>
      <input type="text" id="username" value="<?php echo htmlspecialchars($member['username']); ?>" disabled>
    </div>
    <div class="form-group">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
    </div>
    <div class="form-group">
      <label for="phone">Phone number</label>
      <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Save changes</button>
  </form>
</div>

<h3 style="margin:36px 0 16px;">Your bookings</h3>
<?php if (empty($bookings)): ?>
  <div class="empty-state"><p>You haven't booked a session yet. <a href="<?php echo BASE_URL; ?>/index.php">Browse pools</a>.</p></div>
<?php else: ?>
  <table>
    <thead>
      <tr><th>Reference</th><th>Pool</th><th>Session</th><th>Qty</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $b): $displayStatus = getBookingDisplayStatus($b['booking_status'], $b['session_date'], $b['end_time']); ?>
        <tr>
          <td class="ref" style="font-family:var(--font-mono); font-weight:700;"><?php echo htmlspecialchars($b['booking_reference']); ?></td>
          <td><?php echo htmlspecialchars($b['pool_name']); ?></td>
          <td><?php echo formatDateLabel($b['session_date']); ?> · <?php echo formatTimeLabel($b['start_time']); ?>–<?php echo formatTimeLabel($b['end_time']); ?></td>
          <td><?php echo (int) $b['quantity']; ?></td>
          <td><span class="badge badge-<?php echo $displayStatus; ?>"><?php echo ucfirst($displayStatus); ?></span></td>
          <td>
            <?php if ($displayStatus === 'confirmed'): ?>
              <form method="POST" onsubmit="return confirm('Cancel this booking?');" style="margin:0;">
                <input type="hidden" name="cancel_booking_id" value="<?php echo (int) $b['id']; ?>">
                <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
              </form>
            <?php else: ?>&mdash;<?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>