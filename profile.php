<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get current user information
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Get active tab from URL
$active_tab = isset($_GET['tab']) ? clean($_GET['tab']) : 'profile';

// Process profile update
$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Get form inputs
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $address = clean($_POST['address'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors['email'] = 'Invalid email format';
    } elseif ($email != $user['email']) {
        // Check if new email already exists
        $email_check_query = "SELECT * FROM users WHERE email = '$email' AND id != $user_id";
        $email_check_result = $conn->query($email_check_query);
        if ($email_check_result && $email_check_result->num_rows > 0) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    // Validate address
    if (empty($address)) {
        $errors['address'] = 'Address is required';
    }
    
    // Validate phone
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    }
    
    // Handle password change if current password is provided
    if (!empty($current_password)) {
        // Verify current password
        if (!verifyPassword($current_password, $user['password'])) {
            $errors['current_password'] = 'Current password is incorrect';
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors['new_password'] = 'New password is required';
        } elseif (strlen($new_password) < 6) {
            $errors['new_password'] = 'New password must be at least 6 characters';
        }
        
        // Validate confirm password
        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        // Start building query
        $update_query = "UPDATE users SET name = '$name', email = '$email', address = '$address', phone = '$phone'";
        
        // Add password update if changing password
        if (!empty($current_password) && !empty($new_password)) {
            $hashed_password = hashPassword($new_password);
            $update_query .= ", password = '$hashed_password'";
        }
        
        $update_query .= " WHERE id = $user_id";
        
        if ($conn->query($update_query)) {
            // Update session variables
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            
            setFlashMessage('success', 'Profile updated successfully');
            
            // Refresh user data
            $user_result = $conn->query($user_query);
            $user = $user_result->fetch_assoc();
        } else {
            $errors['db'] = 'Failed to update profile: ' . $conn->error;
        }
    }
}

// Get user orders for orders tab
$orders = [];
if ($active_tab == 'orders') {
    $orders_query = "SELECT o.*, p.payment_method, p.status as payment_status 
                     FROM orders o 
                     LEFT JOIN payments p ON o.id = p.order_id 
                     WHERE o.user_id = $user_id 
                     ORDER BY o.created_at DESC";
    $orders_result = $conn->query($orders_query);
    
    if ($orders_result && $orders_result->num_rows > 0) {
        while ($order = $orders_result->fetch_assoc()) {
            $orders[] = $order;
        }
    }
}

// Get order details if order_id is provided
$order_items = [];
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    
    // Get order items
    $items_query = "SELECT oi.*, p.name, p.image 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = $order_id";
    $items_result = $conn->query($items_query);
    
    if ($items_result && $items_result->num_rows > 0) {
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
    }
    
    // Get order details
    $order_query = "SELECT o.*, p.payment_method, p.transaction_id, p.status as payment_status 
                    FROM orders o 
                    LEFT JOIN payments p ON o.id = p.order_id 
                    WHERE o.id = $order_id AND o.user_id = $user_id";
    $order_result = $conn->query($order_query);
    $order_details = $order_result->fetch_assoc();
}
?>

<h1 class="section-title">My Account</h1>

