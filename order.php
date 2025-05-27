<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Import the logging function if not already available
if (!function_exists('debug_log')) {
    require_once 'config/database.php';
}

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'order.php';
    setFlashMessage('danger', 'Please login to checkout.');
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = getCartItems($conn, $user_id);
$cart_total = getCartTotal($conn, $user_id);

// If cart is empty, redirect to cart page
if (count($cart_items) === 0) {
    setFlashMessage('danger', 'Your cart is empty. Please add some items before checkout.');
    redirect('cart.php');
}

// Get user information for pre-filling the form
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
if (!$user_stmt) {
    error_log("Error preparing user query: " . $conn->error);
    setFlashMessage('danger', 'Error retrieving user information.');
    redirect('index.php');
}

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if (!$user_result || $user_result->num_rows === 0) {
    setFlashMessage('danger', 'User not found.');
    redirect('index.php');
}

$user = $user_result->fetch_assoc();
$user_stmt->close();

// Check for order errors from previous submission
$errors = [];
if (isset($_SESSION['order_errors'])) {
    $errors = $_SESSION['order_errors'];
    unset($_SESSION['order_errors']);
}

// Check for form data from previous submission
$form_data = [];
if (isset($_SESSION['order_form_data'])) {
    $form_data = $_SESSION['order_form_data'];
    unset($_SESSION['order_form_data']);
}

// Generate a CSRF token
$_SESSION['order_token'] = md5(uniqid(mt_rand(), true));

// Debug the page load
debug_log("Order page accessed by user_id=$user_id");
?>

<h1 class="section-title">Checkout</h1>

<!-- Checkout Steps Progress Bar -->
<div class="checkout-steps-bar">
    <div class="step-item completed">
        <div class="step-circle">1</div>
        <div class="step-label">Cart</div>
    </div>
    <div class="step-item active">
        <div class="step-circle">2</div>
        <div class="step-label">Checkout</div>
    </div>
    <div class="step-item">
        <div class="step-circle">3</div>
        <div class="step-label">Payment</div>
    </div>
    <div class="step-item">
        <div class="step-circle">4</div>
        <div class="step-label">Confirmation</div>
    </div>
</div>

<div class="checkout-container">
    <?php displayFlashMessage(); ?>
    
    <?php if (!empty($errors['db'])): ?>
        <div class="alert alert-danger"><?php echo $errors['db']; ?></div>
    <?php endif; ?>
    
    <!-- Modal Popup for Order Status -->
    <div class="modal-overlay" id="order-modal" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modal-title">Order Status</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="modal-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <p id="modal-message"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal()">OK</button>
            </div>
        </div>
    </div>
    
    <div class="checkout-grid">
        <!-- Order Summary -->
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="summary-items">
                <?php 
                $cart_options = isset($_SESSION['cart_options']) ? $_SESSION['cart_options'] : [];
                $order_total = 0;
                foreach ($cart_items as $item): ?>
                    <?php
                        $opt_key = $user_id . '_' . $item['product_id'];
                        $extra_options = !empty($cart_options[$opt_key]) ? $cart_options[$opt_key] : [];
                        $option_cost = count($extra_options) * 1.0;
                        $display_price = $item['price'] + $option_cost;
                        $item_subtotal = $display_price * $item['quantity'];
                        $order_total += $item_subtotal;
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
                    <div class="summary-item">
                        <div class="summary-item-img" style="background-image: url('<?php echo $img_url; ?>')"></div>
                        <div class="summary-item-details">
                            <h3 class="summary-item-title"><?php echo $item['name']; ?></h3>
                            <?php if (!empty($extra_options)) {
                                echo '<div class="cart-item-options summary-item-options" style="font-size:13px;color:#666;margin-bottom:4px;">';
                                echo 'Options: ' . implode(', ', array_map('ucwords', $extra_options));
                                echo '</div>';
                            } ?>
                            <div class="summary-item-price"><?php echo formatPrice($display_price); ?> x <?php echo $item['quantity']; ?><?php if ($option_cost > 0) echo ' <span style=\"font-size:12px;color:#888;\">(with options)</span>'; ?></div>
                        </div>
                        <div class="summary-item-total">
                            <?php echo formatPrice($item_subtotal); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="order-summary-total">
                <div class="summary-total-row">
                    <span>Subtotal:</span>
                    <span><?php echo formatPrice($order_total); ?></span>
                </div>
                <div class="summary-total-row">
                    <span>Delivery Fee:</span>
                    <span>$0.00</span>
                </div>
                <div class="summary-total-row total">
                    <span>Total:</span>
                    <span><?php echo formatPrice($order_total); ?></span>
                </div>
            </div>
            <!-- Coupon Input Section -->
            <div class="coupon-section">
                <form method="post" action="" id="coupon-form" style="display:flex;align-items:center;gap:12px;max-width:400px;margin:16px auto 0 auto;">
                    <label for="coupon_code" style="font-weight:600;">Have a coupon?</label>
                    <input type="text" name="coupon_code" id="coupon_code" class="form-control" placeholder="Enter coupon code" style="flex:1;min-width:0;">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </form>
                <div id="coupon-message" style="margin-top:8px;text-align:center;color:#006241;font-weight:600;"></div>
            </div>
        </div>
        
        <!-- Checkout Form -->
        <div class="checkout-form">
            <h2>Delivery Details</h2>
            <form action="place_order.php" method="POST" novalidate>
                <input type="hidden" name="order_token" value="<?php echo $_SESSION['order_token']; ?>">
                <div class="delivery-details">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($form_data['name']) ? $form_data['name'] : $user['name']; ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <textarea name="address" id="address" rows="3" class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" required><?php echo isset($form_data['address']) ? $form_data['address'] : $user['address']; ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($form_data['phone']) ? $form_data['phone'] : $user['phone']; ?>" required>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h2>Payment Method</h2>
                <div class="payment-methods">
                    <div class="payment-method-btn">
                        <input type="radio" name="payment_method" id="cash_on_delivery" value="cash_on_delivery" checked>
                        <label for="cash_on_delivery">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Cash on Delivery</span>
                        </label>
                    </div>
                    <div class="payment-method-btn">
                        <input type="radio" name="payment_method" id="credit_card" value="credit_card">
                        <label for="credit_card">
                            <i class="fas fa-credit-card"></i>
                            <span>Credit Card</span>
                        </label>
                    </div>
                    <div class="payment-method-btn">
                        <input type="radio" name="payment_method" id="paypal" value="paypal">
                        <label for="paypal">
                            <i class="fab fa-paypal"></i>
                            <span>PayPal</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="place-order-btn">Place Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
