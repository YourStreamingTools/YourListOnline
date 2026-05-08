<?php
require_once '/var/www/lib/session_bootstrap.php';

if (empty($_SESSION['admin_act_as_active'])) {
    header('Location: dashboard.php');
    exit;
}

$original = $_SESSION['admin_act_as_original'] ?? null;
if (!is_array($original) || empty($original['access_token'])) {
    unset(
        $_SESSION['admin_act_as_active'],
        $_SESSION['admin_act_as_started_at'],
        $_SESSION['admin_act_as_actor_user_id'],
        $_SESSION['admin_act_as_actor_username'],
        $_SESSION['admin_act_as_actor_role'],
        $_SESSION['admin_act_as_target_user_id'],
        $_SESSION['admin_act_as_target_username'],
        $_SESSION['admin_act_as_target_display_name'],
        $_SESSION['admin_act_as_original']
    );
    header('Location: login.php');
    exit;
}

$_SESSION['user_id']       = $original['user_id'] ?? null;
$_SESSION['username']      = $original['username'] ?? '';
$_SESSION['twitchUserId']  = $original['twitchUserId'] ?? '';
$_SESSION['access_token']  = $original['access_token'] ?? '';
$_SESSION['refresh_token'] = $original['refresh_token'] ?? '';
$_SESSION['api_key']       = $original['api_key'] ?? '';
$_SESSION['is_admin']      = $original['is_admin'] ?? false;

unset(
    $_SESSION['admin_act_as_active'],
    $_SESSION['admin_act_as_started_at'],
    $_SESSION['admin_act_as_actor_user_id'],
    $_SESSION['admin_act_as_actor_username'],
    $_SESSION['admin_act_as_actor_role'],
    $_SESSION['admin_act_as_target_user_id'],
    $_SESSION['admin_act_as_target_username'],
    $_SESSION['admin_act_as_target_display_name'],
    $_SESSION['admin_act_as_original']
);

session_write_close();
header('Location: mod_channels.php?act_as=stopped');
exit;
