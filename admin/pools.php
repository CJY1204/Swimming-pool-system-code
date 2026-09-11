<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Manage Pools';
$activeNav = 'pools';
$errors = [];
$message = '';

// ---- Create or update a pool ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pool'])) {
    $poolId = (int) ($_POST['pool_id'] ?? 0);
    $slug = trim($_POST['slug'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $depth = trim($_POST['depth_label'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $size = $_POST['size'] ?? 'small';
    $accent = $_POST['accent'] ?? 'deep';

    if ($slug === '' || !preg_match('/^[a-z0-9\-]+$/', $slug)) $errors[] = 'Slug must be lowercase letters, numbers, and hyphens only.';
    if ($name === '') $errors[] = 'Please enter a pool name.';
    if ($capacity < 1) $errors[] = 'Capacity must be at least 1.';
    if (!in_array($size, ['big', 'small'], true)) $errors[] = 'Invalid size.';
    if (!in_array($accent, ['deep', 'coral', 'tide'], true)) $errors[] = 'Invalid accent.';

    // Keep the pool's existing photo by default — only overwritten below if a new file was uploaded
    $photo = '';
    if ($poolId > 0) {
        $existing = $pdo->prepare("SELECT photo FROM pools WHERE id = ?");
        $existing->execute([$poolId]);
        $photo = $existing->fetchColumn() ?: '';
    }

    if (empty($errors) && !empty($_FILES['photo_file']['name'])) {
        $file = $_FILES['photo_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Photo upload failed. Please try again.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Photo must be under 5MB.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $errors[] = 'Photo must be a JPG, PNG, or WEBP file.';
            } else {
                $destDir = __DIR__ . '/../images/';
                $newFilename = $slug . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $destDir . $newFilename)) {
                    $photo = $newFilename;
                } else {
                    $errors[] = 'Could not save the photo — check that the images folder is writable.';
                }
            }
        }
    }

    if (empty($errors)) {
        if ($poolId > 0) {
            $stmt = $pdo->prepare("
                UPDATE pools SET slug=?, name=?, tagline=?, description=?, depth_label=?, capacity=?, size=?, accent=?, photo=?
                WHERE id=?
            ");
            $stmt->execute([$slug, $name, $tagline, $description, $depth, $capacity, $size, $accent, $photo, $poolId]);
            $message = 'Pool updated.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO pools (slug, name, tagline, description, depth_label, capacity, size, accent, photo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$slug, $name, $tagline, $description, $depth, $capacity, $size, $accent, $photo]);
            $message = 'New pool created. Don\'t forget to add its schedule on the Schedules page.';
        }
    }
}

// ---- Toggle active/inactive (soft delete — keeps booking history intact) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active_id'])) {
    $id = (int) $_POST['toggle_active_id'];
    $pdo->prepare("UPDATE pools SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    $message = 'Pool status updated.';
}

$pools = $pdo->query("SELECT * FROM pools ORDER BY FIELD(size,'big','small'), id")->fetchAll();

// Pre-fill the form if editing
$editPool = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pools WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editPool = $stmt->fetch();
}

require_once __DIR__ . '/includes/shell_top.php';
?>

