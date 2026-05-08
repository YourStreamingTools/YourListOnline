<?php
require_once '/var/www/lib/session_bootstrap.php';
require_once '/var/www/config/db_connect.php';
require_once '/var/www/config/twitch.php';

if (!isset($_SESSION['access_token'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

if (!isset($_GET['user_id'])) {
    header('Location: mod_channels.php');
    exit;
}

$targetTwitchId = (string) $_GET['user_id'];

$actorContext = (isset($_SESSION['admin_act_as_original']) && is_array($_SESSION['admin_act_as_original']))
    ? $_SESSION['admin_act_as_original']
    : [
        'user_id'      => $_SESSION['user_id'] ?? null,
        'username'     => $_SESSION['username'] ?? '',
        'twitchUserId' => $_SESSION['twitchUserId'] ?? '',
        'access_token' => $_SESSION['access_token'] ?? null,
        'refresh_token' => $_SESSION['refresh_token'] ?? null,
        'api_key'      => $_SESSION['api_key'] ?? null,
        'is_admin'     => $_SESSION['is_admin'] ?? false,
    ];

$actorUsername    = strtolower(trim((string) ($actorContext['username'] ?? '')));
$actorTwitchId    = trim((string) ($actorContext['twitchUserId'] ?? ''));
$actorAccessToken = trim((string) ($actorContext['access_token'] ?? ''));
$actorIsAdmin     = !empty($actorContext['is_admin']);

$hasAccess = $actorIsAdmin || $actorUsername === 'botofthespecter';

if (!$hasAccess && $actorTwitchId !== '' && $targetTwitchId !== '') {
    $checkStmt = $conn->prepare("SELECT 1 FROM moderator_access WHERE moderator_id = ? AND broadcaster_id = ? LIMIT 1");
    $checkStmt->bind_param('ss', $actorTwitchId, $targetTwitchId);
    $checkStmt->execute();
    $checkStmt->store_result();
    $hasAccess = $checkStmt->num_rows > 0;
    $checkStmt->close();

    if (!$hasAccess && $actorAccessToken !== '' && !empty($clientID)) {
        session_write_close();
        $cursor = null;
        do {
            $url = 'https://api.twitch.tv/helix/moderation/channels?user_id=' . urlencode($actorTwitchId) . '&first=100';
            if ($cursor) { $url .= '&after=' . urlencode($cursor); }
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $actorAccessToken,
                'Client-ID: ' . $clientID,
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            $response = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($response === false || $http !== 200) { break; }
            $data = json_decode($response, true);
            foreach ($data['data'] ?? [] as $ch) {
                if ((string) ($ch['broadcaster_id'] ?? '') === $targetTwitchId) {
                    $hasAccess = true;
                    break 2;
                }
            }
            $cursor = $data['pagination']['cursor'] ?? null;
        } while ($cursor);
        session_start();
    }
}

if (!$hasAccess) {
    header('Location: mod_channels.php?act_as=denied');
    exit;
}

$stmt = $conn->prepare("SELECT id, username, twitch_display_name FROM users WHERE twitch_user_id = ? LIMIT 1");
$stmt->bind_param('s', $targetTwitchId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header('Location: mod_channels.php?act_as=not_found');
    exit;
}

if (!isset($_SESSION['admin_act_as_original']) || !is_array($_SESSION['admin_act_as_original'])) {
    $_SESSION['admin_act_as_original'] = $actorContext;
}
$_SESSION['admin_act_as_active']             = true;
$_SESSION['admin_act_as_started_at']         = time();
$_SESSION['admin_act_as_actor_user_id']      = $actorContext['user_id'] ?? null;
$_SESSION['admin_act_as_actor_username']     = $actorContext['username'] ?? '';
$_SESSION['admin_act_as_actor_role']         = $actorIsAdmin ? 'admin' : 'moderator';
$_SESSION['admin_act_as_target_user_id']     = (int) $row['id'];
$_SESSION['admin_act_as_target_username']    = $row['username'] ?? '';
$_SESSION['admin_act_as_target_display_name'] = $row['twitch_display_name'] ?? '';

header('Location: dashboard.php');
exit;