body {
    background: #f8f9fa;
    min-height: 100vh;
}
.checkout-container {
    margin-bottom: 40px;
    margin-top: 40px;
    max-width: 1100px;
    margin-left: auto;
    margin-right: auto;
}
.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}
.order-summary, .checkout-form {
    background: #fff;
    border-radius: 12px;
    padding: 32px 24px 24px 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    min-width: 0;
    border: 1px solid #e3e3e3;
    transition: none;
}
.summary-items {
    margin-bottom: 24px;
}
.summary-item {
    display: flex;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f0f0f0;
    gap: 14px;
}
.summary-item-img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #eee;
    background: #fff;
}
.summary-item-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.summary-item-title {
    font-weight: 700;
    font-size: 1.05rem;
    color: #222;
}
.summary-item-options {
    color: #888;
    font-size: 0.97rem;
}
.summary-item-price {
    font-weight: 600;
    font-size: 1.05rem;
    margin-left: 10px;
    color: #444;
}
.order-summary-total {
    font-size: 1.1rem;
    font-weight: 700;
    margin-top: 14px;
    text-align: right;
    color: #222;
    position: relative;
}
.order-summary-total .summary-total-row.total span:last-child {
    font-size: 1.2rem;
    color: #222;
    font-weight: 900;
    letter-spacing: 0.5px;
    transition: none;
    animation: none;
}
.coupon-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: none;
    border: none;
    border-radius: 0;
    padding: 0;
    margin: 14px auto 0 auto;
    max-width: 400px;
    box-shadow: none;
}
.coupon-section form {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.coupon-section label {
    font-weight: 600;
    color: #222;
    margin-bottom: 2px;
    align-self: flex-start;
    font-size: 1.08rem;
}
.coupon-section input[type="text"] {
    width: 100%;
    max-width: 320px;
    padding: 10px 16px;
    border: 1px solid #bdbdbd;
    border-radius: 6px;
    font-size: 1.08rem;
    background: #fff;
    transition: border 0.2s;
    box-sizing: border-box;
    letter-spacing: 0.04em;
    margin-bottom: 0;
}
.coupon-section input[type="text"]:focus {
    border: 1px solid #888;
    outline: none;
}
.coupon-section button {
    width: 100%;
    max-width: 320px;
    padding: 10px 0;
    border-radius: 6px;
    background: #222;
    color: #fff;
    border: none;
    font-weight: 600;
    font-size: 1rem;
    transition: background 0.2s;
    cursor: pointer;
    margin-top: 0;
}
.coupon-section button:hover {
    background: #444;
}
#coupon-message {
    margin-top: 8px;
    text-align: center;
    font-weight: 600;
}
.delivery-details {
    background: #fff;
    border: 1px solid #e3e3e3;
    border-radius: 8px;
    padding: 16px 14px 8px 14px;
    margin-bottom: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.delivery-details .form-group {
    margin-bottom: 12px;
    position: static;
}
.delivery-details label {
    font-weight: 600;
    color: #222;
    margin-bottom: 4px;
    display: block;
    position: static;
    background: none;
    padding: 0;
    pointer-events: auto;
    font-size: 1rem;
    transition: none;
    top: auto;
    left: auto;
}
.delivery-details input[type="text"],
.delivery-details input[type="tel"],
.delivery-details textarea {
    width: 100%;
    padding: 10px 10px;
    border: 1px solid #bdbdbd;
    border-radius: 6px;
    font-size: 1rem;
    background: #fff;
    transition: border 0.2s;
    margin-top: 2px;
}
.delivery-details input[type="text"]:focus,
.delivery-details input[type="tel"]:focus,
.delivery-details textarea:focus {
    border: 1px solid #222;
    outline: none;
}
.invalid-feedback {
    color: #d9534f;
    font-size: 0.97rem;
    margin-top: 2px;
}
.payment-methods {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
}
.payment-method-btn {
    flex: 1;
    padding: 14px 0;
    border-radius: 8px;
    border: 1px solid #e3e3e3;
    background: #fafafa;
    font-size: 1.05rem;
    font-weight: 600;
    color: #222;
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: border 0.2s, background 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    position: relative;
}
.payment-method-btn.selected {
    border: 1.5px solid #222;
    background: #f0f0f0;
}
.payment-method-btn:hover {
    border: 1.5px solid #888;
    background: #f5f5f5;
}
.payment-method-btn label {
    cursor: pointer;
    color: #222;
    font-size: 1.05rem;
    font-weight: 600;
    margin-top: 6px;
}
.payment-method-btn i {
    font-size: 1.5rem;
    color: #888;
    margin-bottom: 2px;
    transition: color 0.2s;
}
.payment-method-btn.selected i {
    color: #222;
}
.place-order-btn {
    width: 100%;
    padding: 14px 0;
    font-size: 1.1rem;
    font-weight: 700;
    border-radius: 8px;
    background: #222;
    color: #fff;
    border: none;
    margin-top: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    transition: background 0.2s;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.place-order-btn:hover {
    background: #444;
}
.place-order-btn i {
    font-size: 1.2rem;
    color: #fff;
    margin-left: 4px;
    transition: color 0.2s;
}
@media (max-width: 900px) {
    .checkout-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .order-summary, .checkout-form {
        padding: 18px 4px 14px 4px;
    }
}
.checkout-steps-bar {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 0;
    margin: 0 auto 28px auto;
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
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e3e3e3;
    color: #888;
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 4px;
    border: 2px solid #e3e3e3;
    transition: background 0.2s, border 0.2s, color 0.2s;
    z-index: 1;
}
.step-item.completed .step-circle {
    background: #222;
    color: #fff;
    border: 2px solid #222;
}
.step-item.active .step-circle {
    background: #fff;
    color: #222;
    border: 2px solid #222;
}
.step-label {
    font-size: 0.98rem;
    color: #222;
    font-weight: 600;
    margin-bottom: 0;
    margin-top: 0;
    text-align: center;
}
.step-item:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 16px;
    left: 50%;
    width: 100%;
    height: 3px;
    background: #e3e3e3;
    z-index: 0;
    transform: translateX(16px);
}
.step-item.completed:not(:last-child)::after {
    background: #222;
}
@media (max-width: 600px) {
    .checkout-steps-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        max-width: 100%;
        margin-bottom: 18px;
    }
    .step-item {
        flex-direction: row;
        align-items: center;
        margin-bottom: 6px;
    }
    .step-circle {
        margin-bottom: 0;
        margin-right: 8px;
    }
    .step-label {
        font-size: 0.97rem;
        margin-top: 0;
    }
    .step-item:not(:last-child)::after {
        top: 50%;
        left: 32px;
        width: calc(100% - 40px);
        height: 2px;
        transform: none;
    }
}
</style>

