<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle = 'Add Task';
$activePage = 'insert';

$message = '';
$messageType = '';
$objective = '';
$selectedCategoryId = 0;
$privateChecked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $objective = trim((string) ($_POST['objective'] ?? ''));
    $selectedCategoryId = (int) ($_POST['category'] ?? 0);
    $privateChecked = !empty($_POST['private']);
    $private = $privateChecked ? 1 : 0;

    if ($objective === '') {
        $message = 'Please enter a task.';
        $messageType = 'danger';
    } elseif ($selectedCategoryId <= 0) {
        $message = 'Please choose a category.';
        $messageType = 'danger';
    } else {
        $stmt = $db->prepare("INSERT INTO todos (objective, category, created_at, updated_at, completed, private) VALUES (?, ?, NOW(), NOW(), 'No', ?)");
        $stmt->bind_param('sii', $objective, $selectedCategoryId, $private);
        if ($stmt->execute()) {
            $message = 'Task added successfully!';
            $messageType = 'success';
            $objective = '';
            $privateChecked = false;
        } else {
            $message = 'Error adding task. Please try again.';
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

$catStmt = $db->prepare("SELECT id, category FROM categories ORDER BY category");
$catStmt->execute();
$categories = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$catStmt->close();

include __DIR__ . '/partials/header.php';
?>
<div class="sp-card">
  <div class="sp-card-header">
    <div class="sp-card-title"><i class="fas fa-plus"></i> Add a New Task</div>
  </div>
  <div class="sp-card-body">
    <?php if ($message): ?>
      <div class="sp-alert sp-alert-<?php echo htmlspecialchars($messageType); ?>" style="margin-bottom:1rem;">
        <?php if ($messageType === 'danger'): ?>
          <i class="fas fa-exclamation-triangle"></i>
        <?php elseif ($messageType === 'success'): ?>
          <i class="fas fa-check-circle"></i>
        <?php else: ?>
          <i class="fas fa-info-circle"></i>
        <?php endif; ?>
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>
    <form method="post">
      <div class="sp-form-group">
        <label class="sp-label" for="objective"><i class="fas fa-tasks"></i> Task</label>
        <textarea id="objective" name="objective" class="sp-textarea" placeholder="Describe your task..."><?php echo htmlspecialchars($objective); ?></textarea>
      </div>
      <div class="sp-form-group">
        <label class="sp-label" for="category">Category</label>
        <select id="category" name="category" class="sp-select">
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int) $cat['id']; ?>" <?php if ($selectedCategoryId === (int) $cat['id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($cat['category']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="sp-form-group" style="margin-top:1rem;">
        <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
          <input type="checkbox" name="private" id="private" value="1" <?php if ($privateChecked) echo 'checked'; ?>>
          <i class="fas fa-eye-slash"></i> Private (hide from OBS overlay)
        </label>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
        <button type="submit" class="sp-btn sp-btn-primary">Add</button>
        <a href="dashboard.php" class="sp-btn sp-btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
