<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Cart item count
$cart_count = 0;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    $cart_stmt = $conn->prepare($cart_query);
    if ($cart_stmt) {
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        if ($cart_result && $cart_row = $cart_result->fetch_assoc()) {
            $cart_count = $cart_row['total'] ?: 0;
        }
        $cart_stmt->close();
    } else {
        error_log("Error preparing cart count statement: " . $conn->error);
    }
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Order delicious food online from your favorite restaurants. Fast delivery, easy payment options, and a wide selection of meals.">
    <meta name="keywords" content="food delivery, online food, restaurant, meals, fast delivery">
    <title>CinCin | <?php echo ucfirst(str_replace('.php', '', $current_page)); ?></title>
    <link rel="icon" href="https://img.icons8.com/color/48/000000/hamburger.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body, html {
            font-family: 'Lato', 'Poppins', Arial, sans-serif;
            background: #eaf6f2;
        }
        header {
            background: #fff;
            box-shadow: none;
            border-bottom: 1px solid #e3e3e3;
            padding: 0;
        }
        .navbar {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 80px;
            padding: 0 36px;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo a {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .logo svg {
            width: 44px;
            height: 44px;
        }
        .logo-text {
            font-size: 1.7rem;
            font-weight: 900;
            color: #174832;
            margin-left: 10px;
            letter-spacing: 1px;
        }
        .main-nav {
            display: flex;
            align-items: center;
            gap: 36px;
            margin-left: 40px;
        }
        .main-nav a {
            color: #222;
            text-decoration: none;
            font-size: 1.08rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 4px 0;
            border-bottom: 3px solid transparent;
            transition: border 0.2s;
        }
        .main-nav a.active, .main-nav a:hover {
            border-bottom: 3px solid #174832;
        }
        .utility-nav {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .location-group {
            display: flex;
            align-items: center;
            background: #f4f8f6;
            border-radius: 20px;
            padding: 4px 14px 4px 10px;
            margin-right: 10px;
        }
        .location-group i {
            color: #174832;
            margin-right: 6px;
            font-size: 1.1rem;
        }
        #nav-location {
            border: none;
            background: transparent;
            font-size: 1rem;
            outline: none;
            width: 120px;
            color: #174832;
        }
        .btn-signin, .btn-join {
            border-radius: 999px;
            font-size: 1rem;
            font-weight: 700;
            padding: 7px 22px;
            border: 2px solid #222;
            background: #fff;
            color: #222;
            margin-left: 0;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
        }
        .btn-signin:hover {
            background: #f4f8f6;
        }
        .btn-join {
            background: #222;
            color: #fff;
            border: 2px solid #222;
            margin-left: 4px;
        }
        .btn-join:hover {
            background: #174832;
            border-color: #174832;
        }
        .cart-link {
            color: #174832;
            font-size: 1.3rem;
            margin-right: 8px;
            position: relative;
            text-decoration: none;
        }
        .cart-count {
            position: absolute;
            top: -7px;
            right: -10px;
            background: #174832;
            color: #fff;
            border-radius: 50%;
            font-size: 0.8rem;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .divider-bar {
            width: 100%;
            height: 36px;
            background: #174832;
            display: flex;
            align-items: center;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding-left: 44px;
        }
        @media (max-width: 900px) {
            .navbar { padding: 0 10px; }
            .main-nav { gap: 18px; margin-left: 10px; }
            .divider-bar { padding-left: 10px; }
        }
        @media (max-width: 600px) {
            .navbar { flex-direction: column; height: auto; padding: 10px 0; }
            .main-nav { margin: 10px 0 0 0; }
            .utility-nav { margin-top: 10px; }
            .divider-bar { height: 28px; font-size: 1rem; }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">
                <a href="index.php">
                    <svg viewBox="0 0 64 64" width="44" height="44" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="32" cy="22" rx="24" ry="14" fill="#fff" stroke="#174832" stroke-width="3"/>
                        <ellipse cx="32" cy="22" rx="18" ry="10" fill="#174832" fill-opacity="0.10"/>
                        <path d="M16 36c0 4 7.16 8 16 8s16-4 16-8" stroke="#174832" stroke-width="3" fill="none"/>
                        <rect x="20" y="36" width="24" height="10" rx="4" fill="#fff" stroke="#174832" stroke-width="3"/>
                        <rect x="26" y="44" width="12" height="6" rx="2" fill="#174832" fill-opacity="0.18"/>
                    </svg>
                    <span class="logo-text">CinCin</span>
                </a>
            </div>
            <nav class="main-nav">
                <a href="menu.php" class="<?php echo $current_page == 'menu.php' ? 'active' : ''; ?>">Menu</a>
                <a href="#" class="giftcard-link <?php echo $current_page == 'giftcards.php' ? 'active' : ''; ?>">Gift Cards</a>
            </nav>
            <div class="utility-nav">
                <div class="location-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="nav-location" placeholder="Location" />
                </div>
                <?php if (!isLoggedIn()): ?>
                    <a href="login.php" class="btn-signin">Sign in</a>
                    <a href="register.php" class="btn-join">Join now</a>
                <?php else: ?>
                    <a href="cart.php" class="cart-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    </a>
                    <a href="profile.php" class="btn-signin">Profile</a>
                    <a href="logout.php" class="btn-join" style="background:#fff;color:#174832;border:2px solid #174832;">Logout</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="divider-bar">
            SIMPLE. BUONO. PERFETTO
        </div>
    </header>
    <div class="container main-content"> 
    <script>
    // Save location to localStorage when confirmed
    document.addEventListener('DOMContentLoaded', function() {
        var navLoc = document.getElementById('nav-location');
        var confirmBtn = document.getElementById('confirm-location-btn');
        var confirmedMsg = document.getElementById('location-confirmed');
        if (navLoc) {
            // Load saved location if exists
            var saved = localStorage.getItem('user_location');
            if (saved) navLoc.value = saved;
            function confirmLocation() {
                localStorage.setItem('user_location', navLoc.value);
                if (confirmedMsg) {
                    confirmedMsg.style.display = 'inline';
                    setTimeout(function() { confirmedMsg.style.display = 'none'; }, 1500);
                }
            }
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    confirmLocation();
                });
            }
            navLoc.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    confirmLocation();
                }
            });
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        var giftCardLink = document.querySelector('.giftcard-link');
        if (giftCardLink) {
            giftCardLink.addEventListener('click', function(e) {
                e.preventDefault();
                alert('Your code: WOWHIEUVUONG25');
            });
        }
    });
    </script> 