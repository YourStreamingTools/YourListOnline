<?php
// Initialize the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Require database connection
require_once "db_connect.php";

// Get user information from the database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user_data = mysqli_fetch_assoc($result);
$is_admin = $user_data['is_admin'];
$username = $user_data['username'];
$change_password = $user_data['change_password'];

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

// Get categories from database for the logged-in user
$query = "SELECT * FROM categories WHERE user_id = '$user_id' OR user_id IS NULL";
$result = $conn->query($query);

if (!$result) {
    die("Error retrieving categories: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>YourListOnline - View Categories</title>
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
          <li class="is-active"><a href="categories.php">View Categories</a></li>
          <li><a href="add_category.php">Add Category</a></li>
        </ul>
      </li>
      <li>
        <a>Profile</a>
        <ul class="vertical menu" data-dropdown-menu>
					<li><a href="profile.php">View Profile</a></li>
					<li><a href="update_profile.php">Update Profile</a></li>
          <li><a href="obs_options.php">OBS Viewing Options</a></li>
          <?php if ($change_password) { ?> <li><a href="change_password.php">Change Password</a></li>  <?php } ?>
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
<p>Here is the current list of categories you can filter your lists in, each category is it's own list.<br>
   The categories in this list are either created by you or publicly accessible.</p>
<table>
  <thead>
    <tr>
      <th width="50">ID</th>
      <th width="100%">Category</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['id']) ?></td>
      <td><?php echo htmlspecialchars($row['category']) ?></td>
    </tr>
    <?php endwhile ?>
  </tbody>
</table=>
</div>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.js"></script>
<script>$(document).foundation();</script>
<script>
  // JavaScript function to handle the category filter change
  document.getElementById("categoryFilter").addEventListener("change", function() {
    var selectedCategoryId = this.value;
    // Redirect to the page with the selected category filter
    window.location.href = "categories.php?category=" + selectedCategoryId;
  });
</script>
</body>
</html>