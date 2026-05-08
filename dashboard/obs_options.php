<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle = 'OBS Viewing Options';
$activePage = 'obs_options';

$stmt = $db->prepare("SELECT * FROM showobs LIMIT 1");
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();
$stmt->close();
$hasExisting = (bool) $settings;

$allowed_fonts = ['Arial', 'Arial Narrow', 'Verdana', 'Times New Roman', 'Courier New', 'Georgia'];
$allowed_named_colors = ['Black', 'White', 'Red', 'Blue'];

$font_raw = $settings['font'] ?? '';
$color_raw = $settings['color'] ?? '';
$list_raw = $settings['list'] ?? 'Bullet';

if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color_raw)) {
    $color_display = 'Other';
} elseif (in_array($color_raw, $allowed_named_colors, true)) {
    $color_display = $color_raw;
} else {
    $color_display = 'Not set';
}

$font_display = in_array($font_raw, $allowed_fonts, true) ? $font_raw : ($font_raw ?: 'Not set');
$list_display = ($list_raw === 'Numbered') ? 'Numbered' : 'Bullet';
$shadow = !empty($settings['shadow']);
$bold = !empty($settings['bold']);
$show_completed = !empty($settings['show_completed']);
$font_size = (int) preg_replace('/\D/', '', (string) ($settings['font_size'] ?? '12'));
if ($font_size <= 0) { $font_size = 12; }

$saveError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedFont = (string) ($_POST['font'] ?? '');
    if (!in_array($selectedFont, $allowed_fonts, true)) { $selectedFont = 'Arial'; }

    $selectedList = ((string) ($_POST['list'] ?? 'Bullet')) === 'Numbered' ? 'Numbered' : 'Bullet';
    $selectedShadow = isset($_POST['shadow']) ? 1 : 0;
    $selectedBold = isset($_POST['bold']) ? 1 : 0;
    $selectedShowCompleted = isset($_POST['show_completed']) ? 1 : 0;
    $selectedFontSize = (int) preg_replace('/\D/', '', (string) ($_POST['font_size'] ?? '12'));
    if ($selectedFontSize < 8) { $selectedFontSize = 8; }
    if ($selectedFontSize > 96) { $selectedFontSize = 96; }

    $colorChoice = (string) ($_POST['color'] ?? 'Black');
    if ($colorChoice === 'Other') {
        $custom = trim((string) ($_POST['custom_color'] ?? ''));
        if ($custom !== '' && $custom[0] !== '#') { $custom = '#' . $custom; }
        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $custom)) {
            $selectedColor = $custom;
        } else {
            $selectedColor = 'White';
            $saveError = 'Invalid custom color; defaulted to White.';
        }
    } elseif (in_array($colorChoice, $allowed_named_colors, true)) {
        $selectedColor = $colorChoice;
    } else {
        $selectedColor = 'Black';
    }

    if ($hasExisting) {
        $upd = $db->prepare("UPDATE showobs SET font=?, color=?, list=?, shadow=?, bold=?, font_size=?, show_completed=? LIMIT 1");
        $upd->bind_param('sssiiii', $selectedFont, $selectedColor, $selectedList, $selectedShadow, $selectedBold, $selectedFontSize, $selectedShowCompleted);
    } else {
        $upd = $db->prepare("INSERT INTO showobs (font, color, list, shadow, bold, font_size, show_completed) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $upd->bind_param('sssiiii', $selectedFont, $selectedColor, $selectedList, $selectedShadow, $selectedBold, $selectedFontSize, $selectedShowCompleted);
    }
    if ($upd->execute()) {
        $upd->close();
        header('Location: obs_options.php');
        exit;
    } else {
        $saveError = 'Error saving settings: ' . htmlspecialchars($upd->error);
        $upd->close();
    }
}

