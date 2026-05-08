<?php
// Opens the per-user database (named after the streamer's Twitch username) and
// exposes it as $db. Todos, categories, showobs, and profile all live in this DB.
//
// Requires:
//   - /var/www/config/database.php to define $db_servername, $db_username, $db_password
//   - $_SESSION['username'] (already swapped by userdata.php during act-as)

require_once '/var/www/config/database.php';

if (isset($db) && $db instanceof mysqli) {
    $db->close();
    unset($db);
}

$dbname = $_SESSION['username'] ?? '';
if ($dbname === '') {
    error_log('user_db.php: no username in session');
    header('Location: login.php');
    exit;
}

$db = new mysqli($db_servername, $db_username, $db_password, $dbname);
if ($db->connect_error) {
    error_log('user_db.php connect failed for ' . $dbname . ': ' . $db->connect_error);
    die('Connection to your data store failed. Please contact support.');
}
$db->set_charset('utf8mb4');
