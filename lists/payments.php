<?php ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); ?>
<?php
// Start session
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

// Fetch the user's data from the database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);

// Check if the query succeeded
if (!$result) {
  echo "Error: " . mysqli_error($conn);
  exit();
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
		    <li><a href="update_profile.php">Update Profile</a></li>
            <li><a href="obs_options.php">OBS Viewing Options</a></li>
            <li class="is-active"><a href="payments.php">Payments</a></li>
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