<?php
// Initialize the session
session_start();

// check if user is logged in
if (!isset($_SESSION['access_token'])) {
    header('Location: login.php');
    exit();
}

// Require database connection
require_once "db_connect.php";

// Default Timezone Settings
$defaultTimeZone = 'Etc/UTC';
$user_timezone = $defaultTimeZone;

// Fetch the user's data from the database based on the access_token
$access_token = $_SESSION['access_token'];

$stmt = $conn->prepare("SELECT * FROM users WHERE access_token = ?");
$stmt->bind_param("s", $access_token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];
$username = $user['username'];
$broadcasterID = $user['twitch_id'];
$twitchDisplayName = $user['twitch_display_name'];
$twitch_profile_image_url = $user['profile_image'];
$is_admin = ($user['is_admin'] == 1);
$accessToken = $access_token;
$user_timezone = $user['timezone'];
date_default_timezone_set($user_timezone);

// Determine the greeting based on the user's local time
$currentHour = date('G');
$greeting = '';

if ($currentHour < 12) {
    $greeting = "Good morning";
} else {
    $greeting = "Good afternoon";
}

// API endpoint to fetch moderators
$moderatorsURL = "https://api.twitch.tv/helix/moderation/moderators?broadcaster_id=$broadcasterID";
$clientID = ''; // CHANGE TO MAKE THIS WORK

// Set up cURL request with headers
$curl = curl_init($moderatorsURL);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Client-ID: ' . $clientID
]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// Execute cURL request
$response = curl_exec($curl);

if ($response === false) {
    // Handle cURL error
    echo 'cURL error: ' . curl_error($curl);
    exit;
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ($httpCode !== 200) {
    // Handle non-successful HTTP response
    $HTTPError = 'HTTP error: ' . $httpCode;
    exit;
}

curl_close($curl);

// Process and display moderator information
$moderatorsData = json_decode($response, true);

$existingNames = array();
$existingNamesQuery = "SELECT twitch_display_name FROM users";
$existingNamesResult = mysqli_query($conn, $existingNamesQuery);

if ($existingNamesResult) {
    while ($row = mysqli_fetch_assoc($existingNamesResult)) {
        $existingNames[$row['twitch_display_name']] = true;
    }
} else {
    echo "Failed to fetch existing names from the database.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>YourListOnline - Twitch Mods</title>
    <link rel="stylesheet" href="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.min.css">
    <link rel="stylesheet" href="https://cdn.yourlistonline.com.au/css/custom.css">
    <script src="https://cdn.yourlistonline.com.au/js/about.js"></script>
    <script src="https://cdn.yourlistonline.com.au/js/sorttable.js"></script>
  	<link rel="icon" href="https://cdn.yourlistonline.com.au/img/logo.png" type="image/png" />
  	<link rel="apple-touch-icon" href="https://cdn.yourlistonline.com.au/img/logo.png">
  </head>
<body>
<!-- Navigation -->
<div class="title-bar" data-responsive-toggle="mobile-menu" data-hide-for="medium">
  <button class="menu-icon" type="button" data-toggle="mobile-menu"></button>
  <div class="title-bar-title">Menu</div>
</div>
<nav class="top-bar stacked-for-medium" id="mobile-menu">
  <div class="top-bar-left">
    <ul class="dropdown vertical medium-horizontal menu" data-responsive-menu="drilldown medium-dropdown hinge-in-from-top hinge-out-from-top">
      <li class="menu-text menu-text-black">YourListOnline</li>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="insert.php">Add</a></li>
      <li><a href="remove.php">Remove</a></li>
      <li>
        <a>Update</a>
        <ul class="vertical menu" data-dropdown-menu>
          <li><a href="update_objective.php">Update Objective</a></li>
          <li><a href="update_category.php">Update Objective Category</a></li>
        </ul>
      </li>
      <li><a href="completed.php">Completed</a></li>
      <li>
        <a>Categories</a>
        <ul class="vertical menu" data-dropdown-menu>
          <li><a href="categories.php">View Categories</a></li>
          <li><a href="add_category.php">Add Category</a></li>
        </ul>
      </li>
      <li>
        <a>Profile</a>
        <ul class="vertical menu" data-dropdown-menu>
		    <li><a href="profile.php">View Profile</a></li>
		    <li><a href="update_profile.php">Update Profile</a></li>
            <li><a href="obs_options.php">OBS Viewing Options</a></li>
            <li class="is-active"><a href="twitch_mods.php">Twitch Mods</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
      </li>
      <?php if ($is_admin) { ?>
        <li>
        <a>Admins</a>
        <ul class="vertical menu" data-dropdown-menu>
					<li><a href="../admins/dashboard.php" target="_self">Admin Dashboard</a></li>
        </ul>
      </li>
      <?php } ?>
    </ul>
  </div>
  <div class="top-bar-right">
    <ul class="menu">
      <li><button id="dark-mode-toggle"><i class="icon-toggle-dark-mode"></i></button></li>
      <li><a class="popup-link" onclick="showPopup()">&copy; 2023 YourListOnline. All rights reserved.</a></li>
    </ul>
  </div>
</nav>
<!-- /Navigation -->

<div class="row column">
<br>
<h1><?php echo "$greeting, <img id='profile-image' src='$twitch_profile_image_url' width='50px' height='50px' alt='$twitchDisplayName Profile Image'>$twitchDisplayName!"; ?></h1>
<br>
<?php if ($httpCode !== 200) { echo $HTTPError; exit; } else { ?>
    <h1>Your Moderators:</h1>
    <ul>
        <?php foreach ($moderatorsData['data'] as $moderator) : 
            $modDisplayName = $moderator['user_name'];
        ?>
            <li <?php if (isset($existingNames[$modDisplayName])) echo 'style="color: green;"'; ?>>
                <?= $modDisplayName ?> <?php if (isset($existingNames[$modDisplayName])) echo "(Active)"; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php } ?>
</div>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.js"></script>
<script src="https://cdn.yourlistonline.com.au/js/darkmode.js"></script>
<script>$(document).foundation();</script>
</body>
</html>