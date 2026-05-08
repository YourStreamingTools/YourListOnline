<?php
// Loads the active user from the website.users table. Honours the BotOfTheSpecter
// "act as" session keys so an admin or moderator viewing a streamer's list sees
// the streamer's data instead of their own.
//
// Requires:
//   - Session already started (session_bootstrap.php)
//   - $conn (mysqli to website DB)
//   - $_SESSION['access_token']

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once '/var/www/config/db_connect.php';
}

$isActingAs = isset($_SESSION['admin_act_as_active']) && $_SESSION['admin_act_as_active'] === true;
$sessionAccessToken = $_SESSION['access_token'] ?? null;
$originalContext = (isset($_SESSION['admin_act_as_original']) && is_array($_SESSION['admin_act_as_original']))
    ? $_SESSION['admin_act_as_original']
    : [];
$actorAccessToken = $isActingAs
    ? ($originalContext['access_token'] ?? $sessionAccessToken)
    : $sessionAccessToken;

if (!$actorAccessToken) {
    header('Location: login.php');
    exit;
}

$userStmt = null;
$targetUserId = $isActingAs ? (int) ($_SESSION['admin_act_as_target_user_id'] ?? 0) : 0;

if ($isActingAs && $targetUserId > 0) {
    $userStmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    if ($userStmt) {
        $userStmt->bind_param('i', $targetUserId);
    }
}

if (!$userStmt) {
    $isActingAs = false;
    $userStmt = $conn->prepare("SELECT * FROM users WHERE access_token = ? LIMIT 1");
    $userStmt->bind_param('s', $actorAccessToken);
}

if (!$userStmt->execute()) {
    error_log('userdata.php query failed: ' . $userStmt->error);
    header('Location: login.php');
    exit;
}

$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    if ($isActingAs) {
        unset(
            $_SESSION['admin_act_as_active'],
            $_SESSION['admin_act_as_started_at'],
            $_SESSION['admin_act_as_actor_user_id'],
            $_SESSION['admin_act_as_actor_username'],
            $_SESSION['admin_act_as_actor_role'],
            $_SESSION['admin_act_as_target_user_id'],
            $_SESSION['admin_act_as_target_username'],
            $_SESSION['admin_act_as_target_display_name']
        );
        $fallback = $conn->prepare("SELECT * FROM users WHERE access_token = ? LIMIT 1");
        $fallback->bind_param('s', $actorAccessToken);
        if ($fallback->execute()) {
            $user = $fallback->get_result()->fetch_assoc();
        }
        $fallback->close();
        $isActingAs = false;
    }
    if (!$user) {
        header('Location: login.php');
        exit;
    }
}

$user_id                  = (int) $user['id'];
$username                 = $user['username'];
$twitchDisplayName        = $user['twitch_display_name'] ?? $username;
$twitch_profile_image_url = $user['profile_image'] ?? '';
$email                    = $user['email'] ?? '';
$is_admin                 = !empty($user['is_admin']);
$betaAccess               = !empty($user['beta_access']);
$twitchUserId             = $user['twitch_user_id'] ?? '';
$refreshToken             = $user['refresh_token'] ?? '';
$api_key                  = $user['api_key'] ?? '';
$broadcasterID            = $twitchUserId;
$authToken                = $user['access_token'] ?? '';

$_SESSION['user_id']      = $user_id;
$_SESSION['username']     = $username;
$_SESSION['twitchUserId'] = $twitchUserId;
$_SESSION['api_key']      = $api_key;
$_SESSION['is_admin']     = $is_admin;
$_SESSION['profile_image'] = $twitch_profile_image_url;
$_SESSION['display_name'] = $twitchDisplayName;

if ($isActingAs) {
    $_SESSION['access_token'] = $actorAccessToken;
    $_SESSION['refresh_token'] = $originalContext['refresh_token'] ?? ($_SESSION['refresh_token'] ?? null);
} else {
    $_SESSION['access_token'] = $authToken;
    $_SESSION['refresh_token'] = $refreshToken;
}
