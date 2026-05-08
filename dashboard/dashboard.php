<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$categoryFilterRaw = $_GET['category'] ?? 'all';
$categoryFilter = ($categoryFilterRaw === 'all') ? 'all' : (int) $categoryFilterRaw;

$searchKeyword = trim((string) ($_GET['search'] ?? ''));

$base = "SELECT t.id, t.objective, t.category, t.created_at, t.updated_at, t.completed,
                COALESCE(t.private, 0) AS private,
                c.category AS category_name
         FROM todos t
         LEFT JOIN categories c ON t.category = c.id
         WHERE 1=1";
$types = '';
$params = [];

if ($categoryFilter !== 'all') {
    $base .= ' AND t.category = ?';
    $types .= 'i';
    $params[] = $categoryFilter;
}

if ($searchKeyword !== '') {
    $base .= ' AND t.objective LIKE ?';
    $types .= 's';
    $params[] = '%' . $searchKeyword . '%';
}

$base .= ' ORDER BY t.id ASC';

$stmt = $db->prepare($base);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
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
    <div class="sp-card-title"><i class="fas fa-list-check"></i> Your Tasks</div>
  </div>
  <div class="sp-card-body">
    <form method="GET" action="dashboard.php" style="display:flex; align-items:flex-end; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
      <div style="flex:1; min-width:200px;">
        <label for="searchInput" class="sp-label">Search Objectives</label>
        <input class="sp-input" type="text" id="searchInput" name="search" value="<?php echo htmlspecialchars($searchKeyword); ?>" placeholder="Search...">
      </div>
      <div style="min-width:200px;">
        <label for="categoryFilter" class="sp-label">Filter by Category</label>
        <select id="categoryFilter" class="sp-select" name="category" onchange="this.form.submit()">
          <option value="all" <?php if ($categoryFilter === 'all') echo 'selected'; ?>>All</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int) $cat['id']; ?>" <?php if ($categoryFilter === (int) $cat['id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($cat['category']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <button type="submit" class="sp-btn sp-btn-primary"><i class="fas fa-search"></i> Apply</button>
      </div>
    </form>

    <?php if ($num_rows < 1): ?>
      <div class="sp-alert sp-alert-info">
        <i class="fas fa-tasks" style="margin-right:0.5rem;"></i>
        <div>
          <strong>Your to-do list is empty!</strong>
          <p style="margin-bottom:0;">Start adding tasks to get organized.</p>
        </div>
      </div>
    <?php else: ?>
      <p style="margin-bottom:1rem; color:var(--text-secondary);">Number of total tasks shown: <?php echo $num_rows; ?></p>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:1rem;">
        <?php foreach ($tasks as $row): ?>
          <div class="sp-card" style="margin-bottom:0;">
            <div class="sp-card-body">
              <p style="font-weight:600; margin-bottom:0.4rem;">
                <?php
                  $objective = htmlspecialchars($row['objective']);
                  echo ($row['completed'] === 'Yes') ? '<s>' . $objective . '</s>' : $objective;
                ?>
              </p>
              <p style="font-size:0.8rem; margin-bottom:0.4rem; color:var(--text-secondary); display:flex; align-items:center; gap:0.4rem; flex-wrap:wrap;">
                <i class="fas fa-folder"></i>
                <?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?>
                <?php echo ($row['completed'] === 'Yes')
                  ? '<span class="sp-badge sp-badge-green">Completed</span>'
                  : '<span class="sp-badge sp-badge-amber">Not completed</span>'; ?>
                <?php if (!empty($row['private']) && $row['private'] == 1): ?>
                  <span class="sp-badge sp-badge-red"><i class="fas fa-eye-slash"></i> Private</span>
                <?php endif; ?>
              </p>
              <p style="font-size:0.8rem; display:flex; align-items:center; gap:0.3rem; color:var(--text-secondary); margin-bottom:0.2rem;">
                <i class="fas fa-calendar-plus"></i>
                Created: <span class="sp-timestamp" data-timestamp="<?php echo htmlspecialchars($row['created_at']); ?>"><?php echo htmlspecialchars($row['created_at']); ?></span>
              </p>
              <p style="font-size:0.8rem; display:flex; align-items:center; gap:0.3rem; color:var(--text-secondary); margin-bottom:0;">
                <i class="fas fa-pen"></i>
                Updated: <span class="sp-timestamp" data-timestamp="<?php echo htmlspecialchars($row['updated_at']); ?>"><?php echo htmlspecialchars($row['updated_at']); ?></span>
              </p>
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
function spFormatTimestamp(ts) {
  const date = new Date(ts.replace(' ', 'T') + 'Z');
  if (isNaN(date)) return ts;
  const diff = Math.floor((Date.now() - date.getTime()) / 1000);
  if (diff < 60) return diff + ' second' + (diff !== 1 ? 's' : '') + ' ago';
  const m = Math.floor(diff / 60);
  if (m < 60) return m + ' minute' + (m !== 1 ? 's' : '') + ' ago';
  const h = Math.floor(m / 60);
  if (h < 24) return h + ' hour' + (h !== 1 ? 's' : '') + ' ago';
  const d = Math.floor(h / 24);
  if (d < 30) return d + ' day' + (d !== 1 ? 's' : '') + ' ago';
  const mo = Math.floor(d / 30);
  if (mo < 12) return mo + ' month' + (mo !== 1 ? 's' : '') + ' ago';
  const y = Math.floor(d / 365);
  return y + ' year' + (y !== 1 ? 's' : '') + ' ago';
}
function spUpdateTimestamps() {
  document.querySelectorAll('.sp-timestamp').forEach(el => {
    el.textContent = spFormatTimestamp(el.getAttribute('data-timestamp'));
  });
}
document.addEventListener('DOMContentLoaded', spUpdateTimestamps);
setInterval(spUpdateTimestamps, 30000);
</script>
HTML;
include __DIR__ . '/partials/footer.php';
