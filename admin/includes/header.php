<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    // Redirect to login page
    header('Location: ../login.php');
    exit;
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CinCin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>CinCin</h2>
                <h3>Admin Panel</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" <?php echo $current_page == 'index.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li><a href="categories.php" <?php echo $current_page == 'categories.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tags"></i> Categories</a>
                </li>
                <li><a href="products.php" <?php echo $current_page == 'products.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-utensils"></i> Products</a>
                </li>
                <li><a href="orders.php" <?php echo $current_page == 'orders.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-shopping-cart"></i> Orders</a>
                </li>
                <li><a href="users.php" <?php echo $current_page == 'users.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-users"></i> Users</a>
                </li>
                <li class="divider"></li>
                <li><a href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Website</a>
                </li>
                <li><a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <p>Logged in as: <?php echo $_SESSION['name']; ?></p>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="user-dropdown">
                    <span><?php echo $_SESSION['name']; ?> <i class="fas fa-chevron-down"></i></span>
                    <div class="dropdown-content">
                        <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </header>
            
            <div class="content-wrapper"> 