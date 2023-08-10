<?php
// Initialize the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit();
}

// Require database connection
require_once "db_connect.php";
// Fetch the user's data from the database
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

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

// Define variables and initialize with empty values
$current_password = $new_password = $confirm_password = "";
$current_password_err = $new_password_err = $confirm_password_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

// Validate current password
if (empty(trim($_POST["current_password"]))) {
    $current_password_err = "Please enter your current password.";
} else {
    $current_password = trim($_POST["current_password"]);
}
// Validate new password
if (empty(trim($_POST["new_password"]))) {
    $new_password_err = "Please enter a new password.";
} elseif (strlen(trim($_POST["new_password"])) < 8) {
    $new_password_err = "Password must have at least 8 characters.";
} else {
    $new_password = trim($_POST["new_password"]);
}

// Check input errors before updating the database
if (empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
    // Check current password
    $sql = "SELECT password FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_assoc($result);
                    $hashed_password = $row["password"];
                    if (password_verify($current_password, $hashed_password)) {
                        // Update password
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "ss", $hashed_new_password, $user_id);
                            if (mysqli_stmt_execute($stmt)) {
                                if ($stmt->affected_rows > 0) {
                                    // Password updated successfully, redirect to login page
                                    header("Location: logout.php");
                                    exit();
                                } else {
                                    $error_message = "Oops! Something went wrong. Please try again later.";
                                }
                            } else {
                                $error_message = "Error executing the password update query: " . mysqli_stmt_error($stmt);
                            }
                        } else {
                            $error_message = "Error preparing the password update statement: " . mysqli_error($conn);
                        }
                    } else {
                        $current_password_err = "The password you entered is not valid.";
                    }
                } else {
                    $error_message = "User not found or multiple users with the same ID exist.";
                }
            } else {
                $error_message = "Error fetching the result of the password select query: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Error executing the password select query: " . mysqli_stmt_error($stmt);
        }
    } else {
        $error_message = "Error preparing the password select statement: " . mysqli_error($conn);
    }
}

    // Close database connection
    mysqli_close($conn);
    header("Location: logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>YourListOnline - Change Password</title>
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
          <li class="is-active"><a href="add_category.php">Add Category</a></li>
        </ul>
      </li>
      <li>
        <a>Profile</a>
        <ul class="vertical menu" data-dropdown-menu>
					<li><a href="profile.php">View Profile</a></li>
					<li><a href="update_profile.php">Update Profile</a></li>
          <li><a href="obs_options.php">OBS Viewing Options</a></li>
          <li><a href="logout.php">Logout</a></li>
        </ul>
      </li>
      <?php if ($_SESSION['is_admin']) { ?>
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
<h3>Change Your Password</h3>
<?php if (isset($error_message)) { ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php } ?>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <div class="medium-5 large-5 cell">
        <label for="current_password">Current Password:</label>
        <input type="password" class="form-control" id="current_password" name="current_password" required>
    </div>
    <div class="medium-5 large-5 cell">
        <label for="new_password">New Password:</label>
        <input type="password" class="form-control" id="new_password" name="new_password" required>
    </div>
    <button type="submit" class="defult-button">Submit</button>
</form>
</div>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.js"></script>
<script>$(document).foundation();</script>
<script>
  // JavaScript function to handle the category filter change
  document.getElementById("categoryFilter").addEventListener("change", function() {
    var selectedCategoryId = this.value;
    // Redirect to the page with the selected category filter
    window.location.href = "dashboard.php?category=" + selectedCategoryId;
  });
</script>
</body>
</html>