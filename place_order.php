<?php
// Dedicated script to handle order form submission
session_start();

// Import required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Function to send JSON response for AJAX requests
function sendJsonResponse($status, $message, $data = []) {
    global $is_ajax;
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

// Check for duplicate submissions using a token
if (isset($_SESSION['order_processed']) && $_SESSION['order_processed'] === true) {
    debug_log("Duplicate order submission detected");
    
    if ($is_ajax) {
        sendJsonResponse('info', 'Your order has already been processed.', [
            'showModal' => true,
            'modalTitle' => 'Order Already Processed',
            'modalMessage' => 'Your order has already been processed and is being prepared. You can check your order status in your profile.',
            'redirect' => 'profile.php?tab=orders',
            'redirectDelay' => 3000 // Redirect after 3 seconds
        ]);
    } else {
        // Store modal info in session and redirect to order.php to show modal
        $_SESSION['show_modal'] = [
            'type' => 'success',
            'title' => 'Order Already Processed',
            'message' => 'Your order has already been processed and is being prepared. You can check your order status in your profile.',
            'redirect' => 'profile.php?tab=orders',
            'redirectDelay' => 3000 // Redirect after 3 seconds
        ];
        redirect('order.php');
    }
    exit;
}

// Check for CSRF token
if (!isset($_POST['order_token']) || !isset($_SESSION['order_token']) || $_POST['order_token'] !== $_SESSION['order_token']) {
    debug_log("CSRF token validation failed");
    
    if ($is_ajax) {
        sendJsonResponse('error', 'Invalid form submission. Please try again.');
    } else {
        setFlashMessage('danger', 'Invalid form submission. Please try again.');
        redirect('order.php');
    }
    exit;
}

// Clear the token to prevent reuse
unset($_SESSION['order_token']);

// Make sure this script is only accessed via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("place_order.php accessed with non-POST method");
    
    if ($is_ajax) {
        sendJsonResponse('error', 'Invalid request method.');
    } else {
        redirect('order.php');
    }
    exit;
}

// Redirect if not logged in
if (!isLoggedIn()) {
    debug_log("User not logged in, redirecting to login");
    
    if ($is_ajax) {
        sendJsonResponse('error', 'Please login to checkout.', ['redirect' => 'login.php']);
    } else {
        $_SESSION['redirect_after_login'] = 'order.php';
        setFlashMessage('danger', 'Please login to checkout.');
        redirect('login.php');
    }
    exit;
}

$user_id = $_SESSION['user_id'];
debug_log("Processing order for user_id=$user_id via dedicated script");

// Get cart items
$cart_items = getCartItems($conn, $user_id);

// Calculate total with options
$cart_total = 0;
foreach ($cart_items as $item) {
    $product_id = $item['product_id'];
    $quantity = $item['quantity'];
    $price = $item['price'];
    
    // Get options for this item
    $opt_key = $user_id . '_' . $product_id;
    $options = isset($_SESSION['cart_options'][$opt_key]) ? $_SESSION['cart_options'][$opt_key] : [];
    $option_cost = count($options) * 1.0; // Each option adds $1
    
    // Add to total with options
    $cart_total += ($price + $option_cost) * $quantity;
}

// Get form inputs
$name = clean($_POST['name'] ?? '');
$address = clean($_POST['address'] ?? '');
$phone = clean($_POST['phone'] ?? '');
$payment_method = clean($_POST['payment_method'] ?? '');

debug_log("Form data received: name=$name, payment_method=$payment_method");

// Validate inputs
$errors = [];

if (empty($name)) {
    $errors['name'] = 'Name is required';
}

if (empty($address)) {
    $errors['address'] = 'Address is required';
}

if (empty($phone)) {
    $errors['phone'] = 'Phone number is required';
}

if (empty($payment_method)) {
    $errors['payment_method'] = 'Payment method is required';
}

