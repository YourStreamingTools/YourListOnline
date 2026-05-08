<?php
// Manage who can act as you (the streamer). Only meaningful when not in act-as
// mode; while acting as another channel, write actions are disabled.

require_once '/var/www/lib/session_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['access_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'not_authenticated']);
        exit;
    }
    require_once '/var/www/config/db_connect.php';
    $moderatorId   = $_POST['moderator_id'] ?? null;
    $broadcasterId = $_SESSION['twitchUserId'] ?? null;
    $action        = $_POST['action'] ?? null;

    $isActingAs = isset($_SESSION['admin_act_as_active']) && $_SESSION['admin_act_as_active'] === true;
    $isActingAsAdmin = $isActingAs && ($_SESSION['admin_act_as_actor_role'] ?? '') === 'admin';
    if ($isActingAs && !$isActingAsAdmin && in_array($action, ['add', 'remove'], true)) {
        echo json_encode(['status' => 'error', 'message' => 'Managing dashboard access is disabled while acting as another channel.']);
        exit;
    }

    if (!$moderatorId || !$broadcasterId || !in_array($action, ['add', 'remove'], true)) {
        echo json_encode(['status' => 'error', 'message' => 'missing_or_invalid_parameters']);
        exit;
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO moderator_access (moderator_id, broadcaster_id) VALUES (?, ?)");
    } else {
        $stmt = $conn->prepare("DELETE FROM moderator_access WHERE moderator_id = ? AND broadcaster_id = ?");
    }
    $stmt->bind_param('ss', $moderatorId, $broadcasterId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'ok', 'action' => $action, 'moderator_id' => $moderatorId]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

require_once __DIR__ . '/partials/auth.php';

$pageTitle  = 'Mods';
$activePage = 'mods';

$isActingAs = isset($_SESSION['admin_act_as_active']) && $_SESSION['admin_act_as_active'] === true;
$isActingAsAdmin = $isActingAs && ($_SESSION['admin_act_as_actor_role'] ?? '') === 'admin';
$disableModActions = $isActingAs && !$isActingAsAdmin;

$accessStmt = $conn->prepare("SELECT moderator_id FROM moderator_access WHERE broadcaster_id = ?");
$accessStmt->bind_param('s', $twitchUserId);
$accessStmt->execute();
$accessRows = $accessStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$accessStmt->close();
$accessSet = array_flip(array_column($accessRows, 'moderator_id'));

$registeredStmt = $conn->prepare("SELECT twitch_user_id, twitch_display_name, username, profile_image FROM users WHERE twitch_user_id IS NOT NULL AND twitch_user_id != ''");
$registeredStmt->execute();
$registeredById = [];
$result = $registeredStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $registeredById[(string) $row['twitch_user_id']] = $row;
}
$registeredStmt->close();

$allModerators = [];
$cursor = null;
$clientIDForCalls = $clientID ?? '';
do {
    $url = 'https://api.twitch.tv/helix/moderation/moderators?broadcaster_id=' . urlencode($twitchUserId) . '&first=100';
    if ($cursor) { $url .= '&after=' . urlencode($cursor); }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $authToken,
        'Client-ID: ' . $clientIDForCalls,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $http !== 200) { break; }
    $data = json_decode($response, true);
    if (!empty($data['data'])) {
        $allModerators = array_merge($allModerators, $data['data']);
    }
    $cursor = $data['pagination']['cursor'] ?? null;
} while ($cursor);

$botAccounts = ['nightbot', 'streamelements', 'streamlabs', 'moobot', 'fossabot', 'wizebot'];
$filtered = array_values(array_filter($allModerators, fn($m) => !in_array(strtolower($m['user_name'] ?? ''), $botAccounts, true)));

$currentModeratorIds = array_flip(array_map('strval', array_column($allModerators, 'user_id')));
foreach ($accessRows as $accessRow) {
    $modId = (string) $accessRow['moderator_id'];
    if ($modId === '' || isset($currentModeratorIds[$modId])) { continue; }
    $stale = $registeredById[$modId] ?? null;
    $name = $stale['twitch_display_name'] ?? ('User ' . $modId);
    $filtered[] = [
        'user_id' => $modId,
        'user_name' => $name,
        'is_stale_access' => true,
    ];
}

