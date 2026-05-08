<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle = 'Categories';
$activePage = 'categories';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_category_id'])) {
    $remove_id = (int) $_POST['remove_category_id'];

    if ($remove_id === 1) {
        $message = 'Cannot remove the default category.';
        $messageType = 'warning';
    } else {
        $check = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $check->bind_param('i', $remove_id);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$exists) {
            $message = 'Category not found.';
            $messageType = 'danger';
        } else {
            $defaultCheck = $db->prepare("SELECT id FROM categories WHERE id = 1");
            $defaultCheck->execute();
            $defaultExists = $defaultCheck->get_result()->fetch_assoc();
            $defaultCheck->close();

            if (!$defaultExists) {
                $insertDefault = $db->prepare("INSERT INTO categories (id, category) VALUES (1, 'Default')");
                $insertDefault->execute();
                $insertDefault->close();
            }

            $reassign = $db->prepare("UPDATE todos SET category = 1 WHERE category = ?");
            $reassign->bind_param('i', $remove_id);
            $reassign->execute();
            $reassign->close();

            $del = $db->prepare("DELETE FROM categories WHERE id = ?");
            $del->bind_param('i', $remove_id);
            $del->execute();
            $del->close();

            header('Location: categories.php');
            exit;
        }
    }
}

$stmt = $db->prepare("SELECT id, category FROM categories ORDER BY id");
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/partials/header.php';
?>
<div class="sp-alert sp-alert-info" style="display:flex; gap:1.25rem; align-items:flex-start; margin-bottom:1.5rem;">
  <span style="font-size:1.75rem; color:var(--blue); flex-shrink:0;"><i class="fas fa-list-ul"></i></span>
  <div>
    <p style="font-weight:700; margin-bottom:0.25rem;">Manage Your Categories</p>
    <p style="margin-bottom:0;">Each category is its own list.</p>
  </div>
</div>
<?php if ($message): ?>
  <div class="sp-alert sp-alert-<?php echo htmlspecialchars($messageType); ?>" style="margin-bottom:1rem;">
    <?php echo htmlspecialchars($message); ?>
  </div>
<?php endif; ?>
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:1rem;">
  <?php foreach ($categories as $row): ?>
    <div class="sp-card" style="margin-bottom:0;">
      <div class="sp-card-body">
        <div style="display:flex; align-items:center; gap:1rem;">
          <span style="font-size:1.5rem; color:var(--blue); flex-shrink:0;"><i class="fas fa-folder"></i></span>
          <div style="flex:1; min-width:0;">
            <p style="font-weight:700; margin-bottom:0.25rem; word-break:break-word;">
              <?php echo htmlspecialchars($row['category']); ?>
            </p>
            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0;">ID: <?php echo (int) $row['id']; ?></p>
          </div>
          <?php if ((int) $row['id'] !== 1): ?>
            <form method="post" style="margin-bottom:0;" class="remove-category-form">
              <input type="hidden" name="remove_category_id" value="<?php echo (int) $row['id']; ?>">
              <button type="button" class="sp-btn sp-btn-danger sp-btn-sm remove-category-btn">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php
$pageScripts = <<<'HTML'
<script>
document.querySelectorAll('.remove-category-btn').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    const form = btn.closest('form');
    Swal.fire({
      title: 'Are you sure?',
      text: 'Tasks in this category will be moved to the Default category.',
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