<div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <h3>Account Navigation</h3>
        <ul class="profile-nav">
            <li><a href="profile.php?tab=profile" class="<?php echo $active_tab == 'profile' ? 'active' : ''; ?>">Profile Information</a></li>
            <li><a href="profile.php?tab=orders" class="<?php echo $active_tab == 'orders' ? 'active' : ''; ?>">Order History</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="profile-main">
        <?php displayFlashMessage(); ?>
        
        <?php if (!empty($errors['db'])): ?>
            <div class="alert alert-danger"><?php echo $errors['db']; ?></div>
        <?php endif; ?>
        
        <?php if ($active_tab == 'profile'): ?>
            <!-- Modern Profile Information Tab -->
            <div class="profile-form-section">
                <h1>Personal Information</h1>
                <form action="" method="POST" novalidate>
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>

                    <label for="phone">Phone Number <span style="color:#888;font-weight:400;">(Optional)</span></label>
                    <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="Phone number">

                    <div class="section-title">Receive news and offers via</div>
                    <div class="toggle-row">
                        <span>SMS</span>
                        <input type="checkbox" class="toggle-switch" name="pref_sms">
                    </div>
                    <div class="toggle-row">
                        <span>Email</span>
                        <input type="checkbox" class="toggle-switch" name="pref_email">
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary" style="margin-top:32px;width:100%;font-size:1.1rem;">Save changes</button>
                </form>
                <a class="modify-link" href="#" style="margin-top:32px;">Modify account</a>
                <div class="password-section" style="display:none; margin-top:24px;">
                    <form action="" method="POST" novalidate>
                        <label for="current_password">Current password</label>
                        <input type="password" name="current_password" id="current_password" placeholder="Current password">
                        <label for="new_password">New password</label>
                        <input type="password" name="new_password" id="new_password" placeholder="New password">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password">
                        <button type="submit" name="update_password" class="btn btn-primary" style="margin-top:18px;width:100%;font-size:1.05rem;">Save new password</button>
                    </form>
                </div>
            </div>
        <?php elseif ($active_tab == 'orders'): ?>
            <!-- Order History Tab -->
            <?php if (isset($_GET['order_id']) && !empty($order_items)): ?>
                <!-- Order Details View -->
                <div class="order-details-header">
                    <h2>Order #<?php echo str_pad($order_details['id'], 6, '0', STR_PAD_LEFT); ?></h2>
                    <a href="profile.php?tab=orders" class="btn btn-secondary btn-sm">Back to Orders</a>
                </div>
                
                <div class="order-details">
                    <div class="order-details-info">
                        <div class="order-info-item">
                            <span>Order Date:</span>
                            <span><?php echo date('F j, Y, g:i a', strtotime($order_details['created_at'])); ?></span>
                        </div>
                        <div class="order-info-item">
                            <span>Status:</span>
                            <span class="status-badge status-<?php echo $order_details['status']; ?>">
                                <?php echo ucfirst($order_details['status']); ?>
                            </span>
                        </div>
                        <div class="order-info-item">
                            <span>Payment Method:</span>
                            <span><?php echo ucwords(str_replace('_', ' ', $order_details['payment_method'])); ?></span>
                        </div>
                        <div class="order-info-item">
                            <span>Payment Status:</span>
                            <span class="status-badge status-<?php echo $order_details['payment_status']; ?>">
                                <?php echo ucfirst($order_details['payment_status']); ?>
                            </span>
                        </div>
                        <div class="order-info-item">
                            <span>Total Amount:</span>
                            <span class="order-total"><?php echo formatPrice($order_details['total_amount']); ?></span>
                        </div>
                    </div>
                    
                    <h3>Order Items</h3>
                    <div class="order-items">
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div class="order-item-img" style="background-image: url('assets/images/<?php echo $item['image']; ?>')"></div>
                                <div class="order-item-info">
                                    <h4><?php echo $item['name']; ?></h4>
                                    <?php 
                                    if (!empty($item['options'])) {
                                        $options = json_decode($item['options'], true);
                                        if ($options && is_array($options)) {
                                            echo '<div class="cart-item-options" style="font-size:13px;color:#666;margin-bottom:4px;">';
                                            echo 'Options: ' . implode(', ', array_map('ucwords', $options));
                                            echo '</div>';
                                        }
                                    }
                                    ?>
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
                            <span><?php echo $order_details['address']; ?></span>
                        </div>
                        <div class="delivery-info-item">
                            <span>Phone Number:</span>
                            <span><?php echo $order_details['phone']; ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Order List View -->
                <h2>Order History</h2>
                
                <?php if (count($orders) > 0): ?>
                    <div class="orders-list">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatPrice($order['total_amount']); ?></td>
                                        <td>
                                            <a href="profile.php?tab=orders&order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <p>You haven't placed any orders yet.</p>
                        <a href="menu.php" class="btn btn-primary">Browse Menu</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var modifyLink = document.querySelector('.modify-link');
  if (modifyLink) {
    modifyLink.addEventListener('click', function(e) {
      e.preventDefault();
      var section = document.querySelector('.password-section');
      if (section) {
        section.style.display = (section.style.display === 'none' || section.style.display === '') ? 'block' : 'none';
      }
    });
  }
});
</script>

