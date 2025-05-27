<?php
// Simple test script to verify logging is working

// Include the database configuration which has the debug_log function
require_once 'config/database.php';

// Try to write to the log
debug_log("Test logging message at " . date('Y-m-d H:i:s'));

// Output success message
echo "Logging test completed. Check debug.log file or server error log for the test message.";

// Display the log file contents if it exists and is readable
if (file_exists('debug.log') && is_readable('debug.log')) {
    echo "<hr>";
    echo "<h3>Contents of debug.log:</h3>";
    echo "<pre>";
    echo htmlspecialchars(file_get_contents('debug.log'));
    echo "</pre>";
} else {
    echo "<hr>";
    echo "<p>Cannot read debug.log file. Check server error log.</p>";
}
?> 