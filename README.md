# YourListOnline
YourListOnline is a website that allows you to keep track of all the tasks you need to complete for your streaming or normal day-to-day activities.
<br>As this project is very much a "in progress" site, some or all of the information on how to use this site will be inaccurate, thanks for your understanding.
<br><br>
[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/T6T8D1Y2O)

## Getting Started
To get started, you will need to create a VPS or have a webhosting platform and use the following information to make this work:

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
