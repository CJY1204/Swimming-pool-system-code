<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Booking Confirmed';
$reference = $_GET['ref'] ?? '';
$booking = null;

if ($reference !== '') {
    $stmt = $pdo->prepare("
        SELECT b.*, s.session_date, s.start_time, s.end_time, p.name AS pool_name, m.full_name, m.email
        FROM bookings b
        JOIN sessions s ON s.id = b.session_id
        JOIN pools p ON p.id = s.pool_id
        JOIN members m ON m.id = b.member_id
        WHERE b.booking_reference = ?
    ");
    $stmt->execute([$reference]);
    $booking = $stmt->fetch();
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($booking): ?>
    <div class="success-box">
        <div style="font-size:3rem;">✅</div>
        <h2>Booking Confirmed!</h2>
        <p>Thank you, <?php echo htmlspecialchars($booking['full_name']); ?>. Your reservation is set.</p>
        <div class="ref"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
        <p>
            <?php echo htmlspecialchars($booking['pool_name']); ?><br>
            <?php echo formatDateLabel($booking['session_date']); ?><br>
            <?php echo formatTimeLabel($booking['start_time']); ?> &ndash; <?php echo formatTimeLabel($booking['end_time']); ?><br>
            Tickets: <?php echo (int) $booking['quantity']; ?> &nbsp;|&nbsp; Cost: Free
        </p>
        <p class="form-hint">A confirmation has been recorded under <?php echo htmlspecialchars($booking['email']); ?>. You can find this booking any time in your profile.</p>
        <div style="display:flex; gap:12px; justify-content:center; margin-top:20px;">
            <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/index.php">Book Another Session</a>
            <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/member/profile.php">View My Bookings</a>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <p>We couldn't find that booking. <a href="<?php echo BASE_URL; ?>/index.php">Return to homepage</a>.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
