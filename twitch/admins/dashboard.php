<?php
// Initialize the session
session_start();

// check if user is logged in
if (!isset($_SESSION['access_token'])) {
    header('Location: ../login.php');
    exit();
}

// Connect to database
require_once "../db_connect.php";

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
$is_admin = ($user['is_admin'] == 1);

// Check if the user is an admin
if ($is_admin) {
  // Get the category filter value if set
  $categoryFilter = $_GET['categoryFilter'] ?? 'all';
  
  // Get the search keyword from the form
  $searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';
  
  // Build the SQL query based on the category filter and search keyword
  $sql = "SELECT todos.*, users.username FROM todos INNER JOIN users ON todos.user_id = users.id";
  
  if ($categoryFilter !== 'all') {
      // Add a WHERE condition to filter by category
      $sql .= " INNER JOIN categories ON todos.category = categories.id WHERE categories.id = '$categoryFilter'";
  }
  
  if (!empty($searchKeyword)) {
      // Add a WHERE condition to filter by objective containing the search keyword
      $sql .= " AND todos.objective LIKE '%$searchKeyword%'";
  }
  
  $sql .= " ORDER BY todos.id ASC";
  
  $result = mysqli_query($conn, $sql);
  
  // Handle errors
  if (!$result) {
      echo "Error: " . mysqli_error($conn);
      exit();
  }
} else {
  // The user is not an admin, redirect to dashboard.php
  header("Location: ../dashboard.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>YourListOnline - Admin Dashboard</title>
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
    <ul class="dropdown vertical medium-horizontal menu" data-dropdown-menu data-responsive-menu="drilldown medium-dropdown">
      <li class="menu-text">YourListOnline</li>
      <li><a href="../dashboard.php">User Dashboard</a></li>
      <li class="is-active"><a href="dashboard.php">Admin Dashboard</a></li>
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
<h1><?php echo "$greeting, <img id='profile-image' src='$twitch_profile_image_url' width='50px' height='50px' alt='$twitchDisplayName Profile Image'>$twitchDisplayName!"; ?></h1>
<br>
<!-- Category Filter Dropdown & Search Bar-->
<div class="search-and-filter">
  <form method="GET" action="">
    <input type="text" name="search" placeholder="Search todos" class="search-input">
  </form>
  <select id="categoryFilter" onchange="applyCategoryFilter()">
    <option value="all" <?php if ($categoryFilter === 'all') echo 'selected'; ?>>All</option>
    <?php
        $categories_sql = "SELECT * FROM categories WHERE user_id = '$user_id' OR user_id IS NULL";
        $categories_result = mysqli_query($conn, $categories_sql);
        while ($category_row = mysqli_fetch_assoc($categories_result)) {
            $categoryId = $category_row['id'];
            $categoryName = $category_row['category'];
            $selected = ($categoryFilter == $categoryId) ? 'selected' : '';
            echo "<option value=\"$categoryId\" $selected>$categoryName</option>";
        }
    ?>
  </select>
</div>
<!-- /Category Filter Dropdown & Search Bar -->
<?php echo "Number of total tasks in the category: " . mysqli_num_rows($result); ?>
<table>
  <thead>
    <tr>
      <th>Username</th>
      <th>Objective</th>
      <th width="400">Category</th>
      <th width="600">Created</th>
      <th width="600">Last Updated</th>
      <th width="200">Completed</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <tr>
        <td><?php echo $row['username']; ?></td>
        <td><?php echo ($row['completed'] == 'Yes') ? '<s>' . $row['objective'] . '</s>' : $row['objective']; ?></td>
        <td>
          <?php
            $category_id = $row['category'];
            $category_sql = "SELECT category FROM categories WHERE id = '$category_id'";
            $category_result = mysqli_query($conn, $category_sql);
            $category_row = mysqli_fetch_assoc($category_result);
            echo $category_row['category'];
          ?>
        </td>
        <td><?php echo $row['created_at']; ?></td>
        <td><?php echo $row['updated_at']; ?></td>
        <td><?php echo $row['completed']; ?></td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

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