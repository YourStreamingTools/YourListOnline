<?php ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); ?>
<?php
// Require database connection
require_once "db_connect.php";

// Initialize the session
session_start();

// Check if the user is already logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
} else {
    // Get the user ID
    $user_id = $_SESSION["user_id"];
}

// Retrieve font and color data for the user from the showobs table
$stmt = $conn->prepare("SELECT * FROM showobs WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$settings = $result->fetch_assoc();
$font = isset($settings['font']) ? $settings['font'] : null;
$colour = isset($settings['colour']) ? $settings['colour'] : null;
?>
<!DOCTYPE html>
<html>
<head>
  <title>YourListOnline - OBS Viewing Options</title>
  <link rel="icon" href="img/logo.png" type="image/png" />
  <link rel="apple-touch-icon" href="img/logo.png">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/about.js"></script>
  <script src="js/obsbutton.js"></script>
  <script src="js/profile.js"></script>
  <style type="text/css">
    body {
      font: 14px sans-serif;
    }
    .wrapper {
      width: 350px; padding: 20px;
    }
    a.popup-link {
      text-decoration: none;
      color: black;
      cursor: pointer;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="https://yourlist.online/">YourListOnline</a>
        </div>
        <ul class="nav navbar-nav">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="insert.php">Add</a></li>
            <li><a href="completed.php">Completed</a></li>
            <li class="dropdown dropdown-hover">
                <a class="dropdown" data-toggle="dropdown">Update <span class="caret"></span></a>
                <ul class="dropdown-menu">
                    <li><a href="update_objective.php">Update Objective</a></li>
                    <li><a href="update_category.php">Update Category</a></li>
                </ul>
            </li>
            <li><a href="remove.php">Remove</a></li>
            <li class="dropdown dropdown-hover">
                <a class="dropdown" data-toggle="dropdown">Categories <span class="caret"></span></a>
                <ul class="dropdown-menu">
                    <li><a href="categories.php">View Categories</a></li>
                    <li><a href="add_category.php">Add Category</a></li>
                </ul>
            </li>
            <li class="dropdown dropdown-hover">
			      <a class="dropdown" data-toggle="dropdown">Profile <span class="caret"></span></a>
			      	<ul class="dropdown-menu">
			      		<li><a href="profile.php">View Profile</a></li>
			      		<li><a href="update_profile.php">Update Profile</a></li>
                        <li class="active"><a href="obs_options.php">OBS Viewing Options</a></li>
                        <li><a href="logout.php">Logout</a></li>
			      	</ul>
            </li>
            <?php if ($_SESSION['is_admin']) { ?>
            <li class="dropdown dropdown-hover">
			      <a class="dropdown" data-toggle="dropdown">Admins <span class="caret"></span></a>
			      	<ul class="dropdown-menu">
                <li><a href="admin.php">Admin Dashboard</a></li>
			      	</ul>
            </li>
            <?php } ?>
        </ul>
        <p class="navbar-text navbar-right"><a class="popup-link" onclick="showPopup()">&copy; <?php echo date("Y"); ?> YourListOnline. All rights reserved.</a></p>
    </div>
</nav>
<div class="col-md-6">
    <h1>Your Profile</h1>
    <img src="<?php echo $twitch_profile_image_url; ?>" width="150px" height="150px" alt="Twitch Profile Image for <?php echo $_SESSION['username']; ?>">
    <br><br>
    <h3>Font and Colour Settings:</h3>
    <form action="save_obs_options.php" method="post">
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        <div class="form-group">
            <label for="font">Font:</label>
            <input type="text" name="font" class="form-control" value="<?php echo $font; ?>">
        </div>
        <div class="form-group">
            <label for="colour">Color:</label>
            <input type="text" name="colour" class="form-control" value="<?php echo $colour; ?>">
        </div>
        <input type="submit" value="Save" class="btn btn-primary">
    </form>
</div>
</body>
</html>