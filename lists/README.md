# YourListOnline
YourListOnline is a website that allows you to keep track of all the tasks you need to complete for your streaming or normal day-to-day activities.
<br>As this project is very much a "in progress" site, some or all of the information on how to use this site will be inaccurate, thanks for your understanding.
<br><br>
[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/T6T8D1Y2O)

## Getting Started
To get started, you will need to create a SQL database and use the following code to build the tables:

```sql
CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) COLLATE latin1_swedish_ci,
    password VARCHAR(255) COLLATE latin1_swedish_ci,
    api_key VARCHAR(255) COLLATE latin1_swedish_ci,
    is_admin TINYINT(1) DEFAULT 0,
    signup_date DATETIME,
    last_login DATETIME
);

CREATE TABLE todos (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11),
    objective TEXT COLLATE latin1_swedish_ci,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed TINYTEXT COLLATE latin1_swedish_ci
);

CREATE TABLE categories (
    id INT(255) AUTO_INCREMENT,
    category VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (id)
);

CREATE TABLE showobs (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11),
    font VARCHAR(255) COLLATE latin1_swedish_ci,
    color VARCHAR(255) COLLATE latin1_swedish_ci,
    list VARCHAR(255) COLLATE latin1_swedish_ci DEFAULT 'bullet',
    shadow TINYINT(1) DEFAULT 0,
    bold TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

```
### Users Table
The users table has the following columns:
* id: a unique identifier for the user
* username: the username of the user
* password: the password of the user
* api_key: the API key of the user
* is_admin: a boolean flag indicating whether the user is an admin or not
* signup_date: the timestamp when the user signed up
* last_login: the timestamp when the user last logged in
#### Notes:
id is set to **INT(11)** with the **AUTO_INCREMENT** option, which will automatically generate a unique identifier for each new user added to the table, and is used as the primary key for the table. 

username, password, and api_key are set to **VARCHAR(50)** with the collation **latin1_swedish_ci** and are used to store the user's login credentials and API key.

is_admin is set to **TINYINT(1)** with a default value of **0** and indicates whether the user is an administrator or not.

signup_date and last_login are both set to **DATETIME** and are used to store the date and time when the user signed up and last logged in, respectively.
### Todos Table
The todos table has the following columns:
* id: a unique identifier for the todo item
* user_id: the id of the user who owns the todo item
* objective: the text of the todo item
* created_at: the timestamp when the todo item was created
* updated_at: the timestamp when the todo item was last updated
* completed: a flag indicating whether the todo item has been completed or not definded by **"Yes"** or **"No"**.

#### Notes:
id is set to **INT** with the **AUTO_INCREMENT** option, which will automatically generate a unique identifier for each new todo item added to the table.

user_id and objective are set to **INT** and **TEXT** data types, respectively.

created_at and updated_at are set to **TIMESTAMP** data type to store the date and time values.

completed is set to **TINYTEXT** data type to store a flag that indicates whether the todo item has been completed or not definded by **"Yes"** or **"No"**.
### Categories
The categories table has the following columns:
* id: a unique identifier for the category
* category: the name of the category
### Showobs Table
The showobs table has the following columns:
* id: a unique identifier for the showobs entry
* user_id: the id of the user associated with the showobs entry
* font: the font setting value
* color: the color setting value
* list: the list type setting value (e.g., bullet, numbered)
* shadow: a flag indicating whether the text shadow is enabled or not (0 or 1)
* bold: a flag indicating whether the text bold is enabled or not (0 or 1)
## Database Connection Settings
After you've created the database and tables, you'll have to add those deatils in the *[db_connect.php](../lists/db_connect.php)* file.
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

For example, this code is written in PHP and uses Apache as the web server, you would need to ensure that the VPS has PHP and Apache installed and configured correctly. You would also need to ensure that any required PHP extensions or libraries are installed.

Additionally, you would need to ensure that the VPS has enough resources (such as CPU, RAM, and storage) to handle the expected traffic and workload of this website.

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
