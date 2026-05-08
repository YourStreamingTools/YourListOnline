<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle = 'Update Category';
$activePage = 'update_category';

$catStmt = $db->prepare("SELECT id, category FROM categories ORDER BY category");
$catStmt->execute();
$categories = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$catStmt->close();

$rowsStmt = $db->prepare("SELECT id, objective, category FROM todos ORDER BY id DESC");
$rowsStmt->execute();
$rows = $rowsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rowsStmt->close();
$num_rows = count($rows);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newCategories = $_POST['category'] ?? [];
    foreach ($rows as $row) {
        $row_id = (int) $row['id'];
        $new_category = (int) ($newCategories[$row_id] ?? $row['category']);
        if ($new_category !== (int) $row['category']) {
            $upd = $db->prepare("UPDATE todos SET category = ?, updated_at = NOW() WHERE id = ?");
            $upd->bind_param('ii', $new_category, $row_id);
            $upd->execute();
            $upd->close();
        }
    }
    header('Location: update_category.php');
    exit;
}

include __DIR__ . '/partials/header.php';
?>
<div class="sp-card">
  <div class="sp-card-header">
    <div class="sp-card-title"><i class="fas fa-folder-tree"></i> Update Task Category</div>
  </div>
  <div class="sp-card-body">
    <?php if ($num_rows < 1): ?>
      <div class="sp-alert sp-alert-info">
        <i class="fas fa-tasks fa-2x" style="color:var(--blue);"></i>
        <div>
          <strong>Your to-do list is empty!</strong>
          <p style="margin-bottom:0;">Add a task before you can recategorize.</p>
        </div>
      </div>
    <?php else: ?>
      <form method="POST">
        <h2 style="font-size:1rem; font-weight:700; margin-bottom:1rem;">Reassign categories below, then click "Update All":</h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:1rem;">
          <?php foreach ($rows as $row): ?>
            <?php
              $catName = 'Uncategorized';
              foreach ($categories as $cat) {
                  if ((int) $cat['id'] === (int) $row['category']) { $catName = $cat['category']; break; }
              }
            ?>
            <div class="sp-card" style="margin-bottom:0;">
              <div class="sp-card-body" style="display:flex; flex-direction:column; gap:0.75rem;">
                <div>
                  <p style="font-weight:600; margin-bottom:0.2rem;"><?php echo htmlspecialchars($row['objective']); ?></p>
                  <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0; display:flex; align-items:center; gap:0.3rem;">
                    <i class="fas fa-folder"></i> Currently: <?php echo htmlspecialchars($catName); ?>
                  </p>
                </div>
                <div class="sp-form-group" style="margin-bottom:0;">
                  <label class="sp-label" for="category_<?php echo (int) $row['id']; ?>">New Category</label>
                  <select name="category[<?php echo (int) $row['id']; ?>]" id="category_<?php echo (int) $row['id']; ?>" class="sp-select">
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?php echo (int) $cat['id']; ?>" <?php if ((int) $cat['id'] === (int) $row['category']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['category']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
          <button type="submit" name="submit" class="sp-btn sp-btn-primary">Update All</button>
          <a href="dashboard.php" class="sp-btn sp-btn-secondary">Cancel</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