include __DIR__ . '/partials/header.php';
?>
<div class="sp-card">
  <div class="sp-card-header">
    <div class="sp-card-title"><i class="fas fa-user-shield"></i> Manage Dashboard Access</div>
  </div>
  <div class="sp-card-body">
    <div class="sp-alert sp-alert-info" style="margin-bottom:1.25rem;">
      <i class="fas fa-info-circle"></i>
      <div>
        <p style="font-weight:700; margin-bottom:0.25rem;">Who can manage your list?</p>
        <p style="margin-bottom:0;">Granting access lets a Twitch moderator log in to YourListOnline and act as your channel &mdash; meaning they can add, complete, and remove tasks on your list. Only grant access to people you trust.</p>
      </div>
    </div>
    <?php if ($disableModActions): ?>
      <div class="sp-alert sp-alert-warning" style="margin-bottom:1rem;">
        <i class="fas fa-exclamation-triangle"></i>
        Managing dashboard access is disabled while you are acting as another channel.
      </div>
    <?php endif; ?>
    <?php if (empty($filtered)): ?>
      <p style="color:var(--text-muted);">No moderators on your channel yet.</p>
    <?php else: ?>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:1rem;">
        <?php foreach ($filtered as $mod):
          $modId = (string) $mod['user_id'];
          $modName = $mod['user_name'];
          $hasAccess = isset($accessSet[$modId]);
          $stale = !empty($mod['is_stale_access']);
          $registered = isset($registeredById[$modId]);
          $avatar = $registeredById[$modId]['profile_image'] ?? '';
        ?>
          <div class="sp-card" style="margin-bottom:0;">
            <div class="sp-card-body" style="display:flex; align-items:center; gap:1rem;">
              <?php if ($avatar): ?>
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="" style="width:48px; height:48px; border-radius:50%; object-fit:cover; flex-shrink:0;">
              <?php else: ?>
                <span style="width:48px; height:48px; border-radius:50%; background:var(--bg-base); display:inline-flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0;"><?php echo strtoupper(mb_substr($modName, 0, 1)); ?></span>
              <?php endif; ?>
              <div style="flex:1; min-width:0;">
                <p style="margin:0 0 0.2rem; font-weight:700; word-break:break-word;">
                  <?php echo htmlspecialchars($modName); ?>
                  <?php if ($stale): ?><span class="sp-badge sp-badge-amber" style="margin-left:0.25rem;">No longer mod</span><?php endif; ?>
                  <?php if (!$registered): ?><span class="sp-badge sp-badge-red" style="margin-left:0.25rem;">Unregistered</span><?php endif; ?>
                </p>
                <p style="margin:0; font-size:0.78rem; color:var(--text-muted);">ID: <?php echo htmlspecialchars($modId); ?></p>
              </div>
              <button type="button"
                class="sp-btn sp-btn-sm <?php echo $hasAccess ? 'sp-btn-danger' : 'sp-btn-primary'; ?> access-control"
                data-user-id="<?php echo htmlspecialchars($modId); ?>"
                data-action="<?php echo $hasAccess ? 'remove' : 'add'; ?>"
                <?php echo $disableModActions ? 'disabled' : ''; ?>>
                <?php echo $hasAccess ? 'Revoke' : 'Grant'; ?>
              </button>
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
document.querySelectorAll('.access-control').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (btn.disabled) return;
    const userId = btn.dataset.userId;
    const action = btn.dataset.action;
    btn.disabled = true;
    try {
      const fd = new FormData();
      fd.append('moderator_id', userId);
      fd.append('action', action);
      const resp = await fetch('mods.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const json = await resp.json();
      if (json.status === 'ok') {
        if (json.action === 'add') {
          btn.classList.replace('sp-btn-primary', 'sp-btn-danger');
          btn.dataset.action = 'remove';
          btn.textContent = 'Revoke';
        } else {
          btn.classList.replace('sp-btn-danger', 'sp-btn-primary');
          btn.dataset.action = 'add';
          btn.textContent = 'Grant';
        }
        Swal.fire({ icon: 'success', title: 'Updated', timer: 1200, showConfirmButton: false });
      } else {
        Swal.fire({ icon: 'error', title: 'Failed', text: json.message || 'Try again later.' });
      }
    } catch (e) {
      Swal.fire({ icon: 'error', title: 'Network error' });
    } finally {
      btn.disabled = false;
    }
  });
});
</script>
HTML;
include __DIR__ . '/partials/footer.php';
