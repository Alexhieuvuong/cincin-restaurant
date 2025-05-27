<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Import the logging function if not already available
if (!function_exists('debug_log')) {
    require_once 'config/database.php';
}

// Redirect if not logged in
if (!isLoggedIn()) {
    debug_log("User not logged in, redirecting to login page");
    redirect('login.php');
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id = $_SESSION['user_id'];
debug_log("Order confirmation page accessed - order_id=$order_id, user_id=$user_id");

// Clear the order processed flag to allow future orders
if (isset($_SESSION['order_processed'])) {
    unset($_SESSION['order_processed']);
    debug_log("Cleared order_processed flag for user_id=$user_id");
}

// If no valid order ID, redirect to home
if ($order_id <= 0) {
    debug_log("Invalid order ID, redirecting to index page");
    redirect('index.php');
}

// Get order details
debug_log("Retrieving order details for order_id=$order_id");
$order_query = "SELECT o.*, p.payment_method, p.transaction_id, p.status as payment_status 
                FROM orders o 
                LEFT JOIN payments p ON o.id = p.order_id 
                WHERE o.id = ? AND o.user_id = ?";
$order_stmt = $conn->prepare($order_query);
if (!$order_stmt) {
    debug_log("Error preparing order query: " . $conn->error);
    setFlashMessage('danger', 'Error retrieving order information.');
    redirect('index.php');
}

$order_stmt->bind_param("ii", $order_id, $user_id);
debug_log("Executing order query with parameters: order_id=$order_id, user_id=$user_id");
$order_stmt->execute();
$order_result = $order_stmt->get_result();

// If order not found or doesn't belong to current user, redirect to home
if (!$order_result || $order_result->num_rows === 0) {
    debug_log("Order not found or doesn't belong to current user, redirecting to index page");
    setFlashMessage('danger', 'Order not found.');
    redirect('index.php');
}

debug_log("Order found, retrieving details");
$order = $order_result->fetch_assoc();
$order_stmt->close();

// Print order details for debugging
debug_log("Order details: " . json_encode([
    'id' => $order['id'],
    'total_amount' => $order['total_amount'],
    'status' => $order['status'],
    'payment_status' => $order['payment_status'],
    'payment_method' => $order['payment_method'] ?? 'unknown'
]));

// Get order items
debug_log("Retrieving order items for order_id=$order_id");
$items_query = "SELECT oi.*, p.name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
if (!$items_stmt) {
    debug_log("Error preparing items query: " . $conn->error);
    setFlashMessage('danger', 'Error retrieving order items.');
    redirect('index.php');
}

$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = [];

if ($items_result && $items_result->num_rows > 0) {
    debug_log("Found " . $items_result->num_rows . " items for order_id=$order_id");
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
} else {
    debug_log("No items found for order_id=$order_id");
}
$items_stmt->close();

// Before rendering the page, add one more debug statement
debug_log("Ready to render order confirmation page for order_id=$order_id with " . count($order_items) . " items");
?>

<h1 class="section-title">Order Confirmation</h1>

<!-- Checkout Steps Progress Bar -->
<div class="checkout-steps-bar">
    <div class="step-item active">
        <div class="step-circle">1</div>
        <div class="step-label">Cart</div>
    </div>
    <div class="step-item active">
        <div class="step-circle">2</div>
        <div class="step-label">Checkout</div>
    </div>
    <div class="step-item active">
        <div class="step-circle">3</div>
        <div class="step-label">Payment</div>
    </div>
    <div class="step-item active">
        <div class="step-circle">4</div>
        <div class="step-label">Confirmation</div>
    </div>
</div>

<style>
.checkout-steps-bar {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 0;
    margin: 0 auto 36px auto;
    max-width: 700px;
    padding: 0 12px;
    position: relative;
    z-index: 2;
}
.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1 1 0;
    position: relative;
}
.step-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #e3e3e3;
    color: #888;
    font-weight: 700;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 6px;
    border: 2.5px solid #e3e3e3;
    transition: background 0.2s, border 0.2s, color 0.2s;
    z-index: 1;
}
.step-item.active .step-circle {
    background: #006241;
    color: #fff;
    border: 2.5px solid #006241;
}
.step-label {
    font-size: 1rem;
    color: #222;
    font-weight: 600;
    margin-bottom: 0;
    margin-top: 0;
    text-align: center;
}
.step-item:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 19px;
    left: 50%;
    width: 100%;
    height: 4px;
    background: #e3e3e3;
    z-index: 0;
    transform: translateX(19px);
}
.step-item.active:not(:last-child)::after {
    background: linear-gradient(90deg, #006241 60%, #e3e3e3 100%);
}
@media (max-width: 600px) {
    .checkout-steps-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        max-width: 100%;
        margin-bottom: 24px;
    }
    .step-item {
        flex-direction: row;
        align-items: center;
        margin-bottom: 8px;
    }
    .step-circle {
        margin-bottom: 0;
        margin-right: 10px;
    }
    .step-label {
        font-size: 0.98rem;
        margin-top: 0;
    }
    .step-item:not(:last-child)::after {
        top: 50%;
        left: 38px;
        width: calc(100% - 48px);
        height: 3px;
        transform: none;
    }
}
</style>

