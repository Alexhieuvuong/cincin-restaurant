<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get product ID and quantity from POST data
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$options = isset($_POST['options']) ? json_decode($_POST['options'], true) : [];

// Debug - log request details
error_log("Add to cart request: product_id=$product_id, quantity=$quantity, user_id={$_SESSION['user_id']}");

// Validate inputs
if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]);
    exit;
}

if ($quantity <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid quantity'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if product exists using prepared statement
$product_query = "SELECT * FROM products WHERE id = ? AND is_available = 1";
$product_stmt = $conn->prepare($product_query);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if (!$product_result || $product_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found or not available'
    ]);
    $product_stmt->close();
    exit;
}
$product_stmt->close();

// Check if product already in cart using prepared statement
$check_query = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $user_id, $product_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result && $check_result->num_rows > 0) {
    $cart_item = $check_result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + $quantity;
    $check_stmt->close();
    
    // Update existing cart item using prepared statement
    $update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
    $update_result = $update_stmt->execute();
    
    if (!$update_result) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update cart: ' . $conn->error
        ]);
        $update_stmt->close();
        exit;
    }
    $update_stmt->close();
} else {
    $check_stmt->close();
    
    // Add new cart item using prepared statement
    $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
    $insert_result = $insert_stmt->execute();
    
    if (!$insert_result) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add to cart: ' . $conn->error
        ]);
        $insert_stmt->close();
        exit;
    }
    $insert_stmt->close();
}

// Get updated cart count using prepared statement
$count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$cart_count = ($count_result && $count_row = $count_result->fetch_assoc()) ? $count_row['total'] : 0;
$count_stmt->close();

// Get updated cart total using prepared statement
$total_query = "SELECT SUM(c.quantity * p.price) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
$total_stmt = $conn->prepare($total_query);
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$cart_total = ($total_result && $total_row = $total_result->fetch_assoc()) ? $total_row['total'] : 0;
$total_stmt->close();

// After adding/updating cart item, store options in session
if (!isset($_SESSION['cart_options'])) {
    $_SESSION['cart_options'] = [];
}
$key = $user_id . '_' . $product_id;
if (is_array($options) && count($options) > 0) {
    $_SESSION['cart_options'][$key] = $options;
} else {
    unset($_SESSION['cart_options'][$key]);
}

// Return success response
$response = [
    'success' => true,
    'message' => 'Item added to cart',
    'cart_count' => $cart_count,
    'cart_total' => (float)$cart_total,
    'product_id' => $product_id
];

// Debug - log response
error_log("Add to cart response: " . json_encode($response));

echo json_encode($response);
?> 