<?php
// Security-related functions

/**
 * Clean user input to prevent XSS attacks
 */
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if email is valid
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Redirect to another page
 */
function redirect($url) {
    // Log the redirect
    if (function_exists('debug_log')) {
        debug_log("Redirecting to: $url");
    } else {
        error_log("Redirecting to: $url");
    }
    
    // Make sure we have a valid URL
    if (empty($url)) {
        if (function_exists('debug_log')) {
            debug_log("ERROR: Empty redirect URL");
        } else {
            error_log("ERROR: Empty redirect URL");
        }
        $url = 'index.php';
    }
    
    // Try to flush any existing output buffers
    try {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
    } catch (Exception $e) {
        if (function_exists('debug_log')) {
            debug_log("Warning: Error flushing output buffer: " . $e->getMessage());
        } else {
            error_log("Warning: Error flushing output buffer: " . $e->getMessage());
        }
    }
    
    // Check if headers were already sent
    if (headers_sent($file, $line)) {
        if (function_exists('debug_log')) {
            debug_log("Headers already sent in $file on line $line - using JavaScript redirect");
        } else {
            error_log("Headers already sent in $file on line $line - using JavaScript redirect");
        }
        
        echo "<script>window.location.href = '$url';</script>";
        echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url=$url\"></noscript>";
        echo "If you are not redirected automatically, please click <a href=\"$url\">here</a>.";
        exit;
    }
    
    // Perform the redirect
    header("Location: $url");
    exit;
}

/**
 * Set flash message in session
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Display flash message and remove it from session
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        echo '<div class="alert alert-' . $flash['type'] . '">' . $flash['message'] . '</div>';
        unset($_SESSION['flash']);
    }
}

/**
 * Get current user information
 */
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Format price with currency symbol
 */
function formatPrice($price) {
    return '€' . number_format($price, 2);
}

/**
 * Get cart items for current user
 */
function getCartItems($conn, $user_id) {
    $items = [];
    
    $query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image
              FROM cart c
              JOIN products p ON c.product_id = p.id
              WHERE c.user_id = ?";
              
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement: " . $conn->error);
    }
    
    return $items;
}

/**
 * Calculate cart total
 */
function getCartTotal($conn, $user_id) {
    $total = 0;
    
    $query = "SELECT c.product_id, c.quantity, p.price
              FROM cart c
              JOIN products p ON c.product_id = p.id
              WHERE c.user_id = ?";
              
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $price = $row['price'];
                $quantity = $row['quantity'];
                
                // Get options for this item
                $opt_key = $user_id . '_' . $row['product_id'];
                $options = isset($_SESSION['cart_options'][$opt_key]) ? $_SESSION['cart_options'][$opt_key] : [];
                $option_cost = count($options) * 1.0; // Each option adds $1
                
                // Add to total with options
                $total += ($price + $option_cost) * $quantity;
            }
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement: " . $conn->error);
    }
    
    return $total;
}

/**
 * Get products by category
 */
function getProductsByCategory($conn, $category_id) {
    $query = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = $category_id AND p.is_available = 1";
    $result = $conn->query($query);
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

/**
 * Get all categories
 */
function getAllCategories($conn) {
    $query = "SELECT * FROM categories";
    $result = $conn->query($query);
    
    $categories = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

/**
 * Get all products
 */
function getAllProducts($conn) {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p
              JOIN categories c ON p.category_id = c.id
              WHERE p.is_available = 1";
    $result = $conn->query($query);
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

/**
 * Get product by ID
 */
function getProductById($conn, $product_id) {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p
              JOIN categories c ON p.category_id = c.id
              WHERE p.id = $product_id";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get user orders
 */
function getUserOrders($conn, $user_id) {
    $query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    $orders = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    
    return $orders;
}

/**
 * Get order details
 */
function getOrderDetails($conn, $order_id) {
    $query = "SELECT oi.*, p.name, p.image, oi.options
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = $order_id";
    $result = $conn->query($query);
    
    $items = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    return $items;
}

/**
 * Get order by ID
 */
function getOrderById($conn, $order_id) {
    $query = "SELECT o.*, p.payment_method, p.status as payment_status
              FROM orders o
              LEFT JOIN payments p ON o.id = p.order_id
              WHERE o.id = $order_id";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Search products
 */
function searchProducts($conn, $search_term) {
    $search_term = $conn->real_escape_string($search_term);
    $query = "SELECT p.*, c.name as category_name 
              FROM products p
              JOIN categories c ON p.category_id = c.id
              WHERE p.is_available = 1 AND (p.name LIKE '%$search_term%' OR p.description LIKE '%$search_term%' OR c.name LIKE '%$search_term%')";
    $result = $conn->query($query);
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Clear user's cart
 */
function clearCart($conn, $user_id) {
    $success = false;
    
    // Prepare the statement
    $query = "DELETE FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $success = true;
            // Log the action
            if (function_exists('debug_log')) {
                debug_log("Cart cleared for user_id=$user_id");
            }
        } else {
            // Log the error
            if (function_exists('debug_log')) {
                debug_log("Error clearing cart for user_id=$user_id: " . $stmt->error);
            } else {
                error_log("Error clearing cart for user_id=$user_id: " . $stmt->error);
            }
        }
        $stmt->close();
    } else {
        // Log the error
        if (function_exists('debug_log')) {
            debug_log("Error preparing cart clear statement: " . $conn->error);
        } else {
            error_log("Error preparing cart clear statement: " . $conn->error);
        }
    }
    
    return $success;
}
?> 