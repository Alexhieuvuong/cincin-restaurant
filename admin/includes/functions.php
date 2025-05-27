<?php
// Admin specific functions

/**
 * Display alert message
 */
function displayAlert($message, $type = 'success') {
    echo '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

/**
 * Get total count of products
 */
function getProductCount($conn) {
    $query = "SELECT COUNT(*) as count FROM products";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'];
    }
    
    return 0;
}

/**
 * Get total count of categories
 */
function getCategoryCount($conn) {
    $query = "SELECT COUNT(*) as count FROM categories";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'];
    }
    
    return 0;
}

/**
 * Get total count of users
 */
function getUserCount($conn) {
    $query = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'];
    }
    
    return 0;
}

/**
 * Get total count of orders
 */
function getOrderCount($conn) {
    $query = "SELECT COUNT(*) as count FROM orders";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'];
    }
    
    return 0;
}

/**
 * Get total revenue
 */
function getTotalRevenue($conn) {
    $query = "SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total'] ? $row['total'] : 0;
    }
    
    return 0;
}

/**
 * Get pending orders count
 */
function getPendingOrderCount($conn) {
    $query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'];
    }
    
    return 0;
}

/**
 * Get completed orders count
 */
function getCompletedOrderCount($conn) {
    $query = "SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'];
    }
    
    return 0;
}

/**
 * Get recent orders (limit by count)
 */
function getRecentOrders($conn, $limit = 5) {
    $query = "SELECT o.*, u.name as customer_name 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              ORDER BY o.created_at DESC 
              LIMIT $limit";
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
 * Get top selling products (limit by count)
 */
function getTopSellingProducts($conn, $limit = 5) {
    $query = "SELECT p.id, p.name, p.price, p.image, SUM(oi.quantity) as total_sold 
              FROM products p 
              JOIN order_items oi ON p.id = oi.product_id 
              JOIN orders o ON oi.order_id = o.id 
              WHERE o.status != 'cancelled' 
              GROUP BY p.id 
              ORDER BY total_sold DESC 
              LIMIT $limit";
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
 * Format date to a readable format
 */
function formatDate($date, $format = 'M j, Y, g:i a') {
    return date($format, strtotime($date));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Sanitize input for SQL
 */
function sanitize($conn, $input) {
    return $conn->real_escape_string(trim($input));
}

/**
 * Get sales data for chart
 */
function getSalesData($conn, $days = 7) {
    $data = [];
    $labels = [];
    $values = [];
    
    // Get date for the past N days
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime("-$days days"));
    
    $query = "SELECT DATE(created_at) as order_date, SUM(total_amount) as total 
              FROM orders 
              WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59' 
              AND status != 'cancelled' 
              GROUP BY DATE(created_at) 
              ORDER BY order_date";
    $result = $conn->query($query);
    
    // Initialize all days with 0 sales
    for ($i = 0; $i < $days; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M j', strtotime($date));
        $values[$date] = 0;
    }
    
    // Fill in actual sales data
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $values[$row['order_date']] = (float)$row['total'];
        }
    }
    
    // Reverse arrays to show in chronological order
    $labels = array_reverse($labels);
    $values = array_reverse($values);
    
    $data['labels'] = $labels;
    $data['values'] = array_values($values);
    
    return $data;
}
?> 