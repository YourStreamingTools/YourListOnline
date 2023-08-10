# YourListOnline
YourListOnline is a website that allows you to keep track of all the tasks you need to complete for your streaming or normal day-to-day activities.
<br>As this project is very much a "in progress" site, some or all of the information on how to use this site will be inaccurate, thanks for your understanding.
<br><br>
[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/T6T8D1Y2O)

## Getting Started
To get started, you will need to create a SQL database and use the following code to build the tables:

```sql
CREATE TABLE users (
  id INT(11) AUTO_INCREMENT,
  username VARCHAR(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  is_admin TINYINT(1) DEFAULT 0,
  api_key VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  access_token VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  refresh_token VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  expires_at DATETIME,
  signup_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);

CREATE TABLE categories (
  id INT(11) AUTO_INCREMENT,
  category VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (id)
);

CREATE TABLE todos (
  id INT(11) AUTO_INCREMENT,
  user_id INT(11),
  category_id INT(11),
  title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  completed ENUM('YES','NO') DEFAULT 'NO',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE tokens (
  id INT(11) AUTO_INCREMENT,
  user_id INT(11),
  token VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  expires_at DATETIME,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```
### twitch_users
The users table has the following columns:
* id: A unique identifier for each user in the table.
* username: The Twitch username of the user.
* is_admin: A flag indicating whether the user is an admin or not. The default value is 0.
* api_key: A unique API key generated for each user.
* access_token: The OAuth access token obtained from Twitch API for each user.
* refresh_token: The OAuth refresh token obtained from Twitch API for each user.
* expires_at: The expiration date and time of the access token.
* signup_date: The date and time when the user signed up for the service. The default value is the current timestamp.
* last_login: The date and time of the user's last login. The default value is the current timestamp.
### twitch_todos
The todos table has the following columns:
* id: A unique identifier for each to-do item in the table.
* user_id: The ID of the user who created the to-do item.
* category_id: The ID of the category to which the to-do item belongs.
* description: A brief description of the to-do item.
* completed: A flag indicating whether the to-do item has been completed or not. The default value is 'NO'.
### twitch_categories
* id: A unique identifier for each category in the table.
* category: The name of the category.
## Database Connection Settings
After you've created the database and tables, you'll have to add those deatils in the *[db_connect.php](../discord/db_connect.php)* file.
```php
<?php
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "todolistdb";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

## Running the code
To run this code on a VPS, you would need to ensure that the VPS meets the minimum system requirements for the programming language and web server being used.

For example, if this code is written in PHP and uses Apache as the web server, you would need to ensure that the VPS has PHP and Apache installed and configured correctly. You would also need to ensure that any required PHP extensions or libraries are installed.

Additionally, you would need to ensure that the VPS has enough resources (such as CPU, RAM, and storage) to handle the expected traffic and workload of the website.

Finally, you would need to ensure that the VPS is secure, by configuring firewalls, installing security updates, and following best practices for server security.

Here's an example script for installing Apache and PHP on a Ubuntu VPS:
```bash
#!/bin/bash

# Update package lists and install required packages
sudo apt-get update
sudo apt-get install -y apache2 php libapache2-mod-php php-mysql

# Enable Apache modules
sudo a2enmod rewrite

# Restart Apache
sudo service apache2 restart

# Install MySQL server
sudo apt install -y mysql-server

# Secure the installation
sudo mysql_secure_installation
```
### Notes:
The above script is a basic setup script for setting up a web server with PHP and a MySQL Database on an Ubuntu-based system.

Specifically, it does the following:
1. Updates the package lists and installs Apache2, PHP, and the PHP MySQL extension.
2. Enables the Apache2 rewrite module.
3. Restarts the Apache2 web server.
4. Installs the MySQL server.
5. Runs the MySQL secure installation script to secure the installation by setting a root password, removing anonymous users, and disabling remote root login.
