<?php
// Initialize the session
session_start();

// check if user is logged in
if (!isset($_SESSION['access_token'])) {
    header('Location: login.php');
    exit();
}

// Connect to database
require_once "db_connect.php";

// Get the current hour in 24-hour format (0-23)
$currentHour = date('G');
// Initialize the greeting variable
$greeting = '';
// Check if it's before 12 PM (noon)
if ($currentHour < 12) {
    $greeting = "Good morning";
} else {
    $greeting = "Good afternoon";
}

// Fetch the user's data from the database based on the access_token
$access_token = $_SESSION['access_token'];
$stmt = $conn->prepare("SELECT * FROM users WHERE access_token = ?");
$stmt->bind_param("s", $access_token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];
$username = $user['username'];
$twitchDisplayName = $user['twitch_display_name'];
$twitch_profile_image_url = $user['profile_image'];
$signup_date = $user['signup_date'];
$last_login = $user['last_login'];
$api_key = $user['api_key'];
$is_admin = ($user['is_admin'] == 1);

// Convert the stored date and time to UTC using Sydney time zone (AEST/AEDT)
date_default_timezone_set('Australia/Sydney');
$signup_date_utc = date_create_from_format('Y-m-d H:i:s', $signup_date)->setTimezone(new DateTimeZone('UTC'))->format('F j, Y g:i A');
$last_login_utc = date_create_from_format('Y-m-d H:i:s', $last_login)->setTimezone(new DateTimeZone('UTC'))->format('F j, Y g:i A');

// Determine the tester status message based on the flags
$alpha_user_flag = $user['alpha_user'];
$beta_user_flag = $user['beta_user'];
$tester_status = "";

if ($alpha_user_flag && $beta_user_flag) {
    $tester_status = "Alpha & Beta Tester";
} elseif ($alpha_user_flag) {
    $tester_status = "Alpha Tester";
} elseif ($beta_user_flag) {
    $tester_status = "Beta Tester";
} else {
    $tester_status = "Not A Tester";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>YourListOnline - Profile</title>
    <link rel="stylesheet" href="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.min.css">
    <link rel="stylesheet" href="https://cdn.yourlist.online/css/custom.css">
    <link rel="icon" href="https://cdn.yourlist.online/img/logo.png" type="image/png" />
    <link rel="apple-touch-icon" href="https://cdn.yourlist.online/img/logo.png">
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
            <li class="menu-text">YourListOnline</li>
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
                    <li class="is-active"><a href="profile.php">View Profile</a></li>
                    <li><a href="update_profile.php">Update Profile</a></li>
                    <li><a href="obs_options.php">OBS Viewing Options</a></li>
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
            <li><a class="popup-link" onclick="showPopup()">&copy; 2023 YourListOnline. All rights reserved.</a></li>
        </ul>
    </div>
</nav>
<!-- /Navigation -->
<div class="row column">
    <br>
    <h1><?php echo "$greeting, $twitchDisplayName!"; ?></h1>
    <h2>Your Profile</h2>
    <img src="<?php echo $twitch_profile_image_url; ?>" width="150px" height="150px" alt="Twitch Profile Image for <?php echo $username; ?>">
    <br><br>
    <p><strong>Your Username:</strong> <?php echo $username; ?></p>
    <p><strong>Display Name:</strong> <?php echo $twitchDisplayName; ?></p>
    <p><strong>You Joined:</strong> <span id="localSignupDate"></span></p>
    <p><strong>Your Last Login:</strong> <span id="localLastLogin"></span></p>
    <p><strong>Tester Status:</strong> <?php echo $tester_status; ?></p>
    <p><strong>Your API Key:</strong> <span class="api-key-wrapper" style="display: none;"><?php echo $api_key; ?></span></p>
    <button type="button" class="defult-button" id="show-api-key">Show API Key</button>
    <button type="button" class="defult-button" id="hide-api-key" style="display:none;">Hide API Key</button>
    <br><br>
    <button class="defult-button" onclick="showOBSInfo()">HOW TO PUT ON YOUR STREAM</button>
    <br><br>
    <a href="logout.php" class="logout-button">Logout</a>
</div>
<!-- Include the JavaScript files -->
<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://cdn.yourlist.online/js/profile.js"></script>
<script src="https://cdn.yourlist.online/js/about.js" defer></script>
<script src="https://cdn.yourlist.online/js/obsbutton.js" defer></script>
<script src="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.js"></script>
<script>$(document).foundation();</script>
<script src="https://cdn.yourlist.online/js/timezone.js"></script>

<!-- JavaScript code to convert and display the dates -->
<script>
  // Function to convert UTC date to local date in the desired format
  function convertUTCToLocalFormatted(utcDateStr) {
    const options = {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: 'numeric',
      minute: 'numeric',
      hour12: true,
      timeZoneName: 'short'
    };
    const utcDate = new Date(utcDateStr + ' UTC');
    const localDate = new Date(utcDate.toLocaleString('en-US', { timeZone: 'Australia/Sydney' }));
    const dateTimeFormatter = new Intl.DateTimeFormat('en-US', options);
    return dateTimeFormatter.format(localDate);
  }

  // PHP variables holding the UTC date and time
  const signupDateUTC = "<?php echo $signup_date_utc; ?>";
  const lastLoginUTC = "<?php echo $last_login_utc; ?>";

  // Display the dates in the user's local time zone
  document.getElementById('localSignupDate').innerText = convertUTCToLocalFormatted(signupDateUTC);
  document.getElementById('localLastLogin').innerText = convertUTCToLocalFormatted(lastLoginUTC);
</script>
</body>
</html>