<div class="confirmation-container">
    <div class="confirmation-box">
        <div class="confirmation-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Thank You for Your Order!</h2>
        <p>Your order has been received and is being processed.</p>
        <div class="order-info">
            <div class="order-info-item">
                <span>Order Number:</span>
                <span>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="order-info-item">
                <span>Order Date:</span>
                <span><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="order-info-item">
                <span>Payment Method:</span>
                <span><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></span>
            </div>
            <div class="order-info-item">
                <span>Payment Status:</span>
                <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                    <?php echo ucfirst($order['payment_status']); ?>
                </span>
            </div>
            <div class="order-info-item">
                <span>Order Status:</span>
                <span class="status-badge status-<?php echo $order['status']; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
            <div class="order-info-item">
                <span>Total Amount:</span>
                <span class="order-total"><?php echo formatPrice($order['total_amount']); ?></span>
            </div>
        </div>
        
        <h3>Order Items</h3>
        <div class="order-items">
            <?php foreach ($order_items as $item): ?>
                <?php
                    // Fallback images by product name
                    $fallback_images = [
                        'Margherita Pizza' => 'assets/images/margherita-pizza.jpg',
                        'Pepperoni Pizza' => 'assets/images/pepperoni-pizza.jpg',
                        'Vegetarian Pizza' => 'assets/images/vegetarian-pizza.jpg',
                        'Classic Burger' => 'assets/images/classic-burger.jpg',
                        'Cheese Burger' => 'assets/images/cheese-burger.jpg',
                        'Veggie Burger' => 'assets/images/veggie-burger.jpg',
                        'Spaghetti Bolognese' => 'assets/images/spaghetti-bolognese.jpg',
                        'Fettuccine Alfredo' => 'assets/images/fettuccine-alfredo.jpg',
                        'Caesar Salad' => 'assets/images/caesar-salad.jpg',
                        'Greek Salad' => 'assets/images/greek-salad.jpg',
                        'Chocolate Cake' => 'assets/images/chocolate-cake.jpg',
                        'Cheesecake' => 'assets/images/cheesecake.jpg',
                        'Coca-Cola' => 'assets/images/coca-cola.jpg',
                        'Orange Juice' => 'assets/images/orange-juice.jpg',
                    ];
                    
                    $img_file = isset($item['image']) ? trim($item['image']) : '';
                    $img_path = $img_file ? "assets/images/$img_file" : '';
                    $img_url = $img_path && file_exists($img_path) && filesize($img_path) > 0
                        ? $img_path
                        : (isset($fallback_images[$item['name']]) ? $fallback_images[$item['name']] : 'assets/images/food.jpg');
                ?>
                <div class="order-item">
                    <div class="order-item-img" style="background-image: url('<?php echo $img_url; ?>')"></div>
                    <div class="order-item-info">
                        <h4><?php echo $item['name']; ?></h4>
                        <div class="order-item-details">
                            <span><?php echo formatPrice($item['price']); ?> x <?php echo $item['quantity']; ?></span>
                            <span class="order-item-total"><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <h3>Delivery Information</h3>
        <div class="delivery-info">
            <div class="delivery-info-item">
                <span>Delivery Address:</span>
                <span><?php echo $order['address']; ?></span>
            </div>
            <div class="delivery-info-item">
                <span>Phone Number:</span>
                <span><?php echo $order['phone']; ?></span>
            </div>
        </div>
        
        <div class="confirmation-actions">
            <a href="profile.php?tab=orders" class="btn btn-primary">View All Orders</a>
            <a href="menu.php" class="btn btn-secondary">Continue Shopping</a>
        </div>
    </div>
</div>

<style>
.confirmation-container {
    margin-bottom: 40px;
}

.confirmation-box {
    background-color: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.confirmation-icon {
    font-size: 80px;
    color: var(--success-color);
    margin-bottom: 20px;
}

.confirmation-box h2 {
    margin-bottom: 15px;
    color: var(--dark-color);
}

.confirmation-box p {
    margin-bottom: 30px;
    color: #666;
    font-size: 18px;
}

.order-info, .delivery-info {
    background-color: #f9f9f9;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: left;
}

.order-info-item, .delivery-info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.order-info-item:last-child, .delivery-info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.order-total {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
}

.status-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    text-transform: uppercase;
    font-weight: 700;
    color: white;
}

.status-pending {
    background-color: #f0ad4e;
}

.status-completed {
    background-color: #5cb85c;
}

.status-failed {
    background-color: #d9534f;
}

.status-processing {
    background-color: #5bc0de;
}

.status-out_for_delivery {
    background-color: #5bc0de;
}

.status-delivered {
    background-color: #5cb85c;
}

.status-cancelled {
    background-color: #d9534f;
}

.confirmation-box h3 {
    margin: 30px 0 15px;
    color: var(--dark-color);
}

.order-items {
    margin-bottom: 30px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
    text-align: left;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item-img {
    width: 60px;
    height: 60px;
    background-size: cover;
    background-position: center;
    border-radius: 5px;
    margin-right: 15px;
}

.order-item-info {
    flex: 1;
}

.order-item-info h4 {
    margin-bottom: 5px;
}

.order-item-details {
    display: flex;
    justify-content: space-between;
    color: #666;
}

.order-item-total {
    font-weight: 700;
    color: var(--primary-color);
}

.confirmation-actions {
    margin-top: 30px;
}

.confirmation-actions .btn {
    margin: 0 10px;
}

@media screen and (max-width: 768px) {
    .confirmation-box {
        padding: 20px;
    }
    
    .order-info-item, .delivery-info-item {
        flex-direction: column;
    }
    
    .order-info-item span:first-child, .delivery-info-item span:first-child {
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .confirmation-actions .btn {
        display: block;
        margin: 10px auto;
    }
}
</style>

<?php
require_once 'includes/footer.php';
?> 