<style>
body {
    background: #f6f8fa;
}
.profile-container {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.profile-sidebar {
    background-color: #f4f8f6;
    border: 1.5px solid #e3e3e3;
    border-radius: 16px;
    padding: 36px 32px 32px 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    height: fit-content;
    margin-bottom: 32px;
}

.profile-main {
    background-color: #fff;
    border: 1.5px solid #e3e3e3;
    border-radius: 16px;
    padding: 36px 32px 32px 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    margin-bottom: 32px;
}

.profile-nav {
    margin-top: 24px;
    margin-bottom: 28px;
}

.profile-nav li {
    margin-bottom: 16px;
}

.profile-nav a {
    display: block;
    padding: 14px 18px;
    border-radius: 8px;
    transition: all 0.3s ease;
    color: #174832;
    font-weight: 600;
    background: none;
    letter-spacing: 0.5px;
    font-size: 1.08rem;
}

.profile-nav a:hover,
.profile-nav a.active {
    background-color: #174832;
    color: #fff;
}

h2 {
    margin-bottom: 28px;
    margin-top: 0;
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.form-group {
    margin-bottom: 28px;
}

.form-text {
    display: block;
    margin-top: 7px;
    color: #666;
    font-size: 15px;
}

h3 {
    margin: 36px 0 18px;
    color: var(--dark-color);
    font-size: 1.25rem;
    font-weight: 700;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
}

.orders-table th, .orders-table td {
    padding: 16px 18px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.orders-table th {
    background-color: #f9f9f9;
    font-weight: 700;
}

.orders-table tr:hover {
    background-color: #f4f8f6;
}

.btn-sm {
    padding: 7px 14px;
    font-size: 15px;
}

.no-orders {
    text-align: center;
    padding: 40px 0 30px 0;
}

.no-orders p {
    margin-bottom: 24px;
    color: #666;
}

.order-details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
}

.order-details-info {
    background-color: #f9f9f9;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 28px;
}

.order-info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 14px;
    padding-bottom: 14px;
    border-bottom: 1px solid #eee;
}

.order-info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.order-total {
    font-size: 19px;
    font-weight: 700;
    color: var(--primary-color);
}

.order-items {
    margin-bottom: 28px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 18px 0;
    border-bottom: 1px solid #eee;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item-img {
    width: 64px;
    height: 64px;
    background-size: cover;
    background-position: center;
    border-radius: 7px;
    margin-right: 18px;
}

.order-item-info {
    flex: 1;
}

.order-item-info h4 {
    margin-bottom: 7px;
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

.delivery-info {
    background-color: #f9f9f9;
    border-radius: 12px;
    padding: 18px;
}

.delivery-info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 14px;
    padding-bottom: 14px;
    border-bottom: 1px solid #eee;
}

.delivery-info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
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

@media screen and (max-width: 900px) {
    .profile-container {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .profile-sidebar, .profile-main {
        border-radius: 12px;
        padding: 24px 10px 20px 10px;
    }
}
@media screen and (max-width: 600px) {
    .profile-sidebar, .profile-main {
        border-radius: 8px;
        padding: 12px 2vw 10px 2vw;
    }
    .profile-container {
        gap: 12px;
    }
    h2 { font-size: 1.3rem; }
}

.profile-form-section {
    max-width: 480px;
    margin: 0 auto 40px auto;
    background: #fff;
    border-radius: 18px;
    padding: 32px 24px 24px 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.04);
}
.profile-form-section h1 {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 32px;
    text-align: left;
}
.profile-form-section label {
    font-weight: 700;
    margin-bottom: 8px;
    display: block;
    font-size: 1.08rem;
}
.profile-form-section input[type="text"],
.profile-form-section input[type="email"],
.profile-form-section input[type="tel"] {
    width: 100%;
    padding: 16px 14px;
    border: 1.5px solid #ddd;
    border-radius: 12px;
    font-size: 1.08rem;
    margin-bottom: 24px;
    background: #fff;
    transition: border 0.2s;
}
.profile-form-section input[readonly],
.profile-form-section input[disabled] {
    background: #f3f1ef;
    color: #888;
    cursor: not-allowed;
}
.section-title {
    font-size: 1.15rem;
    font-weight: 800;
    margin: 32px 0 18px 0;
}
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}
.toggle-switch {
    width: 44px;
    height: 24px;
    appearance: none;
    background: #ddd;
    border-radius: 12px;
    position: relative;
    outline: none;
    transition: background 0.2s;
}
.toggle-switch:checked {
    background: #174832;
}
.toggle-switch::before {
    content: '';
    position: absolute;
    left: 4px;
    top: 4px;
    width: 16px;
    height: 16px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
}
.toggle-switch:checked::before {
    transform: translateX(20px);
}
.modify-link {
    color: #222;
    text-decoration: underline;
    font-size: 1rem;
    margin-top: 32px;
    display: inline-block;
    cursor: pointer;
}
.password-section label {
    font-weight: 700;
    margin-bottom: 8px;
    display: block;
    font-size: 1.08rem;
}
.password-section input[type="password"] {
    width: 100%;
    padding: 16px 14px;
    border: 1.5px solid #ddd;
    border-radius: 12px;
    font-size: 1.08rem;
    margin-bottom: 18px;
    background: #fff;
    transition: border 0.2s;
}
@media (max-width: 600px) {
    .profile-form-section {
        padding: 18px 6px 18px 6px;
        border-radius: 10px;
    }
    .profile-form-section h1 {
        font-size: 1.3rem;
    }
}
</style>

<?php
require_once 'includes/footer.php';
?> 