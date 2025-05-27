<?php
// Simple test script to check redirection

// Import the logging function
require_once 'config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Import functions
require_once 'includes/functions.php';

// Log the test
debug_log("Redirect test script started");

// Set a session variable for testing
$_SESSION['test_var'] = 'Testing redirection at ' . date('Y-m-d H:i:s');
debug_log("Session variable set: " . $_SESSION['test_var']);

// Display message
echo "Testing redirection...";

// Flush output buffer
if (ob_get_level()) {
    ob_end_flush();
}
flush();

// Wait a second
sleep(1);

// Log before redirect
debug_log("About to redirect to index.php");

// Try to redirect
redirect('index.php');

// This should not execute if redirect works
debug_log("ERROR: Code executed after redirect!");
echo "ERROR: Redirect failed!";
?> 