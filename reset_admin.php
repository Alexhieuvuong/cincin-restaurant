<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// New admin credentials
$admin_email = 'admin@cincin.com';
$admin_password = 'admin123';

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Update the admin user
$query = "UPDATE users SET password = ? WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $hashed_password, $admin_email);

if ($stmt->execute()) {
    echo "Admin password has been reset successfully!<br>";
    echo "Email: " . $admin_email . "<br>";
    echo "Password: " . $admin_password . "<br>";
    echo "<a href='login.php'>Go to Login</a>";
} else {
    echo "Error resetting password: " . $conn->error;
}

$stmt->close();
?> 