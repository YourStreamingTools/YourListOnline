<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to dashboard page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}

// Include config file
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

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password, last_login FROM users WHERE username = ?";

        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);

                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $last_login);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so update last login time and start a new session
                            $last_login = date('Y-m-d H:i:s');
                            $sql = "UPDATE users SET last_login = ? WHERE id = ?";
                            if($stmt = mysqli_prepare($conn, $sql)){
                                // Bind variables to the prepared statement as parameters
                                mysqli_stmt_bind_param($stmt, "si", $last_login, $id);

                                // Attempt to execute the prepared statement
                                if(mysqli_stmt_execute($stmt)){
                                    // Store data in session variables
                                    session_start();
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["user_id"] = $id;
                                    $_SESSION["username"] = $username;                            
                                    $_SESSION["last_login"] = $last_login;

                                    // Redirect user to dashboard page
                                    header("location: dashboard.php");
                                } else{
                                    echo "Oops! Something went wrong. Please try again later.";
                                }
                                mysqli_stmt_close($stmt);
                            }
                        } else{
                            // Display an error message if password is not valid
                            $password_err = "The password you entered was not valid.";
                        }
                    }
                } else{
                    // Display an error message if username doesn't exist
                    $username_err = "No account found with that username.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>YourListOnline - Login</title>
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
      <li class="is-active"><a href="login.php">Login</a></li>
      <li><a href="register.php">Sign Up</a></li>
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
<p><?php echo "$greeting,"; ?> please fill in your credentials to login.
<br>If you have signup on the YourStreamingTools website, you can use those same credentials here.
<br>You can also login via Twitch by clicking the button below.</p>
<form action="login.php" method="post">
<div class="medium-5 large-3 cell">
    <div class="grid-x grid-padding-x">
        <label>Username</label>
        <input type="text" name="username" class="medium-6 cell">
    </div>    
    <div class="grid-x grid-padding-x">
        <label>Password</label>
        <input type="password" name="password" class="medium-6 cell">
    </div>
    <div class="grid-x grid-padding-x">
        <input type="submit" class="defult-button" value="Login">
        <a href="https://yourlist.online">Back to Home</a>
    </div>
    <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
</div>
</form>
<a href="https://twitch.yourlist.online/dashboard.php"><button class="twitch-button">Login with Twitch</button></a>
</div>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.js"></script>
<script>$(document).foundation();</script>
</body>
</html>