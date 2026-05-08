<?php
// StreamersConnect-based Twitch login. Mirrors BotOfTheSpecter so accounts in
// the website.users table are shared between the two front-ends.

require_once '/var/www/lib/session_bootstrap.php';
require_once '/var/www/config/twitch.php';

$IDScope = 'openid user:read:moderated_channels moderator:read:moderators user:read:email user:read:follows';
$info = 'Please wait while we redirect you to Twitch for authorization.';

if (isset($_SESSION['access_token'])) {
    $loginRedirect = 'dashboard.php';
    if (!empty($_SESSION['redirect_after_login'])) {
        $candidate = $_SESSION['redirect_after_login'];
        if (strncmp($candidate, '/', 1) === 0 && strncmp($candidate, '//', 2) !== 0) {
            $loginRedirect = $candidate;
        }
        unset($_SESSION['redirect_after_login']);
    }
    header('Location: ' . $loginRedirect);
    exit;
}

if (!isset($_GET['code']) && !isset($_GET['auth_data']) && !isset($_GET['auth_data_sig']) && !isset($_GET['server_token'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ? 'https' : 'http';
    $originDomain = $_SERVER['HTTP_HOST'];
    $returnUrl    = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $authUrl = 'https://streamersconnect.com/?' . http_build_query([
        'service'    => 'twitch',
        'login'      => $originDomain,
        'scopes'     => $IDScope,
        'return_url' => $returnUrl,
    ]);
    header('Location: ' . $authUrl);
    exit;
}

if (isset($_GET['auth_data']) || isset($_GET['auth_data_sig']) || isset($_GET['server_token'])) {
    $decoded = null;
    $cfg = require '/var/www/config/main.php';
    $apiKey = $cfg['streamersconnect_api_key'] ?? '';

    if (isset($_GET['auth_data_sig']) && $apiKey) {
        $sig = $_GET['auth_data_sig'];
        $ch = curl_init('https://streamersconnect.com/verify_auth_sig.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['auth_data_sig' => $sig]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'X-API-Key: ' . $apiKey]);
        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response && $http === 200) {
            $res = json_decode($response, true);
            if (!empty($res['success']) && !empty($res['payload'])) $decoded = $res['payload'];
        }
    }
    if (!$decoded && isset($_GET['server_token']) && $apiKey) {
        $token = $_GET['server_token'];
        $ch = curl_init('https://streamersconnect.com/token_exchange.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['server_token' => $token]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'X-API-Key: ' . $apiKey]);
        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response && $http === 200) {
            $res = json_decode($response, true);
            if (!empty($res['success']) && !empty($res['payload'])) $decoded = $res['payload'];
        }
    }
    if (!$decoded && isset($_GET['auth_data'])) {
        $decoded = json_decode(base64_decode($_GET['auth_data']), true);
    }

    if (!is_array($decoded) || empty($decoded['success']) || ($decoded['service'] ?? '') !== 'twitch') {
        $info = 'Authentication failed or was cancelled. <a href="login.php">Try again</a>.';
    } else {
        $accessToken      = $decoded['access_token'] ?? null;
        $refreshToken     = $decoded['refresh_token'] ?? null;
        $userInfo         = $decoded['user'] ?? [];
        $twitchDisplay    = $userInfo['display_name'] ?? ($userInfo['global_name'] ?? null);
        $twitchUsername   = $userInfo['login'] ?? ($userInfo['username'] ?? null);
        $profileImageUrl  = $userInfo['profile_image_url'] ?? null;
        $twitchUserId     = $userInfo['id'] ?? null;
        $email            = $userInfo['email'] ?? '';

        if ($accessToken && $twitchUserId) {
            $_SESSION['access_token']  = $accessToken;
            $_SESSION['refresh_token'] = $refreshToken;
            $_SESSION['username']      = $twitchUsername;
            $_SESSION['twitchUserId']  = $twitchUserId;
            $_SESSION['profile_image'] = $profileImageUrl;
            $_SESSION['display_name']  = $twitchDisplay;

            $expiresIn = isset($decoded['expires_in']) ? (int) $decoded['expires_in'] : 14400;
            $_SESSION['twitch_expires_at']  = time() + $expiresIn;
            $_SESSION['last_validated_at']  = time();

            require_once '/var/www/config/db_connect.php';

            $restrictStmt = $conn->prepare("SELECT id FROM restricted_users WHERE twitch_user_id = ? OR username = ?");
            $restrictStmt->bind_param('ss', $twitchUserId, $twitchUsername);
            $restrictStmt->execute();
            $restrictStmt->store_result();
            if ($restrictStmt->num_rows > 0) {
                $restrictStmt->close();
                $_SESSION = [];
                session_destroy();
                $info = 'Your account has been banned from using this system. If you believe this is a mistake, please contact us at support@yourlistonline.com.au.';
            } else {
                $restrictStmt->close();

                $existing = $conn->prepare("SELECT id, api_key FROM users WHERE twitch_user_id = ?");
                $existing->bind_param('s', $twitchUserId);
                $existing->execute();
                $existing->store_result();

                if ($existing->num_rows > 0) {
                    $existing->bind_result($foundId, $foundKey);
                    $existing->fetch();
                    $existing->close();
                    $_SESSION['user_id'] = $foundId;
                    $_SESSION['api_key'] = $foundKey;
                    $update = $conn->prepare("UPDATE users SET access_token = ?, refresh_token = ?, profile_image = ?, username = ?, twitch_display_name = ?, last_login = NOW(), email = ? WHERE twitch_user_id = ?");
                    $update->bind_param('sssssss', $accessToken, $refreshToken, $profileImageUrl, $twitchUsername, $twitchDisplay, $email, $twitchUserId);
                    $update->execute();
                    $update->close();
                } else {
                    $existing->close();
                    $apiKey = bin2hex(random_bytes(16));
                    $_SESSION['api_key'] = $apiKey;
                    $insert = $conn->prepare("INSERT INTO users (username, access_token, refresh_token, api_key, profile_image, twitch_user_id, twitch_display_name, email, is_admin, signup_date, last_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())");
                    $insert->bind_param('ssssssss', $twitchUsername, $accessToken, $refreshToken, $apiKey, $profileImageUrl, $twitchUserId, $twitchDisplay, $email);
                    if ($insert->execute()) {
                        $_SESSION['user_id'] = $insert->insert_id;
                    }
                    $insert->close();
                }

                $loginRedirect = 'dashboard.php';
                if (!empty($_SESSION['redirect_after_login'])) {
                    $candidate = $_SESSION['redirect_after_login'];
                    if (strncmp($candidate, '/', 1) === 0 && strncmp($candidate, '//', 2) !== 0) {
                        $loginRedirect = $candidate;
                    }
                    unset($_SESSION['redirect_after_login']);
                }
                header('Location: ' . $loginRedirect);
                exit;
            }
        } else {
            $info = 'Failed to parse authentication data from StreamersConnect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YourListOnline &mdash; Twitch Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/dashboard.css">
  <link rel="icon" href="https://cdn.yourlist.online/img/logo.png" type="image/png" />
  <style>
    body { background: #121212; color: #f5f5f5; min-height: 100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .login-card { max-width: 480px; padding: 2rem; border-radius: 12px; background: #1a1a1a; box-shadow: 0 6px 24px rgba(0,0,0,0.5); text-align: center; }
    .login-card img { width: 96px; }
    .spinner { width:48px; height:48px; border:5px solid #6441a5; border-top-color:transparent; border-radius:50%; animation: spin 1s linear infinite; margin: 1rem auto 0; }
    @keyframes spin { to { transform: rotate(360deg); } }
    a { color: #a78bfa; }
  </style>
</head>
<body>
  <div class="login-card">
    <img src="https://cdn.yourlist.online/img/logo.png" alt="YourListOnline">
    <h1 style="margin: 1rem 0 0.5rem;">YourListOnline</h1>
    <p><?php echo $info; ?></p>
    <div class="spinner"></div>
  </div>
</body>
</html>