include __DIR__ . '/partials/header.php';
?>
<div class="sp-card">
  <div class="sp-card-header">
    <div class="sp-card-title"><i class="fas fa-cog"></i> OBS Font &amp; Color Settings</div>
  </div>
  <div class="sp-card-body">
    <button type="button" class="sp-btn sp-btn-primary" style="margin-bottom:1.25rem;" onclick="document.getElementById('obsInfoModal').classList.remove('hidden')">
      <i class="fas fa-info-circle"></i> How to put on your stream
    </button>
    <div id="obsInfoModal" class="db-modal-backdrop hidden">
      <div class="db-modal">
        <div class="db-modal-head">
          <span class="db-modal-title"><i class="fas fa-info-circle"></i> How to use the ToDo List in OBS</span>
          <button class="db-modal-close" type="button" aria-label="close" onclick="document.getElementById('obsInfoModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="db-modal-body">
          <p>The overlay is provided by BotOfTheSpecter and works in any streaming software (OBS, SLOBS, xSplit, Wirecast).</p>
          <p>Add the following link as a browser source:</p>
          <pre style="background:var(--bg-base); padding:0.75rem; border-radius:var(--radius); font-size:0.85rem; overflow-x:auto; white-space:pre-wrap;">https://overlay.botofthespecter.com/todolist.php?code=<?php echo htmlspecialchars($api_key); ?></pre>
          <p>To filter by a specific category, append <code>&amp;category=ID</code> (where ID 1 is the Default category):</p>
          <pre style="background:var(--bg-base); padding:0.75rem; border-radius:var(--radius); font-size:0.85rem; overflow-x:auto; white-space:pre-wrap;">todolist.php?code=<?php echo htmlspecialchars($api_key); ?>&amp;category=1</pre>
          <p>To wrap the list in a styled box (helpful when the stream overlay makes it hard to read), append <code>&amp;theme=true</code>:</p>
          <pre style="background:var(--bg-base); padding:0.75rem; border-radius:var(--radius); font-size:0.85rem; overflow-x:auto; white-space:pre-wrap;">todolist.php?code=<?php echo htmlspecialchars($api_key); ?>&amp;theme=true</pre>
          <p style="font-size:0.85rem; margin-bottom:0;">You can combine: <code>&amp;category=1&amp;theme=true</code></p>
        </div>
        <div class="db-modal-foot">
          <button class="sp-btn sp-btn-danger" type="button" onclick="document.getElementById('obsInfoModal').classList.add('hidden')">Close</button>
        </div>
      </div>
    </div>

    <?php if ($saveError): ?>
      <div class="sp-alert sp-alert-warning" style="margin-bottom:1rem;">
        <i class="fas fa-exclamation-triangle"></i>
        <span><?php echo $saveError; ?></span>
      </div>
    <?php endif; ?>

    <h3 style="font-size:1.05rem; font-weight:700; margin-bottom:1rem;">Current Settings</h3>
    <?php if ($hasExisting): ?>
      <div style="display:flex; flex-wrap:wrap; gap:1rem 2rem; padding:1rem 0; border-bottom:1px solid var(--border); margin-bottom:1.25rem;">
        <div><strong>Font:</strong> <?php echo htmlspecialchars($font_display); ?></div>
        <div>
          <strong>Color:</strong>
          <span style="display:inline-block; width:16px; height:16px; background-color:<?php echo htmlspecialchars($color_raw ?: '#000'); ?>; margin:0 4px; vertical-align:middle; border-radius:3px; border:1px solid var(--border);"></span>
          <?php echo htmlspecialchars($color_raw ?: 'Not set'); ?>
        </div>
        <div><strong>List Type:</strong> <?php echo htmlspecialchars($list_display); ?></div>
        <div><strong>Font Size:</strong> <?php echo $font_size; ?>px</div>
        <div><strong>Shadow:</strong> <?php echo $shadow ? 'Enabled' : 'Disabled'; ?></div>
        <div><strong>Bold:</strong> <?php echo $bold ? 'Enabled' : 'Disabled'; ?></div>
        <div><strong>Show Completed:</strong> <?php echo $show_completed ? 'Yes' : 'No'; ?></div>
      </div>
    <?php else: ?>
      <div class="sp-alert sp-alert-info" style="margin-bottom:1.25rem;">
        <i class="fas fa-palette"></i>
        <div>
          <p style="font-weight:700; margin-bottom:0.25rem;">Customize your list!</p>
          <p style="margin-bottom:0;">No font and color settings have been set yet. Use the controls below to personalize the look.</p>
        </div>
      </div>
    <?php endif; ?>

    <form method="post">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
        <div class="sp-form-group">
          <label class="sp-label" for="font">Font</label>
          <select name="font" id="font" class="sp-select">
            <?php foreach ($allowed_fonts as $f): ?>
              <option value="<?php echo htmlspecialchars($f); ?>" <?php if ($font_display === $f) echo 'selected'; ?>><?php echo htmlspecialchars($f); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sp-form-group">
          <label class="sp-label" for="color-select">Color</label>
          <select name="color" id="color-select" class="sp-select">
            <?php foreach ($allowed_named_colors as $c): ?>
              <option value="<?php echo $c; ?>" <?php if ($color_display === $c) echo 'selected'; ?>><?php echo $c; ?></option>
            <?php endforeach; ?>
            <option value="Other" <?php if ($color_display === 'Other') echo 'selected'; ?>>Other (custom hex)</option>
          </select>
          <div id="custom-color-group" style="margin-top:0.5rem; <?php if ($color_display !== 'Other') echo 'display:none;'; ?>">
            <input type="text" name="custom_color" id="custom-color-input" class="sp-input" placeholder="#aabbcc" value="<?php echo htmlspecialchars($color_display === 'Other' ? $color_raw : ''); ?>">
          </div>
        </div>
        <div class="sp-form-group">
          <label class="sp-label" for="list">List Type</label>
          <select name="list" id="list" class="sp-select">
            <option value="Bullet" <?php if ($list_display === 'Bullet') echo 'selected'; ?>>Bullet List</option>
            <option value="Numbered" <?php if ($list_display === 'Numbered') echo 'selected'; ?>>Numbered List</option>
          </select>
        </div>
        <div class="sp-form-group">
          <label class="sp-label" for="font_size">Font Size (px)</label>
          <input type="number" name="font_size" id="font_size" class="sp-input" min="8" max="96" value="<?php echo $font_size; ?>">
        </div>
        <div class="sp-form-group">
          <label class="sp-label">Text Shadow</label>
          <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
            <input type="checkbox" name="shadow" value="1" <?php if ($shadow) echo 'checked'; ?>>
            <span>Enable shadow</span>
          </label>
        </div>
        <div class="sp-form-group">
          <label class="sp-label">Text Bold</label>
          <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
            <input type="checkbox" name="bold" value="1" <?php if ($bold) echo 'checked'; ?>>
            <span>Enable bold</span>
          </label>
        </div>
        <div class="sp-form-group">
          <label class="sp-label">Show Completed Tasks</label>
          <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
            <input type="checkbox" name="show_completed" value="1" <?php if ($show_completed) echo 'checked'; ?>>
            <span>Show completed tasks in overlay</span>
          </label>
        </div>
      </div>
      <div style="display:flex; justify-content:flex-end; margin-top:1.25rem;">
        <button type="submit" class="sp-btn sp-btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<?php
$pageScripts = <<<'HTML'
<script>
(function() {
  const colorSelect = document.getElementById('color-select');
  const customGroup = document.getElementById('custom-color-group');
  const customInput = document.getElementById('custom-color-input');
  if (colorSelect && customGroup) {
    colorSelect.addEventListener('change', function() {
      customGroup.style.display = (colorSelect.value === 'Other') ? 'block' : 'none';
    });
  }
  if (customInput) {
    customInput.addEventListener('blur', function() {
      const v = customInput.value.trim();
      if (v && v[0] !== '#') customInput.value = '#' + v;
    });
  }
})();
</script>
HTML;
include __DIR__ . '/partials/footer.php';
