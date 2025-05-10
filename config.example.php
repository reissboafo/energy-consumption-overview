<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'energy_dashboard';
$db_user = 'your_username';
$db_pass = 'your_password';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Error reporting
if ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], 'dev.') === 0) {
    // Development environment
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production environment
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/error.log');
}

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_MIME_TYPES', [
    'text/csv',
    'text/plain',
    'application/csv',
    'application/vnd.ms-excel'
]);
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('CACHE_DIR', __DIR__ . '/cache/');

// Ensure required directories exist
foreach ([UPLOAD_DIR, CACHE_DIR] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}
