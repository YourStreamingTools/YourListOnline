<?php
// Shared authentication bootstrap.
// Pages include this immediately after starting work. After return:
//   $conn          - mysqli to the website (primary) DB
//   $db            - mysqli to the active streamer's per-user DB (todos live here)
//   $user_id       - website.users.id of the active streamer (target when acting as)
//   $username      - the active streamer's Twitch login (also their per-user DB name)
//   $twitchUserId  - the active streamer's Twitch numeric user_id
//   $twitchDisplayName, $twitch_profile_image_url, $email, $api_key
//   $is_admin, $betaAccess, $authToken, $broadcasterID, $timezone
//   $isActingAs    - true when an admin or moderator is acting as this streamer
//   $modChannels   - channels the actor can act as (built by mod_access.php)
//
// During an act-as session, the moderator's original session bundle is preserved
// in $_SESSION['admin_act_as_original'] and the target's data is loaded instead.

require_once '/var/www/lib/session_bootstrap.php';

if (!isset($_SESSION['access_token'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
    header('Location: login.php');
    exit;
}

require_once '/var/www/config/db_connect.php';
require_once '/var/www/config/twitch.php';

require_once __DIR__ . '/userdata.php';
require_once __DIR__ . '/mod_access.php';
require_once __DIR__ . '/user_db.php';

session_write_close();

$timezone = 'UTC';
if ($db) {
    $tzStmt = $db->prepare("SELECT timezone FROM profile LIMIT 1");
    if ($tzStmt && $tzStmt->execute()) {
        $tzRow = $tzStmt->get_result()->fetch_assoc();
        if (!empty($tzRow['timezone'])) {
            $timezone = $tzRow['timezone'];
        }
        $tzStmt->close();
    }
}
date_default_timezone_set($timezone);

$currentHour = (int) date('G');
$greeting = ($currentHour < 12) ? 'Good morning' : (($currentHour < 18) ? 'Good afternoon' : 'Good evening');
