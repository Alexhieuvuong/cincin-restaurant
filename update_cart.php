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

// Get item ID and quantity from POST data
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

// Debug - log to error log
error_log("Update cart request: item_id=$item_id, quantity=$quantity");

// Validate inputs
if ($item_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item ID'
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

// Verify this cart item belongs to the user
$check_query = "SELECT c.*, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $item_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if (!$check_result || $check_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found in your cart'
    ]);
    $check_stmt->close();
    exit;
}

$cart_item = $check_result->fetch_assoc();
$check_stmt->close();

// Update cart item
$update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("iii", $quantity, $item_id, $user_id);
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

// Calculate subtotal
$opt_key = $user_id . '_' . $cart_item['product_id'];
$options = isset($_SESSION['cart_options'][$opt_key]) ? $_SESSION['cart_options'][$opt_key] : [];
$option_cost = count($options) * 1.0; // Each option adds $1
$subtotal = ($cart_item['price'] + $option_cost) * $quantity;

// Get updated cart count
$count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$cart_count = ($count_result && $count_row = $count_result->fetch_assoc()) ? $count_row['total'] : 0;
$count_stmt->close();

// Get updated cart total with options
$total = 0;
$items_query = "SELECT c.product_id, c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
$items_stmt = $conn->prepare($items_query);
if ($items_stmt) {
    $items_stmt->bind_param("i", $user_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    if ($items_result && $items_result->num_rows > 0) {
        while ($row = $items_result->fetch_assoc()) {
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
    $items_stmt->close();
}

// Return success response
$response = [
    'success' => true,
    'message' => 'Cart updated',
    'item_id' => $item_id,
    'quantity' => $quantity,
    'subtotal' => formatPrice($subtotal),
    'cart_count' => $cart_count,
    'cart_total' => formatPrice($total)
];

// Debug - log response
error_log("Update cart response: " . json_encode($response));

echo json_encode($response);
?> 