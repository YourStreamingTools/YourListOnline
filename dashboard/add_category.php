<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle = 'Add Category';
$activePage = 'add_category';

$category = '';
$category_err = '';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim((string) ($_POST['category'] ?? ''));

    if ($category === '') {
        $category_err = 'Please enter a category name.';
    } else {
        $dupe = $db->prepare("SELECT id FROM categories WHERE category = ?");
        $dupe->bind_param('s', $category);
        $dupe->execute();
        $dupe->store_result();
        if ($dupe->num_rows > 0) {
            $category_err = 'This category name already exists.';
        }
        $dupe->close();
    }

    if ($category_err === '') {
        $insert = $db->prepare("INSERT INTO categories (category) VALUES (?)");
        $insert->bind_param('s', $category);
        if ($insert->execute()) {
            $message = 'Category added successfully!';
            $messageType = 'success';
            $category = '';
        } else {
            $message = 'Oops! Something went wrong. Please try again later.';
            $messageType = 'danger';
        }
        $insert->close();
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="sp-card">
  <div class="sp-card-header">
    <div class="sp-card-title"><i class="fas fa-folder-plus"></i> Add New Category</div>
  </div>
  <div class="sp-card-body">
    <?php if ($message): ?>
      <div class="sp-alert sp-alert-<?php echo htmlspecialchars($messageType); ?>" style="margin-bottom:1rem;">
        <?php if ($messageType === 'success'): ?><i class="fas fa-check-circle"></i><?php else: ?><i class="fas fa-exclamation-triangle"></i><?php endif; ?>
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
      <h3 style="font-size:1.05rem; font-weight:700; margin-bottom:1rem;">Type in what your new category will be:</h3>
      <div class="sp-form-group">
        <label class="sp-label" for="category">Category Name</label>
        <input type="text" name="category" id="category" class="sp-input" value="<?php echo htmlspecialchars($category); ?>" placeholder="e.g. Work, Personal, Shopping">
        <?php if ($category_err): ?>
          <p style="color:var(--red); font-size:0.8rem; margin-top:0.25rem;"><?php echo htmlspecialchars($category_err); ?></p>
        <?php endif; ?>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
        <input type="submit" class="sp-btn sp-btn-primary" value="Submit">
        <a href="categories.php" class="sp-btn sp-btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
