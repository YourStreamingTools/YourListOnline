<?php
// Initialize the session
session_start();

// check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit();
}

// Require database connection
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

// Get user information from the database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user_data = mysqli_fetch_assoc($result);
$is_admin = $user_data['is_admin'];
$username = $user_data['username'];
$change_password = $user_data['change_password'];

// Get user's Twitch profile image URL
$url = 'https://decapi.me/twitch/avatar/' . $username;

// Initialize cURL session
$curl = curl_init();
// Set cURL options
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL => $url,
));
// Execute cURL request and get response
$response = curl_exec($curl);
// Close cURL session
curl_close($curl);
// Set Twitch profile image URL to the response
$twitch_profile_image_url = trim($response);

// Check if form has been submitted to update the username
if (isset($_POST['update_username'])) {
    // Get new username from form data
    $new_username = $_POST['twitch_username'];

    // Update user's username in database
    $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->bind_param("si", $new_username, $_SESSION['user_id']);
    $stmt->execute();

    // Redirect to profile page
    header("Location: logout.php");
    exit();
}

// Check if form has been submitted to update the profile image
if (isset($_POST['update_profile_image'])) {
    // Get new profile image URL from form data
    $twitch_profile_image_url = $_POST['twitch_profile_image_url'];

    // Update user's profile image URL in database
    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
    $stmt->bind_param("si", $twitch_profile_image_url, $_SESSION['user_id']);
    $stmt->execute();
    // Redirect to profile page
    header("Location: profile.php");
    exit();
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>YourListOnline - Update Profile</title>
    <link rel="stylesheet" href="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.min.css">
    <link rel="stylesheet" href="https://cdn.yourlist.online/css/custom.css">
    <script src="https://cdn.yourlist.online/js/about.js"></script>
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
			  <li><a href="profile.php">View Profile</a></li>
		    <li class="is-active"><a href="update_profile.php">Update Profile</a></li>
        <li><a href="obs_options.php">OBS Viewing Options</a></li>
        <?php if ($change_password) { ?><li><a href="change_password.php">Change Password</a></li><?php } ?>
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
<h1><?php echo "$greeting, $username!"; ?></h1>
<br>
<table>
    <tr>
    <th width="210">Update Username</th>
    <th width="300">Update Profile Image</th>
    </tr>
    <tbody>
    <tr>
        <td>
            <form action="update_profile.php" method="POST">
            <div>
            <label for="twitch_username">Twitch Username:</label>
            <input type="text" id="twitch_username" name="twitch_username" value="<?php echo $username; ?>">
            <button class="save-button" type="submit" name="update_username">Update Username</button>
            </div>
            </form>
        </td>
        <td>
            <form id="update-profile-image-form" action="update_profile.php" method="POST">
            <div><img id="profile-image" src="<?php echo $twitch_profile_image_url; ?>" width="100px" height="100px" alt="New Profile Image"></div>
            <div>
            <input type="hidden" name="twitch_profile_image_url" value="<?php echo $twitch_profile_image_url; ?>">
            <button class="save-button" id="update-profile-image-button" name="update_profile_image">Update New Profile Image</button>
            </div>
            </form>
        </td>
    </tr>
    </tbody>
</table>
<div class="row column">
<p>Before you switch over to the Twitch Login;<br>Make sure your username above is the same as twitch.<br>This will insure the tasks sync over correctly.</p>
<a href="https://twitch.yourlist.online/login.php"><button class="twitch-button">Switch to Twitch Login</button></a>
</div>
</div>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.js"></script>
<script>$(document).foundation();</script>
</body>
</html>