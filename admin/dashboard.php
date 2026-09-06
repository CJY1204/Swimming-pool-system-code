<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_generator.php';
require_once __DIR__ . '/includes/auth_check.php';

ensureSessionsExist($pdo);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$totalBookingsToday = $pdo->query("
    SELECT COUNT(*) AS c FROM bookings b
    JOIN sessions s ON s.id = b.session_id
    WHERE s.session_date = CURDATE() AND b.booking_status = 'confirmed'
")->fetch()['c'];

$ticketsToday = $pdo->query("
    SELECT COALESCE(SUM(b.quantity),0) AS c FROM bookings b
    JOIN sessions s ON s.id = b.session_id
    WHERE s.session_date = CURDATE() AND b.booking_status = 'confirmed'
")->fetch()['c'];

$totalMembers = $pdo->query("SELECT COUNT(*) AS c FROM members")->fetch()['c'];

$activeSessionsToday = $pdo->query("
    SELECT COUNT(*) AS c FROM sessions WHERE session_date = CURDATE() AND status = 'active'
")->fetch()['c'];

$todayBookings = $pdo->query("
    SELECT b.*, m.full_name, m.email, s.start_time, s.end_time, p.name AS pool_name
    FROM bookings b
    JOIN sessions s ON s.id = b.session_id
    JOIN pools p ON p.id = s.pool_id
    JOIN members m ON m.id = b.member_id
    WHERE s.session_date = CURDATE()
    ORDER BY b.created_at DESC
    LIMIT 10
")->fetchAll();

require_once __DIR__ . '/includes/shell_top.php';
?>

<div class="admin-topbar">
  <h1>Dashboard</h1>
  <div class="date"><?php echo date('l, j F Y'); ?></div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="top"><div class="stat-icon">📋</div></div><div class="value"><?php echo (int)$totalBookingsToday; ?></div><div class="label">Bookings today</div></div>
  <div class="stat-card"><div class="top"><div class="stat-icon">🎟️</div></div><div class="value"><?php echo (int)$ticketsToday; ?></div><div class="label">Tickets today</div></div>
  <div class="stat-card"><div class="top"><div class="stat-icon">👥</div></div><div class="value"><?php echo (int)$totalMembers; ?></div><div class="label">Registered members</div></div>
  <div class="stat-card"><div class="top"><div class="stat-icon">🕒</div></div><div class="value"><?php echo (int)$activeSessionsToday; ?></div><div class="label">Active sessions today</div></div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Today's bookings</h3>
    <a href="bookings.php" class="btn btn-outline btn-sm">View all bookings</a>
  </div>
  <table>
    <thead><tr><th>Reference</th><th>Member</th><th>Pool</th><th>Time</th><th>Qty</th><th>Status</th></tr></thead>
    <tbody>
      <?php if (empty($todayBookings)): ?>
        <tr class="empty-row"><td colspan="6" style="text-align:center; color:var(--c-mist); padding:30px;">No bookings for today yet.</td></tr>
      <?php else: foreach ($todayBookings as $b): ?>
        <tr>
          <td class="ref"><?php echo htmlspecialchars($b['booking_reference']); ?></td>
          <td><?php echo htmlspecialchars($b['full_name']); ?><br><small style="color:var(--c-mist);"><?php echo htmlspecialchars($b['email']); ?></small></td>
          <td><?php echo htmlspecialchars($b['pool_name']); ?></td>
          <td class="mono"><?php echo formatTimeLabel($b['start_time']); ?>–<?php echo formatTimeLabel($b['end_time']); ?></td>
          <td><?php echo (int)$b['quantity']; ?></td>
          <td><span class="badge badge-<?php echo $b['booking_status']; ?>"><?php echo ucfirst($b['booking_status']); ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/shell_bottom.php'; ?>
