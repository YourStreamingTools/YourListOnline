<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle = 'Remove Task';
$activePage = 'remove';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['todo_id'])) {
    $todo_id = (int) $_POST['todo_id'];
    $stmt = $db->prepare("DELETE FROM todos WHERE id = ?");
    $stmt->bind_param('i', $todo_id);
    $stmt->execute();
    $stmt->close();
    header('Location: remove.php' . (isset($_GET['category']) ? '?category=' . urlencode($_GET['category']) : ''));
    exit;
}

$categoryFilterRaw = $_GET['category'] ?? 'all';
$categoryFilter = ($categoryFilterRaw === 'all') ? 'all' : (int) $categoryFilterRaw;

if ($categoryFilter === 'all') {
    $stmt = $db->prepare("SELECT t.*, c.category AS category_name FROM todos t LEFT JOIN categories c ON t.category = c.id ORDER BY t.id ASC");
} else {
    $stmt = $db->prepare("SELECT t.*, c.category AS category_name FROM todos t LEFT JOIN categories c ON t.category = c.id WHERE t.category = ? ORDER BY t.id ASC");
    $stmt->bind_param('i', $categoryFilter);
}
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$num_rows = count($tasks);

$catStmt = $db->prepare("SELECT id, category FROM categories ORDER BY category");
$catStmt->execute();
$categories = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$catStmt->close();

include __DIR__ . '/partials/header.php';
?>
<div class="sp-card">
  <div class="sp-card-header">
    <div class="sp-card-title"><i class="fas fa-trash"></i> Remove a Task</div>
  </div>
  <div class="sp-card-body">
    <?php if ($num_rows < 1): ?>
      <div class="sp-alert sp-alert-info">
        <i class="fas fa-tasks fa-2x" style="color:var(--blue);"></i>
        <div>
          <strong>Your to-do list is empty!</strong>
          <p style="margin-bottom:0;">You can't remove any tasks because there aren't any yet.</p>
        </div>
      </div>
    <?php else: ?>
      <div style="display:flex; align-items:flex-end; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px;">
          <label for="searchInput" class="sp-label">Search Tasks</label>
          <input class="sp-input" type="text" id="searchInput" placeholder="Search todos" onkeyup="spFilterCards()">
        </div>
        <div style="min-width:200px;">
          <label for="categoryFilter" class="sp-label">Filter by Category</label>
          <select id="categoryFilter" class="sp-select">
            <option value="all" <?php if ($categoryFilter === 'all') echo 'selected'; ?>>All</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo (int) $cat['id']; ?>" <?php if ($categoryFilter === (int) $cat['id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($cat['category']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <h2 style="font-size:1rem; font-weight:700; margin-bottom:1rem;">Pick a task to remove:</h2>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:1rem;">
        <?php foreach ($tasks as $row): ?>
          <div class="sp-card sp-task" style="margin-bottom:0;">
            <div class="sp-card-body" style="display:flex; flex-direction:column; gap:0.75rem; height:100%;">
              <div style="flex:1;">
                <p class="sp-task-title" style="font-weight:600; margin-bottom:0.3rem;"><?php echo htmlspecialchars($row['objective']); ?></p>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.3rem; display:flex; align-items:center; gap:0.3rem;">
                  <i class="fas fa-folder"></i>
                  <?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?>
                </p>
                <?php echo ($row['completed'] === 'Yes')
                  ? '<span class="sp-badge sp-badge-green">Completed</span>'
                  : '<span class="sp-badge sp-badge-amber">Not completed</span>'; ?>
              </div>
              <form method="POST" style="margin-bottom:0;" class="remove-task-form">
                <input type="hidden" name="todo_id" value="<?php echo (int) $row['id']; ?>">
                <button type="button" class="sp-btn sp-btn-danger sp-btn-sm remove-task-btn" style="width:100%;">
                  <i class="fas fa-trash"></i> Remove
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
$pageScripts = <<<'HTML'
<script>
document.getElementById('categoryFilter')?.addEventListener('change', function() {
  window.location.href = 'remove.php?category=' + encodeURIComponent(this.value);
});
function spFilterCards() {
  const q = (document.getElementById('searchInput').value || '').toLowerCase();
  document.querySelectorAll('.sp-task').forEach(card => {
    const text = (card.querySelector('.sp-task-title')?.textContent || '').toLowerCase();
    card.style.display = text.includes(q) ? '' : 'none';
  });
}
document.querySelectorAll('.remove-task-btn').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    const form = btn.closest('form');
    Swal.fire({
      title: 'Are you sure?',
      text: 'This will permanently remove the task.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Yes, remove it!'
    }).then(r => { if (r.isConfirmed) form.submit(); });
  });
});
</script>
HTML;
include __DIR__ . '/partials/footer.php';