<script>
// Add client-side error handling and timeout detection for form submission
document.addEventListener('DOMContentLoaded', function() {
    const orderForm = document.querySelector('form[action="place_order.php"]');
    
    // Check if there's a modal to show from the session
    <?php if (isset($_SESSION['show_modal'])): ?>
        const modalData = <?php echo json_encode($_SESSION['show_modal']); ?>;
        showModal(modalData.title, modalData.message, modalData.type);
        
        // Redirect if needed
        if (modalData.redirect) {
            setTimeout(function() {
                window.location.href = modalData.redirect;
            }, modalData.redirectDelay || 3000);
        }
        
        // Clear the modal data from session
        <?php unset($_SESSION['show_modal']); ?>
    <?php endif; ?>
    
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Add a hidden div to show errors
            let messageDiv = document.getElementById('order-message');
            if (!messageDiv) {
                messageDiv = document.createElement('div');
                messageDiv.id = 'order-message';
                messageDiv.className = 'alert alert-info';
                messageDiv.style.marginTop = '20px';
                messageDiv.innerHTML = 'Processing your order...';
                messageDiv.style.display = 'none';
                this.appendChild(messageDiv);
            }
            
            messageDiv.className = 'alert alert-info';
            messageDiv.innerHTML = 'Processing your order...';
            messageDiv.style.display = 'block';
            
            // Set a timeout to handle long-running requests
            const orderTimeout = setTimeout(function() {
                messageDiv.className = 'alert alert-warning';
                messageDiv.innerHTML = 'Your order is still processing. Please wait...';
            }, 5000); // 5 seconds
            
            // Set a timeout for network failure
            const networkTimeout = setTimeout(function() {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                messageDiv.className = 'alert alert-danger';
                messageDiv.innerHTML = 'The request is taking too long. Please try again. If the problem persists, please contact support.';
                
                // Log the timeout to the console
                console.error('Order submission timed out after 30 seconds');
            }, 30000); // 30 seconds
            
            // Use fetch API to submit the form
            fetch('place_order.php', {
                method: 'POST',
                body: new FormData(orderForm),
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                // Clear the timeouts
                clearTimeout(orderTimeout);
                clearTimeout(networkTimeout);
                
                // Check if response is a redirect
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                
                // Check content type for JSON response
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        // Clear the timeouts
                        clearTimeout(orderTimeout);
                        clearTimeout(networkTimeout);
                        
                        // Check if we need to show a modal
                        if (data.data && data.data.showModal) {
                            showModal(
                                data.data.modalTitle || 'Order Status', 
                                data.data.modalMessage || data.message,
                                data.status
                            );
                            
                            // If cart was cleared in the response, update the cart count
                            if (data.data.cartCleared) {
                                updateCartCount(0);
                            }
                            
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalText;
                            messageDiv.style.display = 'none';
                            
                            // Handle redirect if provided
                            if (data.data.redirect) {
                                setTimeout(function() {
                                    // Add a timestamp parameter to prevent caching
                                    const redirectUrl = data.data.redirect + 
                                        (data.data.redirect.includes('?') ? '&' : '?') + 
                                        '_=' + new Date().getTime();
                                    window.location.href = redirectUrl;
                                }, data.data.redirectDelay || 3000);
                            }
                            
                            return data;
                        }
                        
                        if (data.status === 'success') {
                            messageDiv.className = 'alert alert-success';
                            messageDiv.innerHTML = data.message;
                            
                            // Check if we need to redirect
                            if (data.data && data.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = data.data.redirect;
                                }, 1000); // Wait 1 second to show the success message
                            }
                        } else {
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalText;
                            
                            messageDiv.className = 'alert alert-danger';
                            messageDiv.innerHTML = data.message;
                            
                            // If there are field-specific errors, mark them
                            if (data.data && data.data.errors) {
                                const errors = data.data.errors;
                                Object.keys(errors).forEach(field => {
                                    const input = document.getElementById(field);
                                    if (input) {
                                        input.classList.add('is-invalid');
                                        
                                        // Create or update error message
                                        let feedback = input.nextElementSibling;
                                        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                                            feedback = document.createElement('div');
                                            feedback.className = 'invalid-feedback';
                                            input.parentNode.insertBefore(feedback, input.nextSibling);
                                        }
                                        feedback.textContent = errors[field];
                                    }
                                });
                            }
                            
                            // If there's a redirect for errors
                            if (data.data && data.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = data.data.redirect;
                                }, 2000); // Wait 2 seconds to show the error message
                            }
                        }
                        
                        return data;
                    });
                }
                
                // Parse the response as text (for non-JSON responses)
                return response.text().then(text => {
                    // Try to parse as JSON first
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // If not JSON, it's probably HTML (error page or form with validation errors)
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalText;
                        
                        messageDiv.className = 'alert alert-danger';
                        messageDiv.innerHTML = 'There was an error processing your order. Please try again.';
                        
                        console.error('Error processing response', text.substring(0, 500));
                        
                        // If it contains a redirect in JavaScript, execute it
                        if (text.includes('window.location.href')) {
                            const redirectMatch = text.match(/window\.location\.href\s*=\s*['"]([^'"]+)['"]/);
                            if (redirectMatch && redirectMatch[1]) {
                                window.location.href = redirectMatch[1];
                            }
                        }
                    }
                });
            })
            .catch(error => {
                // Clear the timeouts
                clearTimeout(orderTimeout);
                clearTimeout(networkTimeout);
                
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                messageDiv.className = 'alert alert-danger';
                messageDiv.innerHTML = 'There was a network error. Please check your connection and try again.';
                
                console.error('Fetch error:', error);
            });
            
            // Prevent default form submission
            e.preventDefault();
        });
    }

    var savedLocation = localStorage.getItem('user_location');
    var addressInput = document.querySelector('textarea[name="address"]');
    if (savedLocation && addressInput) {
        addressInput.value = savedLocation;
    }
});

