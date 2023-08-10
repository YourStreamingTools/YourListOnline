<?php
// start session
session_start();

// include database connection
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

// define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";

// set this variable to true or false depending on whether registration is enabled or not
$registration_enabled = true;

// process form data when the form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && $registration_enabled){

    // validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";

        if($stmt = $conn->prepare($sql)){
            // bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);

            // set parameters
            $param_username = trim($_POST["username"]);

            // attempt to execute the prepared statement
            if($stmt->execute()){
                // store result
                $stmt->store_result();

                if($stmt->num_rows == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // close statement
            $stmt->close();
        }
    }

    // validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // check input errors before inserting into database
    if(empty($username_err) && empty($password_err)){

        // prepare an insert statement
        $sql = "INSERT INTO users (username, password, api_key, is_admin) VALUES (?, ?, ?, ?)";

        if($stmt = $conn->prepare($sql)){
            // bind variables to the prepared statement as parameters
            $stmt->bind_param("sssi", $param_username, $param_password, $param_api_key, $param_is_admin);

            // set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // creates a password hash
            $param_api_key = bin2hex(random_bytes(16)); // generate api key
            $param_is_admin = 0; // set is_admin to false

            // attempt to execute the prepared statement
            if($stmt->execute()){
                // redirect to login page
                header("location: login.php");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // close statement
            $stmt->close();
        }
    }

    // close connection
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>YourListOnline - Register</title>
    <link rel="stylesheet" href="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.min.css">
    <link rel="stylesheet" href="https://cdn.yourlist.online/css/custom.css">
    <script src="https://cdn.yourlist.online/js/about.js"></script>
  	<link rel="icon" href="https://cdn.yourlist.online/img/logo.png" type="image/png" />
  	<link rel="apple-touch-icon" href="https://cdn.yourlist.online/img/logo.png">
  </head>
<body>
<!-- Navigation -->
<nav class="top-bar stacked-for-medium">
  <div class="top-bar-left">
    <ul class="menu horizontal">
      <li class="menu-text">YourListOnline</li>
      <li><a href="https://yourlist.online">Home</a></li>
      <li><a href="login.php">Login</a></li>
      <li class="is-active"><a href="register.php">Sign Up</a></li>
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
<p><?php echo "$greeting,"; ?> please fill this form to create an account.<br>Welcome to OPEN BETA!</p>
<p>You can also login via Twitch by clicking the button below.</p>
<?php if (!$registration_enabled) { echo '<div id="registration-error" style="color: red;">Registration is currently disabled.</div>'; } ?>
<form action="register.php" method="post">
<div class="medium-5 large-3 cell">
  <div class="grid-x grid-padding-x">
    <label>Username</label>
    <input type="text" name="username" class="form-control">
  </div>   
  <div class="grid-x grid-padding-x">
    <label>Password</label>
    <input type="password" name="password" class="form-control">
  </div>
  <div class="grid-x grid-padding-x">
    <input type="submit" class="defult-button" value="Submit">
    <input type="reset" value="Reset">
  </div>
  <p>Already have an account? <a href="login.php">Login here</a>.</p>
</div>
</form>
<a href="https://twitch.yourlist.online/dashboard.php"><button class="twitch-button">Login with Twitch</button></a>
</div>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.js"></script>
<script>$(document).foundation();</script>
</body>
</html>