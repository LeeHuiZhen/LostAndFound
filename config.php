<?php
// ===================================================
// Database Configuration - INFINITYFREE
// ===================================================

// UPDATE THESE WITH YOUR INFINITYFREE CREDENTIALS
// Go to InfinityFree Control Panel → MySQL Databases to find these
$servername = "sql196.infinityfree.com";     // Your actual hostname
$dbname = "if0_XXXXXX_lost_and_found";       // Your actual database name
$username = "if0_XXXXXX";                    // Your actual username
$password = "YourActualPassword";            // Your actual password

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL
define('BASE_URL', 'https://lost-and-found.infinityfree.me/');
?>