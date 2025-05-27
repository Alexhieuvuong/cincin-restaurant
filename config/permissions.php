<?php
/**
 * File Permission Manager
 * 
 * This script checks and fixes permissions for critical files
 * that need to be writable by the web server.
 */

// Only run this if we're in a web environment
if (PHP_SAPI !== 'cli') {
    // Check if session is already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Function to check and fix file permissions
    function check_file_permissions($file_path, $create_if_not_exists = true) {
        // Check if the file exists
        if (!file_exists($file_path)) {
            if ($create_if_not_exists) {
                // Try to create the file
                $dir = dirname($file_path);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                @touch($file_path);
            } else {
                return false; // File doesn't exist and shouldn't be created
            }
        }
        
        // Check if the file is writable
        if (!is_writable($file_path)) {
            // Try to fix permissions
            @chmod($file_path, 0666);
            
            // Check again after attempting to fix
            if (!is_writable($file_path)) {
                // If still not writable, check directory permissions
                $dir = dirname($file_path);
                if (!is_writable($dir)) {
                    @chmod($dir, 0755);
                }
                
                // Try one more time to make the file writable
                @chmod($file_path, 0666);
                
                // Final check
                if (!is_writable($file_path)) {
                    // Log permission issue
                    error_log("Permission issue: Cannot write to {$file_path}");
                    return false;
                }
            }
        }
        
        return true; // File is writable
    }
    
    // Files that need to be writable
    $critical_files = [
        __DIR__ . '/../debug.log',
        // Add other critical files here
    ];
    
    // Check and fix permissions for all critical files
    $permission_issues = false;
    foreach ($critical_files as $file) {
        if (!check_file_permissions($file)) {
            $permission_issues = true;
        }
    }
    
    // Only show warning once per session
    if ($permission_issues && !isset($_SESSION['permission_warning_shown'])) {
        // Only show to admins
        if (function_exists('isAdmin') && isAdmin()) {
            echo "<div class='alert alert-warning'>Warning: Some files have permission issues. Check server logs for details.</div>";
        }
        $_SESSION['permission_warning_shown'] = true;
    }
}
?> 