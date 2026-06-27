<?php
// Automatic environment detection for database connection
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

if (strpos($http_host, 'localhost') !== false || strpos($http_host, '127.0.0.1') !== false) {
    // Local XAMPP Configuration
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'lostandfound'); // Local database name
} else {
    // Production InfinityFree Configuration
    define('DB_SERVER', 'sql305.infinityfree.com');
    define('DB_USERNAME', 'if0_42243807');
    define('DB_PASSWORD', 'lostandfound888');
    define('DB_NAME', 'if0_42243807_lostandfound');
}

// Google Cloud Vision API Key (Set a valid key to enable real image analysis, otherwise it falls back to keyword tagging)
define('GOOGLE_VISION_API_KEY', ''); 

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>