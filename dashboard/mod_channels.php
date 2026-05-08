<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle  = 'Mod Channels';
$activePage = 'mod_channels';

include __DIR__ . '/partials/header.php';
?>
<?php if (isset($_GET['act_as']) && $_GET['act_as'] === 'stopped'): ?>
  <div class="sp-alert sp-alert-info" style="margin-bottom:1rem;">Act-as mode has been stopped.</div>
<?php elseif (isset($_GET['act_as']) && $_GET['act_as'] === 'denied'): ?>
  <div class="sp-alert sp-alert-danger" style="margin-bottom:1rem;">You do not have permission to act as that channel.</div>
<?php elseif (isset($_GET['act_as']) && $_GET['act_as'] === 'not_found'): ?>
  <div class="sp-alert sp-alert-warning" style="margin-bottom:1rem;">The selected channel could not be found.</div>
<?php endif; ?>

<div class="sp-card">
  <div class="sp-card-header">
    <div class="sp-card-title"><i class="fas fa-user-secret"></i> Channels you can act as</div>
  </div>
  <div class="sp-card-body">
    <?php if (count($modChannels) > 9): ?>
      <div class="sp-form-group">
        <label class="sp-label" for="mod-channel-search">Search channels</label>
        <input id="mod-channel-search" class="sp-input" type="text" placeholder="Type a streamer name or username" autocomplete="off">
      </div>
    <?php endif; ?>

    <?php if (empty($modChannels)): ?>
      <div class="sp-alert sp-alert-info">
        <i class="fas fa-info-circle"></i>
        <div>
          <strong>No channels yet.</strong>
          <p style="margin-bottom:0;">Once a streamer adds you on their <a href="mods.php">Mods</a> page, they'll show up here.</p>
        </div>
      </div>
    <?php else: ?>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:1rem;">
        <?php foreach ($modChannels as $channel): ?>
          <div class="sp-card mod-channel-card" data-search="<?php echo htmlspecialchars(strtolower(($channel['twitch_display_name'] ?? '') . ' ' . ($channel['username'] ?? '')), ENT_QUOTES); ?>" style="margin-bottom:0;">
            <div class="sp-card-body">
              <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
                <?php if (!empty($channel['profile_image'])): ?>
                  <img src="<?php echo htmlspecialchars($channel['profile_image']); ?>" alt="" style="width:64px; height:64px; border-radius:50%; object-fit:cover; flex-shrink:0;">
                <?php endif; ?>
                <div style="min-width:0;">
                  <p style="font-weight:700; margin:0 0 0.15rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($channel['twitch_display_name'] ?? ''); ?></p>
                  <p style="margin:0; font-size:0.85rem; color:var(--text-muted);">@<?php echo htmlspecialchars($channel['username'] ?? ''); ?></p>
                </div>
              </div>
              <a href="switch_channel.php?user_id=<?php echo urlencode($channel['twitch_user_id']); ?>" class="sp-btn sp-btn-primary" style="width:100%; justify-content:center;">
                <i class="fas fa-user-secret"></i> Act as this channel
              </a>
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
const search = document.getElementById('mod-channel-search');
if (search) {
  const cards = document.querySelectorAll('.mod-channel-card');
  search.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    cards.forEach(card => {
      card.style.display = (!q || card.dataset.search.includes(q)) ? '' : 'none';
    });
  });
}
</script>
HTML;
include __DIR__ . '/partials/footer.php';
