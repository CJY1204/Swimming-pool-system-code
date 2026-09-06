<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'All Bookings';
$activeNav = 'bookings';
$message = '';

// Cancel a booking (admin can cancel any booking)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $bookingId = (int) $_POST['cancel_booking_id'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND booking_status = 'confirmed' FOR UPDATE");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        if ($booking) {
            $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?")->execute([$bookingId]);
            $pdo->prepare("UPDATE sessions SET booked = GREATEST(0, booked - ?) WHERE id = ?")
                ->execute([$booking['quantity'], $booking['session_id']]);
            $message = 'Booking cancelled and spots released.';
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Could not cancel booking.';
    }
}

$dateFilter = $_GET['date'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? 'all';

$sql = "
    SELECT b.*, s.session_date, s.start_time, s.end_time, p.name AS pool_name, m.full_name, m.email, m.phone
    FROM bookings b
    JOIN sessions s ON s.id = b.session_id
    JOIN pools p ON p.id = s.pool_id
    JOIN members m ON m.id = b.member_id
    WHERE 1=1
";
$params = [];
if ($dateFilter !== '') {
    $sql .= " AND s.session_date = ?";
    $params[] = $dateFilter;
}
if ($statusFilter !== 'all') {
    $sql .= " AND b.booking_status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY s.start_time ASC, b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/includes/shell_top.php';
?>

<div class="admin-topbar">
  <h1>All Bookings</h1>
  <div class="date"><?php echo date('l, j F Y'); ?></div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head">
    <h3>Bookings</h3>
    <form method="GET" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>" onchange="this.form.submit()">
      <div class="panel-filters">
        <a href="?date=<?php echo urlencode($dateFilter); ?>&status=all" class="chip <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
        <a href="?date=<?php echo urlencode($dateFilter); ?>&status=confirmed" class="chip <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
        <a href="?date=<?php echo urlencode($dateFilter); ?>&status=cancelled" class="chip <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
      </div>
      <a href="bookings.php" class="btn btn-outline btn-sm">All dates</a>
    </form>
  </div>
  <table>
    <thead><tr><th>Reference</th><th>Member</th><th>Pool</th><th>Session</th><th>Qty</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="7" style="text-align:center; color:var(--c-mist); padding:34px;">No bookings match this filter.</td></tr>
      <?php else: foreach ($bookings as $b): ?>
        <tr>
          <td class="ref"><?php echo htmlspecialchars($b['booking_reference']); ?></td>
          <td><?php echo htmlspecialchars($b['full_name']); ?><br><small style="color:var(--c-mist);"><?php echo htmlspecialchars($b['email']); ?> · <?php echo htmlspecialchars($b['phone']); ?></small></td>
          <td><?php echo htmlspecialchars($b['pool_name']); ?></td>
          <td class="mono"><?php echo formatDateLabel($b['session_date']); ?><br><?php echo formatTimeLabel($b['start_time']); ?>–<?php echo formatTimeLabel($b['end_time']); ?></td>
          <td><?php echo (int) $b['quantity']; ?></td>
          <td><span class="badge badge-<?php echo $b['booking_status']; ?>"><?php echo ucfirst($b['booking_status']); ?></span></td>
          <td>
            <?php if ($b['booking_status'] === 'confirmed'): ?>
              <form method="POST" onsubmit="return confirm('Cancel this booking?');" style="margin:0;">
                <input type="hidden" name="cancel_booking_id" value="<?php echo $b['id']; ?>">
                <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
              </form>
            <?php else: ?>&mdash;<?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/shell_bottom.php'; ?>
