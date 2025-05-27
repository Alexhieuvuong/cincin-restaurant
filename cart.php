<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'cart.php';
    setFlashMessage('danger', 'Please login to view your cart.');
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = getCartItems($conn, $user_id);
$cart_total = getCartTotal($conn, $user_id);

// Add debugging - hidden in HTML comment
echo "<!-- DEBUG INFO - Cart Items: " . count($cart_items) . " items -->\n";
if (count($cart_items) > 0) {
    echo "<!-- First cart item ID: " . $cart_items[0]['id'] . " -->\n";
}
?>

<h1 class="section-title">Your Cart</h1>

<div class="cart-container">
    <?php if (count($cart_items) > 0): ?>
        <?php foreach ($cart_items as $item): ?>
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
            <div class="cart-item">
                <div class="cart-item-img" style="background-image: url('<?php echo $img_url; ?>')"></div>
                <div class="cart-item-info">
                    <h3><?php echo $item['name']; ?></h3>
                    <?php
                    $cart_options = isset($_SESSION['cart_options']) ? $_SESSION['cart_options'] : [];
                    $opt_key = $user_id . '_' . $item['product_id'];
                    $extra_options = !empty($cart_options[$opt_key]) ? $cart_options[$opt_key] : [];
                    if (!empty($extra_options)) {
                        echo '<div class="cart-item-options" style="font-size:13px;color:#666;margin-bottom:4px;">';
                        echo 'Options: ' . implode(', ', array_map('ucwords', $extra_options));
                        echo '</div>';
                    }
                    ?>
                    <?php
                    $option_cost = count($extra_options) * 1.0;
                    $display_price = $item['price'] + $option_cost;
                    $subtotal = $display_price * $item['quantity'];
                    if (!isset($cart_total_with_options)) $cart_total_with_options = 0;
                    $cart_total_with_options += $subtotal;
                    ?>
                    <div class="cart-item-price"><?php echo formatPrice($display_price); ?><?php if ($option_cost > 0) echo ' <span style="font-size:12px;color:#888;">(with options)</span>'; ?></div>
                    <div class="cart-item-quantity">
                        <button class="decrement-btn" data-id="<?php echo $item['id']; ?>" data-action="decrease">-</button>
                        <input type="number" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input" data-id="<?php echo $item['id']; ?>">
                        <button class="increment-btn" data-id="<?php echo $item['id']; ?>" data-action="increase">+</button>
                        <button class="cart-item-remove" data-id="<?php echo $item['id']; ?>"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="cart-item-subtotal">
                    <strong>Subtotal:</strong> <span id="subtotal-<?php echo $item['id']; ?>"><?php echo formatPrice($subtotal); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="cart-summary">
            <div class="cart-total">Total: <?php echo formatPrice(isset($cart_total_with_options) ? $cart_total_with_options : $cart_total); ?></div>
            <a href="order.php" class="btn btn-primary">Proceed to Checkout</a>
            <a href="menu.php" class="btn btn-secondary">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="empty-cart">
            <h2>Your cart is empty</h2>
            <p>Add some delicious food items to your cart!</p>
            <a href="menu.php" class="btn btn-primary">Browse Menu</a>
        </div>
    <?php endif; ?>
</div>

<style>
.cart-container {
    background-color: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.cart-item {
    display: grid;
    grid-template-columns: 100px 1fr auto;
    gap: 20px;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.cart-item-img {
    width: 100px;
    height: 100px;
    background-size: cover;
    background-position: center;
    border-radius: 5px;
}

.cart-item-price {
    color: var(--primary-color);
    font-weight: 700;
    font-size: 18px;
    margin: 5px 0;
}

.cart-item-quantity {
    display: flex;
    align-items: center;
    margin-top: 10px;
}

.cart-item-quantity button {
    background-color: #eee;
    border: none;
    width: 30px;
    height: 30px;
    font-size: 16px;
    cursor: pointer;
}

.quantity-input {
    width: 60px;
    height: 30px;
    text-align: center;
    border: 1px solid #ddd;
    margin: 0 5px;
}

.cart-item-remove {
    margin-left: 15px;
    color: var(--danger-color);
}

.cart-item-subtotal {
    text-align: right;
    font-size: 18px;
}

.cart-summary {
    margin-top: 30px;
    text-align: right;
}

.cart-total {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 20px;
}

.empty-cart {
    text-align: center;
    padding: 40px 0;
}

.empty-cart h2 {
    margin-bottom: 15px;
    color: var(--dark-color);
}

.empty-cart p {
    margin-bottom: 20px;
}

@media screen and (max-width: 768px) {
    .cart-item {
        grid-template-columns: 1fr;
    }
    
    .cart-item-img {
        width: 150px;
        height: 150px;
        margin: 0 auto;
    }
    
    .cart-item-info {
        text-align: center;
    }
    
    .cart-item-quantity {
        justify-content: center;
    }
    
    .cart-item-subtotal {
        text-align: center;
        margin-top: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewBtns = document.querySelectorAll('.view-options-btn');
    const modal = document.getElementById('optionsModal');
    const closeModal = document.getElementById('closeOptionsModal');
    const optionsList = document.getElementById('optionsList');
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const options = JSON.parse(this.getAttribute('data-options'));
            optionsList.innerHTML = '';
            if (options.length > 0) {
                options.forEach(opt => {
                    optionsList.innerHTML += `<div style='margin-bottom:10px;'><span style='background:#f5f5f5;padding:6px 16px;border-radius:16px;color:#333;font-size:15px;'>${opt.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></div>`;
                });
            } else {
                optionsList.innerHTML = '<p>No extra options for this product.</p>';
            }
            modal.style.display = 'block';
        });
    });
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});
</script>

<?php
require_once 'includes/footer.php';
?> 