<div class="admin-topbar">
  <h1>Manage Pools</h1>
  <div class="date"><?php echo date('l, j F Y'); ?></div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h3><?php echo $editPool ? 'Edit pool: ' . htmlspecialchars($editPool['name']) : 'Add a new pool'; ?></h3></div>
  <form method="POST" enctype="multipart/form-data" style="padding:20px 22px; display:grid; grid-template-columns:1fr 1fr; gap:16px;">
    <input type="hidden" name="save_pool" value="1">
    <input type="hidden" name="pool_id" value="<?php echo $editPool['id'] ?? ''; ?>">

    <div class="form-group" style="margin:0;">
      <label>Name</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($editPool['name'] ?? ''); ?>" required>
    </div>
    <div class="form-group" style="margin:0;">
      <label>Slug (used for /images/&lt;slug&gt;.jpg)</label>
      <input type="text" name="slug" value="<?php echo htmlspecialchars($editPool['slug'] ?? ''); ?>" placeholder="e.g. deep-blue" required>
    </div>
    <div class="form-group" style="margin:0;">
      <label>Tagline</label>
      <input type="text" name="tagline" value="<?php echo htmlspecialchars($editPool['tagline'] ?? ''); ?>">
    </div>
    <div class="form-group" style="margin:0;">
      <label>Pool photo</label>
      <div class="upload-dropzone" id="uploadDropzone">
        <input type="file" id="photoFileInput" name="photo_file" accept="image/jpeg,image/png,image/webp" style="display:none;">
        <div id="uploadDropzoneContent">
          <?php if (!empty($editPool['photo'])): ?>
            <img src="<?php echo BASE_URL; ?>/images/<?php echo htmlspecialchars($editPool['photo']); ?>" alt="Current photo" class="upload-preview-img" onerror="this.remove()">
            <p class="upload-hint-text">Current photo — click or drag to replace</p>
          <?php else: ?>
            <div class="upload-icon">📷</div>
            <p class="upload-hint-text">Click to upload or drag &amp; drop<br>JPG, PNG, or WEBP — max 5MB</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="form-group" style="grid-column:1 / -1; margin:0;">
      <label>Description</label>
      <input type="text" name="description" value="<?php echo htmlspecialchars($editPool['description'] ?? ''); ?>">
    </div>
    <div class="form-group" style="margin:0;">
      <label>Depth label</label>
      <input type="text" name="depth_label" value="<?php echo htmlspecialchars($editPool['depth_label'] ?? ''); ?>" placeholder="e.g. 1.2m – 2.0m">
    </div>
    <div class="form-group" style="margin:0;">
      <label>Capacity per session</label>
      <input type="number" name="capacity" min="1" value="<?php echo htmlspecialchars($editPool['capacity'] ?? '20'); ?>" required>
    </div>
    <div class="form-group" style="margin:0;">
      <label>Card size</label>
      <select name="size">
        <option value="big" <?php echo (($editPool['size'] ?? '') === 'big') ? 'selected' : ''; ?>>Big (full-width card)</option>
        <option value="small" <?php echo (($editPool['size'] ?? 'small') === 'small') ? 'selected' : ''; ?>>Small</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label>Accent color</label>
      <select name="accent">
        <option value="deep" <?php echo (($editPool['accent'] ?? '') === 'deep') ? 'selected' : ''; ?>>Deep blue</option>
        <option value="coral" <?php echo (($editPool['accent'] ?? '') === 'coral') ? 'selected' : ''; ?>>Coral</option>
        <option value="tide" <?php echo (($editPool['accent'] ?? '') === 'tide') ? 'selected' : ''; ?>>Tide teal</option>
      </select>
    </div>

    <div style="grid-column:1 / -1; display:flex; gap:10px;">
      <button type="submit" class="btn btn-dark"><?php echo $editPool ? 'Save changes' : 'Create pool'; ?></button>
      <?php if ($editPool): ?><a class="btn btn-outline" href="pools.php">Cancel edit</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h3>All pools</h3></div>
  <table>
    <thead><tr><th>Name</th><th>Size</th><th>Capacity</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($pools as $p): ?>
        <tr>
          <td><?php echo htmlspecialchars($p['name']); ?><br><small style="color:var(--c-mist);"><?php echo htmlspecialchars($p['slug']); ?></small></td>
          <td><?php echo ucfirst($p['size']); ?></td>
          <td><?php echo (int) $p['capacity']; ?></td>
          <td><span class="badge badge-<?php echo $p['is_active'] ? 'active' : 'closed'; ?>"><?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
          <td style="display:flex; gap:8px;">
            <a href="pools.php?edit=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="toggle_active_id" value="<?php echo $p['id']; ?>">
              <button type="submit" class="btn <?php echo $p['is_active'] ? 'btn-danger' : 'btn-dark'; ?> btn-sm"><?php echo $p['is_active'] ? 'Deactivate' : 'Reactivate'; ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p class="form-hint" style="padding:0 22px 20px;">Deactivating hides a pool from the homepage but keeps its booking history intact — pools aren't hard-deleted so past bookings always stay traceable.</p>
</div>

<?php require_once __DIR__ . '/includes/shell_bottom.php'; ?>

<script>
(function () {
  const dropzone = document.getElementById('uploadDropzone');
  const fileInput = document.getElementById('photoFileInput');
  const content = document.getElementById('uploadDropzoneContent');
  if (!dropzone) return;

  dropzone.addEventListener('click', () => fileInput.click());

  ['dragover', 'dragleave', 'drop'].forEach(evt => {
    dropzone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); });
  });
  dropzone.addEventListener('dragover', () => dropzone.classList.add('dragover'));
  dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
  dropzone.addEventListener('drop', e => {
    dropzone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
      showPreview(fileInput.files[0]);
    }
  });
  fileInput.addEventListener('change', () => {
    if (fileInput.files.length) showPreview(fileInput.files[0]);
  });

  function showPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
      content.innerHTML =
        '<img src="' + e.target.result + '" class="upload-preview-img">' +
        '<p class="upload-hint-text">' + file.name + ' — click or drag to change</p>';
    };
    reader.readAsDataURL(file);
  }
})();
</script>