// Process order if no errors
if (!empty($errors)) {
    debug_log("Validation errors: " . json_encode($errors));
    
    if ($is_ajax) {
        sendJsonResponse('error', 'Please fix the errors in the form.', [
            'errors' => $errors,
            'form_data' => [
                'name' => $name,
                'address' => $address,
                'phone' => $phone,
                'payment_method' => $payment_method
            ]
        ]);
    } else {
        $_SESSION['order_errors'] = $errors;
        $_SESSION['order_form_data'] = [
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'payment_method' => $payment_method
        ];
        redirect('order.php');
    }
    exit;
}

debug_log("Form validation passed, proceeding with order processing");

// Set transaction wait timeout
try {
    $conn->query("SET SESSION innodb_lock_wait_timeout=50");
    $conn->query("SET SESSION wait_timeout=60");
    debug_log("Set database transaction timeouts");
} catch (Exception $e) {
    debug_log("Warning: Could not set transaction timeouts: " . $e->getMessage());
}

// Start transaction
try {
    // Start transaction
    $conn->begin_transaction();
    debug_log("Database transaction started");

    // Create order
    $order_query = "INSERT INTO orders (user_id, total_amount, status, payment_status, address, phone) 
                   VALUES (?, ?, 'pending', 'pending', ?, ?)";
    $order_stmt = $conn->prepare($order_query);
    if (!$order_stmt) {
        throw new Exception("Error preparing order statement: " . $conn->error);
    }

    $order_stmt->bind_param("idss", $user_id, $cart_total, $address, $phone);
    if (!$order_stmt->execute()) {
        throw new Exception("Error executing order statement: " . $order_stmt->error);
    }

    $order_id = $conn->insert_id;
    debug_log("Order created with ID: $order_id");
    $order_stmt->close();

    // Add order items
    $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price, options) VALUES (?, ?, ?, ?, ?)";
    $item_stmt = $conn->prepare($item_query);
    if (!$item_stmt) {
        throw new Exception("Error preparing order item statement: " . $conn->error);
    }

    foreach ($cart_items as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        
        // Get options for this item
        $opt_key = $user_id . '_' . $product_id;
        $options = isset($_SESSION['cart_options'][$opt_key]) ? json_encode($_SESSION['cart_options'][$opt_key]) : null;
        $option_cost = isset($_SESSION['cart_options'][$opt_key]) ? count($_SESSION['cart_options'][$opt_key]) * 1.0 : 0;
        
        // Add option cost to price
        $final_price = $price + $option_cost;

        debug_log("Adding order item: product_id=$product_id, quantity=$quantity, price=$final_price, options=" . ($options ?? 'none'));
        $item_stmt->bind_param("iiids", $order_id, $product_id, $quantity, $final_price, $options);
        if (!$item_stmt->execute()) {
            throw new Exception("Error executing order item statement: " . $item_stmt->error);
        }
    }
    $item_stmt->close();

    // Add payment record
    $transaction_id = 'TXN' . time() . rand(1000, 9999);
    $payment_status = ($payment_method == 'cash_on_delivery') ? 'pending' : 'completed';

    $payment_query = "INSERT INTO payments (order_id, amount, payment_method, transaction_id, status) 
                     VALUES (?, ?, ?, ?, ?)";
    $payment_stmt = $conn->prepare($payment_query);
    if (!$payment_stmt) {
        throw new Exception("Error preparing payment statement: " . $conn->error);
    }

    $payment_stmt->bind_param("idsss", $order_id, $cart_total, $payment_method, $transaction_id, $payment_status);
    if (!$payment_stmt->execute()) {
        throw new Exception("Error executing payment statement: " . $payment_stmt->error);
    }
    $payment_stmt->close();

    // Update order payment status if not cash on delivery
    if ($payment_method != 'cash_on_delivery') {
        $update_query = "UPDATE orders SET payment_status = 'completed' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        if (!$update_stmt) {
            throw new Exception("Error preparing order update statement: " . $conn->error);
        }
        $update_stmt->bind_param("i", $order_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Error executing order update statement: " . $update_stmt->error);
        }
        $update_stmt->close();
    }

    // Clear cart
    debug_log("Clearing user's cart: user_id=$user_id");
    if (clearCart($conn, $user_id)) {
        // Flag that the cart was cleared successfully
        $_SESSION['cart_cleared'] = true;
        debug_log("Cart cleared successfully");
    } else {
        // If the function failed, try the direct query as a fallback
        debug_log("Using fallback method to clear cart");
        $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
        $clear_cart_stmt = $conn->prepare($clear_cart_query);
        if (!$clear_cart_stmt) {
            throw new Exception("Error preparing cart clear statement: " . $conn->error);
        }
        
        $clear_cart_stmt->bind_param("i", $user_id);
        if (!$clear_cart_stmt->execute()) {
            throw new Exception("Error executing cart clear statement: " . $clear_cart_stmt->error);
        }
        debug_log("Cart cleared successfully via fallback");
        $_SESSION['cart_cleared'] = true;
        $clear_cart_stmt->close();
    }

    // Commit transaction
    $retry_count = 0;
    $max_retries = 3;
    $committed = false;

    while (!$committed && $retry_count < $max_retries) {
        try {
            $conn->commit();
            $committed = true;
            debug_log("Order transaction committed successfully on attempt " . ($retry_count + 1));
            
            // Mark order as processed to prevent duplicate submissions
            $_SESSION['order_processed'] = true;
            
            // Redirect to success page
            debug_log("Order placed successfully: order_id=$order_id");
            
            if ($is_ajax) {
                sendJsonResponse('success', 'Order placed successfully!', [
                    'showModal' => true,
                    'modalTitle' => 'Order Confirmed',
                    'modalMessage' => 'Your order has been placed successfully! You will be redirected to the confirmation page.',
                    'redirect' => "order_confirmation.php?order_id=$order_id",
                    'redirectDelay' => 2000, // Redirect after 2 seconds
                    'cartCleared' => true    // Indicate that the cart has been cleared
                ]);
            } else {
                // Store modal info in session and redirect
                $_SESSION['show_modal'] = [
                    'type' => 'success',
                    'title' => 'Order Confirmed',
                    'message' => 'Your order has been placed successfully! You will be redirected to the confirmation page.',
                    'redirect' => "order_confirmation.php?order_id=$order_id",
                    'redirectDelay' => 2000 // Redirect after 2 seconds
                ];
                redirect("order.php");
            }
            exit;
        } catch (Exception $e) {
            $retry_count++;
            debug_log("Commit attempt $retry_count failed: " . $e->getMessage());
            
            if ($retry_count >= $max_retries) {
                throw new Exception("Failed to commit transaction after $max_retries attempts: " . $e->getMessage());
            }
            
            // Wait before retrying
            usleep(500000); // 0.5 seconds
        }
    }
} catch (Exception $e) {
    // Rollback transaction on error
    try {
        debug_log("ERROR: Order processing failed. Rolling back transaction. Exception: " . $e->getMessage());
        $conn->rollback();
        debug_log("Transaction rolled back successfully");
    } catch (Exception $rollback_error) {
        debug_log("ERROR: Failed to rollback transaction: " . $rollback_error->getMessage());
    }
    
    $errors['db'] = 'Order failed: ' . $e->getMessage();
    
    if ($is_ajax) {
        sendJsonResponse('error', 'Order failed: ' . $e->getMessage(), [
            'showModal' => true,
            'modalTitle' => 'Order Failed',
            'modalMessage' => 'There was a problem processing your order: ' . $e->getMessage(),
            'redirect' => null
        ]);
    } else {
        // Store error in session as a modal
        $_SESSION['show_modal'] = [
            'type' => 'danger',
            'title' => 'Order Failed',
            'message' => 'There was a problem processing your order: ' . $e->getMessage(),
            'redirect' => null
        ];
        redirect('order.php');
    }
    exit;
}
?> 