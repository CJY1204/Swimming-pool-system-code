<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Book a Session';

// Booking always requires a logged-in member. If not logged in, this
// bounces to login.php?next=<this exact URL> and comes straight
// back here once they've signed in.
requireMemberLogin();

$sessionId = isset($_GET['session']) ? (int) $_GET['session'] : (isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0);
$memberId = (int) $_SESSION['member_id'];

$stmt = $pdo->prepare("
    SELECT s.*, p.id AS pool_id, p.name AS pool_name, p.capacity AS pool_capacity, p.accent
    FROM sessions s
    JOIN pools p ON p.id = s.pool_id
    WHERE s.id = ?
");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session) {
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="empty-state"><p>Sorry, that session could not be found. <a href="' . BASE_URL . '/index.php">Go back</a>.</p></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Every booking is fixed at 1 ticket — one person, one spot.
$quantity = 1;
$errors = [];
$occ = occupancy((int) $session['capacity'], (int) $session['booked']);

// A member can only hold ONE active (confirmed) booking per pool at a time.
// Different pools are fine — up to 3 concurrent bookings, one per pool —
// but the same pool can't be double-booked until the existing one is cancelled.
$existingStmt = $pdo->prepare("
    SELECT b.booking_reference, s.session_date, s.start_time, s.end_time
    FROM bookings b
    JOIN sessions s ON s.id = b.session_id
    WHERE b.member_id = ? AND s.pool_id = ? AND b.booking_status = 'confirmed'
      AND (s.session_date > CURDATE() OR (s.session_date = CURDATE() AND s.end_time > CURTIME()))
    LIMIT 1
");
$existingStmt->execute([$memberId, $session['pool_id']]);
$existingBooking = $existingStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($session['status'] !== 'active') {
        $errors[] = 'This session is no longer open for booking.';
    }

    if (empty($errors) && !$existingBooking) {
        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare("SELECT capacity, booked, status FROM sessions WHERE id = ? FOR UPDATE");
            $lockStmt->execute([$sessionId]);
            $locked = $lockStmt->fetch();

            $remaining = $locked['capacity'] - $locked['booked'];

            if ($locked['status'] !== 'active') {
                throw new Exception('This session is no longer open for booking.');
            }
            if ($remaining < 1) {
                throw new Exception('This session just filled up. Please choose another slot.');
            }

            // Re-check the one-booking-per-pool rule inside the transaction too,
            // in case of a double-submit / race condition.
            $recheck = $pdo->prepare("
                SELECT COUNT(*) AS c FROM bookings b
                JOIN sessions s ON s.id = b.session_id
                WHERE b.member_id = ? AND s.pool_id = ? AND b.booking_status = 'confirmed'
                  AND (s.session_date > CURDATE() OR (s.session_date = CURDATE() AND s.end_time > CURTIME()))
            ");
            $recheck->execute([$memberId, $session['pool_id']]);
            if ($recheck->fetch()['c'] > 0) {
                throw new Exception('You already have an active booking for this pool.');
            }

            $reference = generateBookingReference();

            $insertStmt = $pdo->prepare("
                INSERT INTO bookings (booking_reference, session_id, member_id, quantity, booking_status)
                VALUES (?, ?, ?, 1, 'confirmed')
            ");
            $insertStmt->execute([$reference, $sessionId, $memberId]);

            $updateStmt = $pdo->prepare("UPDATE sessions SET booked = booked + 1 WHERE id = ?");
            $updateStmt->execute([$sessionId]);

            $pdo->commit();

            // Publish to SNS in the background (the trailing `&` means PHP
            // doesn't wait for it) so the confirmation page loads instantly.
            // SNS -> Lambda -> Gmail SMTP sends the actual email.
            $snsMessage = json_encode([
                'email'     => $_SESSION['member_email'],
                'name'      => $_SESSION['member_name'],
                'pool'      => $session['pool_name'],
                'date'      => formatDateLabel($session['session_date']),
                'time'      => formatTimeLabel($session['start_time']) . '–' . formatTimeLabel($session['end_time']),
                'reference' => $reference,
            ]);
            $snsMessageEscaped = escapeshellarg($snsMessage);
            $snsTopicArn = 'arn:aws:sns:us-east-1:082810551484:pool-booking-confirmations'; // <-- paste your pool-booking-confirmations ARN here
            shell_exec("aws sns publish --topic-arn {$snsTopicArn} --message {$snsMessageEscaped} --region us-east-1 > /dev/null 2>&1 &");

            header('Location: ' . BASE_URL . '/booking_success.php?ref=' . urlencode($reference));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-card">
  <h2>Book: <?php echo htmlspecialchars($session['pool_name']); ?></h2>
  <p class="slot-meta" style="color:var(--c-mist); margin-bottom:20px;">
    <?php echo formatDateLabel($session['session_date']); ?> ·
    <?php echo formatTimeLabel($session['start_time']); ?>–<?php echo formatTimeLabel($session['end_time']); ?>
    &nbsp;|&nbsp; <?php echo $occ['left']; ?> spots left
    &nbsp;|&nbsp; Free
  </p>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
    </div>
  <?php endif; ?>

  <?php if ($existingBooking): ?>
    <div class="alert alert-error">
      You already have an active booking for <strong><?php echo htmlspecialchars($session['pool_name']); ?></strong>
      (Ref: <?php echo htmlspecialchars($existingBooking['booking_reference']); ?>,
      <?php echo formatDateLabel($existingBooking['session_date']); ?> ·
      <?php echo formatTimeLabel($existingBooking['start_time']); ?>–<?php echo formatTimeLabel($existingBooking['end_time']); ?>).
      One active booking per pool at a time — cancel it in your profile first to book a different slot here.
    </div>
    <div style="display:flex; gap:10px;">
      <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/member/profile.php">Go to My Bookings</a>
      <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>/index.php">Back to Sessions</a>
    </div>
  <?php elseif ($occ['left'] <= 0): ?>
    <div class="alert alert-error">This session is fully booked. Please choose another slot.</div>
    <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>/index.php">Back to Sessions</a>
  <?php else: ?>
    <form method="POST" action="<?php echo BASE_URL; ?>/book.php">
      <input type="hidden" name="session_id" value="<?php echo (int) $session['id']; ?>">

      <div class="form-group">
        <div class="form-hint">Booking as <strong><?php echo htmlspecialchars($_SESSION['member_name']); ?></strong> (<?php echo htmlspecialchars($_SESSION['member_email']); ?>)</div>
      </div>

      <div class="summary-box">
        <div class="summary-row"><span>Tickets</span><span>1 (one booking per person)</span></div>
        <div class="summary-row total"><span>Cost</span><span>Free 🎉</span></div>
      </div>

      <div class="modal-actions" style="margin-top:24px;">
        <button type="submit" class="btn btn-primary">Confirm Booking</button>
        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/index.php">Cancel</a>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>