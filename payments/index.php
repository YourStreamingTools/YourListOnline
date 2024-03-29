<?php ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); ?>
<?php
// Start session
session_start();

// Store the referrer URL in a session variable
if(isset($_SERVER['HTTP_REFERER'])) {
  $_SESSION['referrer_url'] = $_SERVER['HTTP_REFERER'];
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) && !isset($_SESSION['access_token'])) {
  // Get the referrer domain
  $referrerDomain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

  // Define allowed referrer domains
  $allowedDomains = [
      'access.yourlist.online',
      'discord.yourlist.online',
      'twitch.yourlist.online'
  ];

    // Check if the referrer domain is in the allowed list
    if (in_array($referrerDomain, $allowedDomains)) {
      // Redirect to login page of the respective domain
      $loginUrl = "https://$referrerDomain/login.php";
      header("Location: $loginUrl");
      exit();
  } else {
      // Redirect to a default index page
      header("Location: https://yourlist.online/");
      exit();
  }
}

$DisplayName = 'user';
// User is logged in, fetch user data based on session
include 'db_connect.php';

if (isset($_SESSION['loggedin'])) {
    // Get user information from the database
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);
    $username = $user['username'];
    $DisplayName = $username;
    $twitch_profile_image_url = $user['profile_image'];
    $is_admin = ($user['is_admin'] ==1);
} elseif (isset($_SESSION['access_token'])) {
    // Fetch the user's data from the database based on the access_token
    $access_token = $_SESSION['access_token'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE access_token = ?");
    $stmt->bind_param("s", $access_token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $username = $user['username'];
    $DisplayName = $user['twitch_display_name'];
    $twitch_profile_image_url = $user['profile_image'];
    $is_admin = ($user['is_admin'] == 1);
}

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

// Blank informatin for build
$customer_name = '';
$customer_email= '';
//$paymentssql = "SELECT * FROM payments WHERE user_id = '$user_id'";
//$paymentresults = mysqli_query($conn, $paymentssql);

// Check if the query succeeded
//if (!$paymentresults) {
  //echo "Error: " . mysqli_error($conn);
  //exit();
//}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>YourListOnline - Payments</title>
    <link rel="stylesheet" href="https://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.min.css">
    <link rel="stylesheet" href="https://cdn.yourlist.online/css/custom.css">
    <script src="https://cdn.yourlist.online/js/about.js"></script>
    <script src="https://cdn.yourlist.online/js/darkmode.js"></script>
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
      <li class="is-active"><a href="payments.php">Payments</a></li>
      <li><a href="https://<?php echo $referrerDomain; ?>">BACK</a></li>
    </ul>
  </div>
  <div class="top-bar-right">
    <ul class="menu">
      <!--<li><button id="dark-mode-toggle"><i class="icon-toggle-dark-mode"></i></button></li>-->
      <li><a class="popup-link" onclick="showPopup()">&copy; 2023 YourListOnline. All rights reserved.</a></li>
    </ul>
  </div>
</nav>
<!-- /Navigation -->

<div class="row column">
<br>
<h1><?php echo "$greeting, $DisplayName!"; ?></h1>
<br><!-- 
    PULL DATA FROM THE DATA BASE, IF THE USER HAS A SCRIPE CUSTOMER ACCOUNT WITH US (stripe_cutomer_id)
    ELSE, DISPLY THE ERROR MESSAGE AND CREATE AN SUBSCRIPTION ACCOUTN WITH US
    ANOTHER IF, THE CUTOMER ACCOUNT IS ALREADY WITH US, PULL THE SUBSCRIPTION ID AND ADD IT TO THE DATABASE (stripe_subscription_id)
    EVERYTHING NEED TO GO THOUGH CURL (curl https://api.stripe.com/v1) FOR THE API. 
    CAN PULL DATA USING (-d "expand[]"=customer) AND (-d "expand[]"="invoice.subscription") FOR THE INFORMATION THAT WE NEED 
    (invoice.subscription) DISPLAY A LIST OF INVOICES THAT HAVE BEEN PAID FORE THE USER 
    -->
<table>
  <tr>
    <td width="300px">User Payment Information</td>
    <td width="">Update User Payment Information</td>
  </tr>
  <tr>
    <td>
      <p>Your name: <?php echo $customer_name; ?></p>
      <p>Your Email: <?php echo $customer_email; ?></p>
      <p></p>
    </td>
    <td></td>
</table>
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