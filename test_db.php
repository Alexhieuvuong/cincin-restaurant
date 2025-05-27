<?php
require_once 'config/database.php';

try {
    // Test the connection
    if ($conn) {
        echo "Database connection successful!";
        
        // Test a simple query
        $test_query = "SELECT 1";
        $result = $conn->query($test_query);
        
        if ($result) {
            echo "<br>Query test successful!";
            
            // Test database selection
            if ($conn->select_db(DB_NAME)) {
                echo "<br>Database '" . DB_NAME . "' selected successfully!";
            } else {
                echo "<br>Error selecting database: " . $conn->error;
            }
        } else {
            echo "<br>Query test failed: " . $conn->error;
        }
    } else {
        echo "Database connection failed!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 