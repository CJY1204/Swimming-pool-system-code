<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Manage Schedules';
$activeNav = 'schedules';
$errors = [];
$message = '';

$pools = $pdo->query("SELECT * FROM pools ORDER BY FIELD(size,'big','small'), id")->fetchAll();

// Add a new recurring schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $poolId = (int) ($_POST['pool_id'] ?? 0);
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $capOverride = trim($_POST['capacity_override'] ?? '');

    if ($poolId <= 0) $errors[] = 'Please choose a pool.';
    if ($start === '' || $end === '' || $start >= $end) $errors[] = 'End time must be after start time.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO pool_schedules (pool_id, start_time, end_time, capacity_override) VALUES (?, ?, ?, ?)");
        $stmt->execute([$poolId, $start, $end, $capOverride === '' ? null : (int) $capOverride]);
        $message = 'Schedule added. It will appear as a bookable session from tomorrow onward (today\'s sessions were already generated).';
    }
}

// Toggle active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_schedule_id'])) {
    $id = (int) $_POST['toggle_schedule_id'];
    $pdo->prepare("UPDATE pool_schedules SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    $message = 'Schedule status updated.';
}

// Delete a schedule entirely (existing generated sessions keep their history, just lose the schedule link)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule_id'])) {
    $id = (int) $_POST['delete_schedule_id'];
    $pdo->prepare("DELETE FROM pool_schedules WHERE id = ?")->execute([$id]);
    $message = 'Schedule deleted.';
}

$schedules = $pdo->query("
    SELECT ps.*, p.name AS pool_name, p.capacity AS pool_capacity
    FROM pool_schedules ps
    JOIN pools p ON p.id = ps.pool_id
    ORDER BY p.id, ps.start_time
")->fetchAll();

require_once __DIR__ . '/includes/shell_top.php';
?>

<div class="admin-topbar">
  <h1>Manage Schedules</h1>
  <div class="date"><?php echo date('l, j F Y'); ?></div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h3>Add a recurring time slot</h3></div>
  <form method="POST" class="inline-form">
    <input type="hidden" name="add_schedule" value="1">
    <div class="fg">
      <label>Pool</label>
      <select name="pool_id">
        <?php foreach ($pools as $p): ?>
          <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fg">
      <label>Start time</label>
      <input type="time" name="start_time" required>
    </div>
    <div class="fg">
      <label>End time</label>
      <input type="time" name="end_time" required>
    </div>
    <div class="fg">
      <label>Capacity override</label>
      <input type="number" name="capacity_override" min="1" placeholder="Uses pool default">
    </div>
    <button type="submit" class="btn btn-dark">Add slot</button>
  </form>
  <p class="form-hint" style="padding:0 22px 18px;">This defines a <strong>daily recurring</strong> time slot (e.g. every day 7:00–12:00) — it's not a one-off session. Actual bookable sessions for each date are generated automatically from this.</p>
</div>

<div class="panel">
  <div class="panel-head"><h3>All recurring slots</h3></div>
  <table>
    <thead><tr><th>Pool</th><th>Time</th><th>Capacity</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($schedules)): ?>
        <tr><td colspan="5" style="text-align:center; color:var(--c-mist); padding:30px;">No schedules yet.</td></tr>
      <?php else: foreach ($schedules as $s): ?>
        <tr>
          <td><?php echo htmlspecialchars($s['pool_name']); ?></td>
          <td class="mono"><?php echo formatTimeLabel($s['start_time']); ?>–<?php echo formatTimeLabel($s['end_time']); ?></td>
          <td><?php echo $s['capacity_override'] ?? $s['pool_capacity']; ?><?php echo $s['capacity_override'] ? '' : ' (default)'; ?></td>
          <td><span class="badge badge-<?php echo $s['is_active'] ? 'active' : 'closed'; ?>"><?php echo $s['is_active'] ? 'Active' : 'Paused'; ?></span></td>
          <td style="display:flex; gap:8px;">
            <form method="POST" style="margin:0;">
              <input type="hidden" name="toggle_schedule_id" value="<?php echo $s['id']; ?>">
              <button type="submit" class="btn btn-outline btn-sm"><?php echo $s['is_active'] ? 'Pause' : 'Resume'; ?></button>
            </form>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this recurring slot? Future sessions from it will stop generating.');">
              <input type="hidden" name="delete_schedule_id" value="<?php echo $s['id']; ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/shell_bottom.php'; ?>
