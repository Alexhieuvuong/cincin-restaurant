<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the permissions checker
require_once __DIR__ . '/permissions.php';

// Set error log file - use an absolute path to ensure it's writable
$error_log_file = __DIR__ . '/../debug.log';
ini_set('error_log', $error_log_file);
ini_set('log_errors', 1);

// Add a simple logging function
function debug_log($message) {
    $log_file = __DIR__ . '/../debug.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $log_message = $timestamp . ' ' . $message . PHP_EOL;
    
    // Try to write to the log file
    if (is_writable($log_file) || is_writable(dirname($log_file))) {
        // Try to append to the log file
        if (@file_put_contents($log_file, $log_message, FILE_APPEND) === false) {
            // If failed, use PHP's error_log function as a fallback
            error_log($message);
        }
    } else {
        // If the file isn't writable, use PHP's built-in error_log function
        error_log($message);
        
        // Only show this message once per session to avoid flooding
        if (!isset($_SESSION['log_warning_shown'])) {
            // Output a warning message to admins only
            if (function_exists('isAdmin') && isAdmin()) {
                echo "<div class='alert alert-warning'>Warning: Cannot write to log file. Check permissions for {$log_file}</div>";
            }
            $_SESSION['log_warning_shown'] = true;
        }
    }
}

// Log database connection
debug_log("Database connection script started");

// Database Configuration
define('DB_HOST', 'localhost:3308');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'food_delivery');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    debug_log("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) !== TRUE) {
    debug_log("Error creating database: " . $conn->error);
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);
debug_log("Database connection established successfully");
?> 