// Function to show modal popup
function showModal(title, message, type = 'info') {
    const modal = document.getElementById('order-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const modalIcon = document.getElementById('modal-icon').querySelector('i');
    
    // Update modal content
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    // Set the appropriate icon based on type
    modalIcon.className = ''; // Clear existing classes
    
    switch(type) {
        case 'success':
            modalIcon.className = 'fas fa-check-circle';
            // If this is a success modal, clear the cart count in the header
            updateCartCount(0);
            break;
        case 'info':
            modalIcon.className = 'fas fa-info-circle';
            break;
        case 'warning':
            modalIcon.className = 'fas fa-exclamation-triangle';
            break;
        case 'danger':
        case 'error':
            modalIcon.className = 'fas fa-times-circle';
            break;
        default:
            modalIcon.className = 'fas fa-info-circle';
    }
    
    // Add type-based class for styling
    modal.className = 'modal-overlay modal-' + (type === 'error' ? 'danger' : type);
    
    // Show the modal
    modal.style.display = 'flex';
    
    // Apply a clean blur effect on the backdrop - the modal overlay handles the blur
    // We don't need to blur individual elements anymore
    
    // Save the current scroll position and prevent scrolling on body
    document.body.style.overflow = 'hidden';
    
    // Make sure the modal container and its contents have no blur
    const modalContainer = document.querySelector('.modal-container');
    if (modalContainer) {
        modalContainer.style.filter = 'none';
        Array.from(modalContainer.querySelectorAll('*')).forEach(el => {
            el.style.filter = 'none';
            el.style.webkitFilter = 'none';
        });
    }
}

// Function to close modal
function closeModal() {
    const modal = document.getElementById('order-modal');
    modal.style.display = 'none';
    
    // Re-enable scrolling
    document.body.style.overflow = '';
    
    // Check if there's a redirect URL in the modal data
    const redirectUrl = modal.getAttribute('data-redirect');
    if (redirectUrl) {
        window.location.href = redirectUrl;
    }
}

// Function to update cart count in the header
function updateCartCount(count) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}

document.querySelectorAll('.payment-method-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');
    });
});

document.getElementById('coupon-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var code = document.getElementById('coupon_code').value.trim();
    var msg = document.getElementById('coupon-message');
    if (!code) {
        msg.textContent = 'Please enter a coupon code.';
        msg.style.color = '#d9534f';
        return;
    }
    // Placeholder: Replace with AJAX or backend validation
    if (code.toUpperCase() === 'WOWHIEUVUONG25') {
        msg.textContent = 'Coupon applied! You get a discount!';
        msg.style.color = '#28a745';
    } else {
        msg.textContent = 'Invalid coupon code.';
        msg.style.color = '#d9534f';
    }
});

// Add checkmark icon to Place Order button
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.querySelector('.place-order-btn');
    if (btn && !btn.querySelector('i')) {
        var icon = document.createElement('i');
        icon.className = 'fas fa-check-circle';
        btn.appendChild(icon);
    }
});
</script>

<?php
require_once 'includes/footer.php';
?> 
?> 