<?php
// Loads the channels the current actor (real session user, not the act-as target)
// can switch into. Result lands in $modChannels as an array of associative rows
// with twitch_display_name, profile_image, twitch_user_id.
//
// Requires:
//   - $conn (mysqli to website DB)
//   - $_SESSION['username'] (might be the act-as target's username, so we also
//     read admin_act_as_actor_username if the act-as flow is active)

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once '/var/www/config/db_connect.php';
}

$actorUsername = isset($_SESSION['admin_act_as_active']) && $_SESSION['admin_act_as_active'] === true
    ? ($_SESSION['admin_act_as_actor_username'] ?? ($_SESSION['username'] ?? ''))
    : ($_SESSION['username'] ?? '');

$actorTwitchId = '';
if ($actorUsername !== '') {
    $stmt = $conn->prepare("SELECT twitch_user_id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $actorUsername);
    if ($stmt->execute()) {
        $actorRow = $stmt->get_result()->fetch_assoc();
        $actorTwitchId = (string) ($actorRow['twitch_user_id'] ?? '');
    }
    $stmt->close();
}

$modChannels = [];
$botId = '971436498'; // BotOfTheSpecter — sees every channel

if ($actorTwitchId !== '' && $actorTwitchId === $botId) {
    $stmt = $conn->prepare("SELECT twitch_display_name, profile_image, twitch_user_id, username FROM users ORDER BY twitch_display_name ASC");
    if ($stmt && $stmt->execute()) {
        $modChannels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} elseif ($actorTwitchId !== '') {
    $stmt = $conn->prepare(
        "SELECT u.twitch_display_name, u.profile_image, u.twitch_user_id, u.username
         FROM moderator_access ma
         JOIN users u ON ma.broadcaster_id = u.twitch_user_id
         WHERE ma.moderator_id = ?
         ORDER BY u.twitch_display_name ASC"
    );
    if ($stmt) {
        $stmt->bind_param('s', $actorTwitchId);
        if ($stmt->execute()) {
            $modChannels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }
}
