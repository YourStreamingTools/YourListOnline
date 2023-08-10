<?php
// Require database connection
require_once "lists/db_connect.php";

if (!isset($_GET['api']) || empty($_GET['api'])) {
    // Display missing API Key Error
    echo "Please provide your API key in the URL like this: obs.php?api=API_KEY";
    echo "<br>Get your API Key from your <a href='https://access.yourlist.online/profile.php'>profile</a>";
    echo "<br>If you wish to define a working category, please add it like this:";
    echo "<br>https://yourlist.online/obs.php?api=API_KEY&category=1";
    echo "<br>(where ID 1 is called Default defined on the categories page.";
    exit;
}

$api_key = $_GET['api'];

// Check if API key is valid
$stmt = $conn->prepare("SELECT * FROM users WHERE api_key = ?");
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    // Retrieve font, color, list, shadow, and font_size data for the user from the showobs table
    $stmt = $conn->prepare("SELECT * FROM showobs WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();
    $font = isset($settings['font']) ? $settings['font'] : null;
    $color = isset($settings['color']) ? $settings['color'] : null;
    $list = isset($settings['list']) ? $settings['list'] : null;
    $shadow = isset($settings['shadow']) ? $settings['shadow'] : null;
    $font_size = isset($settings['font_size']) ? $settings['font_size'] : null;
    $listType = ($list === 'Numbered') ? 'ol' : 'ul';
    $bold = isset($settings['bold']) ? $settings['bold'] : null;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>OBS TASK LIST</title>
    <link rel="icon" href="https://cdn.yourlist.online/img/logo.png" type="image/png" />
    <link rel="apple-touch-icon" href="https://cdn.yourlist.online/img/logo.png">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.yourlist.online/js/about.js"></script>
    <meta http-equiv="refresh" content="10">
    <style>
        body {
            <?php
            if ($font) {
                echo "font-family: $font; ";
            }
            if ($color) {
                echo "color: $color;";
                if ($shadow && $shadow == 1) {
                    if ($color === 'Black') {
                        echo "text-shadow: 0px 0px 6px White;";
                    } elseif ($color === 'White') {
                        echo "text-shadow: 0px 0px 6px Black;";
                    } else {
                        echo "text-shadow: 0px 0px 6px Black;";
                    }
                }
            }
            ?>
        }
    </style>
</head>
<body>
    <?php
    if (!isset($_GET['api']) || empty($_GET['api'])) {
        // Display missing API Key Error
        echo "Please provide your API key in the URL like this: obs.php?api=API_KEY";
        echo "<br>Get your API Key from your <a href='https://access.yourlist.online/profile.php'>profile</a>";
        echo "<br>If you wish to define a working category please add it like this:";
        echo "<br>https://yourlist.online/obs.php?api=API_KEY&category=1";
        echo "<br>(where ID 1 is called Default defined on the <a href='https://access.yourlist.online/categories.php'>categories</a> page.";
        exit;
    }

    $api_key = $_GET['api'];
    // Check if API key is valid
    $stmt = $conn->prepare("SELECT * FROM users WHERE api_key = ?");
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        // Get category id from url, default to "Default" category if not provided
        $category_id = isset($_GET['category']) && !empty($_GET['category']) ? $_GET['category'] : "1";
        // Get category name
        $stmt = $conn->prepare("SELECT category FROM categories WHERE id = ?");
        $stmt->bind_param("s", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $category = $result->fetch_assoc()['category'];
            // Get tasks for user in the specified category
            $stmt = $conn->prepare("SELECT * FROM todos WHERE user_id = ? AND category = ? ORDER BY id ASC");
            $stmt->bind_param("is", $user_id, $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $tasks = $result->fetch_all(MYSQLI_ASSOC);
            echo "<h1>$category List:</h1>";
            echo "<$listType>";
            foreach ($tasks as $task) {
                $task_id = $task['id'];
                $objective = $task['objective'];
                $completed = $task['completed'];
                if ($completed == 'Yes') {
                    if ($bold == 1) {
                        echo "<li style='font-size: {$font_size}px;'><s><strong>" . htmlspecialchars($objective) . "</strong></s></li>";
                    } else {
                        echo "<li style='font-size: {$font_size}px;'><s>" . htmlspecialchars($objective) . "</s></li>";
                    }
                } else {
                    if ($bold == 1) {
                        echo "<li style='font-size: {$font_size}px;'><strong>" . htmlspecialchars($objective) . "</strong></li>";
                    } else {
                        echo "<li style='font-size: {$font_size}px;'>" . htmlspecialchars($objective) . "</li>";
                    }
                }
            }
            echo "</$listType>";
        } else {
            // Invalid category id, show error message
            echo "Invalid category ID.";
        }
    } else {
        // Invalid API key, show error message
        echo "Invalid API key.";
    }
    ?>
</body>
</html>