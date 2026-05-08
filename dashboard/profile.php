<?php
require_once __DIR__ . '/partials/auth.php';

$pageTitle = 'Profile';
$activePage = 'profile';

include __DIR__ . '/partials/header.php';
?>
<div class="sp-card">
  <div class="sp-card-header">
    <div class="sp-card-title"><i class="fab fa-twitch"></i> Your Profile</div>
  </div>
  <div class="sp-card-body">
    <div style="display:flex; align-items:center; gap:1.25rem; margin-bottom:1.5rem; flex-wrap:wrap;">
      <?php if ($twitch_profile_image_url): ?>
        <img src="<?php echo htmlspecialchars($twitch_profile_image_url); ?>" alt="" style="width:96px; height:96px; border-radius:50%; object-fit:cover;">
      <?php endif; ?>
      <div>
        <p style="font-size:1.4rem; font-weight:700; margin:0 0 0.2rem;"><?php echo htmlspecialchars($twitchDisplayName); ?></p>
        <p style="margin:0; color:var(--text-muted);">@<?php echo htmlspecialchars($username); ?></p>
        <?php if ($is_admin): ?>
          <span class="sp-badge sp-badge-amber" style="margin-top:0.4rem;">Admin</span>
        <?php endif; ?>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
      <div>
        <p class="sp-label">Twitch User ID</p>
        <p style="margin:0; font-family: monospace;"><?php echo htmlspecialchars($twitchUserId); ?></p>
      </div>
      <div>
        <p class="sp-label">Email</p>
        <p style="margin:0;"><?php echo $email !== '' ? htmlspecialchars($email) : '<span style="color:var(--text-muted);">not provided</span>'; ?></p>
      </div>
      <div>
        <p class="sp-label">Time Zone</p>
        <p style="margin:0;"><?php echo htmlspecialchars($timezone); ?></p>
      </div>
    </div>

    <div class="sp-form-group">
      <p class="sp-label">Your API Key</p>
      <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
        <code id="api-key-text" style="background:var(--bg-base); padding:0.5rem 0.75rem; border-radius:var(--radius-sm); flex:1; min-width:200px; word-break:break-all; filter:blur(6px); transition:filter 0.15s;"><?php echo htmlspecialchars($api_key); ?></code>
        <button id="toggle-api-key" type="button" class="sp-btn sp-btn-secondary"><i class="fas fa-eye"></i> Show</button>
        <button id="copy-api-key" type="button" class="sp-btn sp-btn-secondary"><i class="fas fa-copy"></i> Copy</button>
      </div>
    </div>

    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:1rem;">
      <a href="obs_options.php" class="sp-btn sp-btn-primary"><i class="fas fa-cog"></i> OBS Viewing Options</a>
      <a href="logout.php" class="sp-btn sp-btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</div>
<?php
$pageScripts = <<<'HTML'
<script>
(function() {
  const code = document.getElementById('api-key-text');
  const toggle = document.getElementById('toggle-api-key');
  const copy = document.getElementById('copy-api-key');
  let visible = false;
  toggle?.addEventListener('click', () => {
    visible = !visible;
    code.style.filter = visible ? 'none' : 'blur(6px)';
    toggle.innerHTML = visible ? '<i class="fas fa-eye-slash"></i> Hide' : '<i class="fas fa-eye"></i> Show';
  });
  copy?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(code.textContent.trim());
      copy.innerHTML = '<i class="fas fa-check"></i> Copied';
      setTimeout(() => { copy.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 1500);
    } catch (e) {}
  });
})();
</script>
HTML;
include __DIR__ . '/partials/footer.php';
