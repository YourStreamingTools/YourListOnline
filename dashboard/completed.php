<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle = 'Completed';
$activePage = 'completed';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task_id = (int) $_POST['task_id'];
    $stmt = $db->prepare("UPDATE todos SET completed = 'Yes', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $task_id);
    $stmt->execute();
    $stmt->close();
    header('Location: completed.php' . (isset($_GET['category']) ? '?category=' . urlencode($_GET['category']) : ''));
    exit;
}

$categoryFilterRaw = $_GET['category'] ?? 'all';
$categoryFilter = ($categoryFilterRaw === 'all') ? 'all' : (int) $categoryFilterRaw;

if ($categoryFilter === 'all') {
    $stmt = $db->prepare("SELECT t.*, c.category AS category_name FROM todos t LEFT JOIN categories c ON t.category = c.id WHERE t.completed = 'No' ORDER BY t.id ASC");
} else {
    $stmt = $db->prepare("SELECT t.*, c.category AS category_name FROM todos t LEFT JOIN categories c ON t.category = c.id WHERE t.category = ? AND t.completed = 'No' ORDER BY t.id ASC");
    $stmt->bind_param('i', $categoryFilter);
}
$stmt->execute();
$incompleteTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$num_rows = count($incompleteTasks);

$catStmt = $db->prepare("SELECT id, category FROM categories ORDER BY category");
$catStmt->execute();
$categories = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$catStmt->close();

include __DIR__ . '/partials/header.php';
?>
<div class="sp-card">
  <div class="sp-card-header">
    <div class="sp-card-title"><i class="fas fa-check-double"></i> Mark Tasks as Completed</div>
  </div>
  <div class="sp-card-body">
    <?php if ($num_rows < 1): ?>
      <div class="sp-alert sp-alert-info">
        <i class="fas fa-tasks fa-2x" style="color:var(--blue);"></i>
        <div>
          <strong>No incomplete tasks!</strong>
          <p style="margin-bottom:0;">Add a task or pick a different category to see more.</p>
        </div>
      </div>
    <?php else: ?>
      <div style="display:flex; align-items:flex-end; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px;">
          <label for="searchInput" class="sp-label">Search Tasks</label>
          <input class="sp-input" type="text" id="searchInput" placeholder="Search objectives" onkeyup="spFilterCards()">
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
      <p style="margin-bottom:1rem; color:var(--text-secondary);">Number of incomplete tasks: <?php echo $num_rows; ?></p>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:1rem;" id="taskCardList">
        <?php foreach ($incompleteTasks as $row): ?>
          <div class="sp-card sp-task" style="margin-bottom:0;">
            <div class="sp-card-body" style="display:flex; flex-direction:column; gap:0.75rem; height:100%;">
              <div style="flex:1;">
                <p class="sp-task-title" style="font-weight:600; margin-bottom:0.3rem;"><?php echo htmlspecialchars($row['objective']); ?></p>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0; display:flex; align-items:center; gap:0.3rem;">
                  <i class="fas fa-folder"></i>
                  <?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?>
                </p>
              </div>
              <form method="post" action="completed.php" style="margin-bottom:0;" class="mark-completed-form">
                <input type="hidden" name="task_id" value="<?php echo (int) $row['id']; ?>">
                <button type="button" class="sp-btn sp-btn-success sp-btn-sm mark-completed-btn" style="width:100%;">
                  <i class="fas fa-check"></i> Mark as completed
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
  window.location.href = 'completed.php?category=' + encodeURIComponent(this.value);
});
function spFilterCards() {
  const q = (document.getElementById('searchInput').value || '').toLowerCase();
  document.querySelectorAll('.sp-task').forEach(card => {
    const text = (card.querySelector('.sp-task-title')?.textContent || '').toLowerCase();
    card.style.display = text.includes(q) ? '' : 'none';
  });
}
document.querySelectorAll('.mark-completed-btn').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    const form = btn.closest('form');
    Swal.fire({
      title: 'Mark as completed?',
      text: 'This will mark the task as completed.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Yes, mark completed!'
    }).then(r => { if (r.isConfirmed) form.submit(); });
  });
});
</script>
HTML;
include __DIR__ . '/partials/footer.php';
