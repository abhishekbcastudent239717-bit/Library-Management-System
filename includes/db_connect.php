<?php

define('DB_HOST', 'localhost');           // Database server host (localhost)
define('DB_USER', 'root');                // Database username
define('DB_PASS', '');                    // Database password 
define('DB_NAME', 'library_management');  // Database name

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

