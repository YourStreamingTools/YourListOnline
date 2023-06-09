<?php
// Set your Twitch application credentials
$clientID = ''; // CHANGE TO MAKE THIS WORK
$redirectURI = ''; // CHANGE TO MAKE THIS WORK
$clientSecret = ''; // CHANGE TO MAKE THIS WORK

// Database credentials
require_once "db_connect.php";

// Start PHP session
session_start();

// If the user is already logged in, redirect them to the dashboard page
if (isset($_SESSION['access_token'])) {
    header('Location: dashboard.php');
    exit;
}

// If the user is not logged in and no authorization code is present, redirect to Twitch authorization page
if (!isset($_SESSION['access_token']) && !isset($_GET['code'])) {
    header('Location: https://id.twitch.tv/oauth2/authorize' .
        '?client_id=' . $clientID .
        '&redirect_uri=' . $redirectURI .
        '&response_type=code' .
        '&scope=openid');
    exit;
}

// If an authorization code is present, exchange it for an access token
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Exchange the authorization code for an access token
    $tokenURL = 'https://id.twitch.tv/oauth2/token';
    $postData = array(
        'client_id' => $clientID,
        'client_secret' => $clientSecret,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirectURI
    );

    $curl = curl_init($tokenURL);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);

    if ($response === false) {
        // Handle cURL error
        echo 'cURL error: ' . curl_error($curl);
        exit;
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
        // Handle non-successful HTTP response
        echo 'HTTP error: ' . $httpCode;
        exit;
    }

    curl_close($curl);

    // Extract the access token from the response
    $responseData = json_decode($response, true);
    $accessToken = $responseData['access_token'];

    // Store the access token in the session
    $_SESSION['access_token'] = $accessToken;

    // Fetch the user's Twitch username and profile image URL
    $userInfoURL = 'https://api.twitch.tv/helix/users';
    $curl = curl_init($userInfoURL);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $_SESSION['access_token'],
        'Client-ID: ' . $clientID
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $userInfoResponse = curl_exec($curl);

    if ($userInfoResponse === false) {
        // Handle cURL error
        echo 'cURL error: ' . curl_error($curl);
        exit;
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
        // Handle non-successful HTTP response
        echo 'HTTP error: ' . $httpCode;
        exit;
    }

    curl_close($curl);

    $userInfo = json_decode($userInfoResponse, true);

    if (isset($userInfo['data']) && count($userInfo['data']) > 0) {
        $twitchUsername = $userInfo['data'][0]['login'];
        $profileImageUrl = $userInfo['data'][0]['profile_image_url'];

        // Insert/update the access token and profile image URL in the 'users' table
        $insertQuery = "INSERT INTO users (username, access_token, api_key, profile_image, is_admin) VALUES ('$twitchUsername', '$accessToken', '" . bin2hex(random_bytes(16)) . "', '$profileImageUrl', 0)
                    ON DUPLICATE KEY UPDATE access_token = '$accessToken', profile_image = '$profileImageUrl'";
        $insertResult = mysqli_query($conn, $insertQuery);

        if ($insertResult) {
            // Update the last login time
            $last_login = date('Y-m-d H:i:s');
            $sql = "UPDATE users SET last_login = ? WHERE username = '$twitchUsername'";
            // Prepare and execute the update statement
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 's', $last_login);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Redirect the user to the dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            // Handle the case where the insertion failed
            echo "Failed to save user information.";
            exit;
        }
    } else {
        // Failed to fetch user information from Twitch
        echo "Failed to fetch user information from Twitch.";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>YourListOnline - Twitch Login</title>
    <link rel="icon" href="https://cdn.yourlist.online/img/logo.png" sizes="32x32" />
    <link rel="icon" href="https://cdn.yourlist.online/img/logo.png" sizes="192x192" />
    <link rel="apple-touch-icon" href="https://cdn.yourlist.online/img/logo.png" />
    <meta name="msapplication-TileImage" content="https://cdn.yourlist.online/img/logo.png" />
</head>
<body>
    <p>Please wait while we redirect you to Twitch for authorization...</p>
</body>
</html>