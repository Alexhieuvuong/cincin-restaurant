<?php
// Include database connection
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if admin user already exists
$query = "SELECT * FROM users WHERE email = 'admin'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "Admin user already exists!";
} else {
    // Create admin user with email 'admin' and password 'admin'
    $name = "Admin User";
    $email = "admin";
    $password = hashPassword("admin"); // Hash the password
    $is_admin = 1;
    
    $query = "INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("sssi", $name, $email, $password, $is_admin);
        
        if ($stmt->execute()) {
            echo "Admin user created successfully!<br>";
            echo "Email: admin<br>";
            echo "Password: admin<br>";
            echo "You can now <a href='login.php'>login</a>.";
        } else {
            echo "Error creating admin user: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

$conn->close();
?> 