<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no product ID provided or invalid, redirect to menu
if ($product_id <= 0) {
    redirect('menu.php');
}

// Get product details
$product = getProductById($conn, $product_id);

// If product not found, redirect to menu
if (!$product) {
    setFlashMessage('danger', 'Product not found.');
    redirect('menu.php');
}

// Determine product image path
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

$img_file = isset($product['image']) ? trim($product['image']) : '';
if ($img_file) {
    if (strpos($img_file, 'assets/images/') === 0 || strpos($img_file, 'uploads/products/') === 0) {
        $img_path = $img_file;
    } else {
        // If the file exists in uploads/products, use that
        if (file_exists('uploads/products/' . $img_file)) {
            $img_path = 'uploads/products/' . $img_file;
        } else {
            $img_path = 'assets/images/' . $img_file;
        }
    }
} else {
    $img_path = '';
}
$img_url = $img_path && file_exists($img_path) && filesize($img_path) > 0
    ? $img_path
    : (isset($fallback_images[$product['name']]) ? $fallback_images[$product['name']] : 'assets/images/food.jpg');

// Get related products from the same category
$related_query = "SELECT * FROM products 
                  WHERE category_id = {$product['category_id']} 
                  AND id != {$product_id}
                  AND is_available = 1
                  LIMIT 5";
$related_result = $conn->query($related_query);
$related_products = [];
if ($related_result && $related_result->num_rows > 0) {
    while ($row = $related_result->fetch_assoc()) {
        $related_products[] = $row;
    }
}
// If less than 5, fill with other products
if (count($related_products) < 5) {
    $exclude_ids = array_map(function($p) { return $p['id']; }, $related_products);
    $exclude_ids[] = $product_id;
    $exclude_ids_str = implode(',', $exclude_ids);
    $fill_query = "SELECT * FROM products WHERE id NOT IN ($exclude_ids_str) AND is_available = 1 LIMIT " . (5 - count($related_products));
    $fill_result = $conn->query($fill_query);
    if ($fill_result && $fill_result->num_rows > 0) {
        while ($row = $fill_result->fetch_assoc()) {
            $related_products[] = $row;
        }
    }
}
?>

<div class="product-details-container">
    <div class="product-details">
        <div class="product-image">
            <div class="product-img-large" style="background-image: url('<?php echo $img_url; ?>')"></div>
        </div>
        <div class="product-info">
            <h1><?php echo $product['name']; ?></h1>
            <p class="product-category"><?php echo $product['category_name']; ?></p>
            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
            <div class="product-description">
                <h3>Description</h3>
                <p><?php echo $product['description']; ?></p>
            </div>
            <div class="product-actions">
                <?php if (isLoggedIn()): ?>
                    <div class="quantity-selector">
                        <label for="quantity">Quantity:</label>
                        <div class="quantity-input-group">
                            <button type="button" class="quantity-btn decrement-btn">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" class="quantity-input">
                            <button type="button" class="quantity-btn increment-btn">+</button>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-lg add-to-cart-with-qty" data-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-lg">Login to Order</a>
                <?php endif; ?>
            </div>
            <div class="product-options" style="margin: 18px 0 24px 0;">
                <label style="font-weight:600;">Add more options:</label><br>
                <label><input type="checkbox" class="option-checkbox" value="Olive"> Olive (+€1)</label><br>
                <label><input type="checkbox" class="option-checkbox" value="Cheese"> Cheese (+€1)</label><br>
                <label><input type="checkbox" class="option-checkbox" value="Extra Sauce"> Extra Sauce (+€1)</label>
            </div>
        </div>
    </div>
    
    <!-- Product Options Modal (with overlay and animation) -->
    <div class="modal-overlay" id="optionsOverlay" style="display:none;">
        <div class="modal animated-modal" id="optionsModal" tabindex="-1" aria-modal="true" role="dialog">
            <div class="modal-accent"></div>
            <div class="modal-content" style="max-width:420px;margin:10% auto;padding:36px 32px 30px 32px;position:relative;background:#fff;border-radius:18px;box-shadow:0 8px 40px rgba(0,0,0,0.25),0 1.5px 8px rgba(0,0,0,0.08);border:2.5px solid #ff6b6b;">
                <span class="close" id="closeOptionsModal" style="position:absolute;top:18px;right:28px;font-size:2.2rem;cursor:pointer;color:#ff6b6b;z-index:2;">&times;</span>
                <h2 style="margin-bottom:22px;font-size:2rem;font-weight:800;color:#222;text-align:center;letter-spacing:0.5px;">Customize your <span style='color:#ff6b6b;'><?php echo $product['name']; ?></span></h2>
                <form id="optionsForm">
                    <div id="optionsContainer" style="margin-bottom:22px;font-size:1.15rem;color:#333;"></div>
                    <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
                        <button type="button" class="btn btn-secondary" id="skipOptionsBtn" style="font-size:1rem;padding:10px 22px;">Skip</button>
                        <button type="submit" class="btn btn-primary" style="font-size:1rem;padding:10px 22px;">Add to Cart</button>
                    </div>
                </form>
                <div id="successAnimation" style="display:none;text-align:center;margin-top:24px;">
                    <div style="font-size:54px;color:#28a745;">
                        <i class="fas fa-check-circle fa-bounce"></i>
                    </div>
                    <div style="font-size:20px;color:#28a745;margin-top:12px;">Added to cart!</div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($related_products) > 0): ?>
    <div class="related-products">
        <h2 class="section-title">You May Also Like</h2>
        <div class="products-container">
            <?php foreach ($related_products as $related): ?>
                <?php
                $related_img_file = isset($related['image']) ? trim($related['image']) : '';
                $related_img_path = $related_img_file ? (file_exists($related_img_file) ? $related_img_file : (file_exists('assets/images/' . $related_img_file) ? 'assets/images/' . $related_img_file : (file_exists($related_img_file) ? $related_img_file : ''))) : '';
                $related_img_url = $related_img_path && file_exists($related_img_path) && filesize($related_img_path) > 0
                    ? $related_img_path
                    : (isset($fallback_images[$related['name']]) ? $fallback_images[$related['name']] : 'assets/images/food.jpg');
                ?>
                <div class="product-card">
                    <a href="product.php?id=<?php echo $related['id']; ?>" style="display:block;">
                        <div class="product-img-wrap">
                            <img src="<?php echo $related_img_url; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" class="product-img">
                        </div>
                    </a>
                    <div class="product-content">
                        <div class="product-info-block" style="flex:1 1 auto; width:100%; min-height:90px; display:flex; flex-direction:column; justify-content:flex-start;">
                            <h3><?php echo $related['name']; ?></h3>
                            <p><?php echo substr($related['description'], 0, 80) . (strlen($related['description']) > 80 ? '...' : ''); ?></p>
                            <div class="product-price" style="margin-top:auto;"><?php echo formatPrice($related['price']); ?></div>
                        </div>
                        <div class="product-actions-block" style="width:100%;display:flex;flex-direction:column;gap:8px;margin-top:auto;">
                            <a href="product.php?id=<?php echo $related['id']; ?>" class="btn btn-secondary">View Details</a>
                            <?php if (isLoggedIn()): ?>
                                <button class="btn btn-primary add-to-cart-btn" data-id="<?php echo $related['id']; ?>">Add to Cart</button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login to Order</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="notification-banner" style="display:none;position:fixed;top:32px;right:32px;z-index:9999;min-width:220px;padding:18px 32px;background:#28a745;color:#fff;font-size:1.1rem;font-weight:600;border-radius:12px;box-shadow:0 4px 24px rgba(40,167,69,0.13);transition:opacity 0.3s;opacity:0;">
    <span id="notification-message"></span>
</div>

<style>
.product-details-container {
    margin-bottom: 40px;
}

.product-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    background-color: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 40px;
    padding: 20px;
}

.product-img-large {
    height: 450px;
    background-size: cover;
    background-position: center;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-info {
    padding: 30px;
}

.product-info h1 {
    font-size: 36px;
    margin-bottom: 15px;
    color: var(--dark-color);
}

.product-category {
    color: #666;
    font-style: italic;
    margin-bottom: 20px;
    font-size: 18px;
}

.product-price {
    font-size: 38px;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 25px;
}

.product-description {
    margin-bottom: 35px;
}

.product-description h3 {
    font-size: 24px;
    margin-bottom: 15px;
    color: var(--dark-color);
}

.product-description p {
    font-size: 16px;
    line-height: 1.6;
    color: #444;
}

.product-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 15px;
}

.quantity-input-group {
    display: flex;
    align-items: center;
}

.quantity-btn {
    width: 40px;
    height: 40px;
    background-color: #eee;
    border: none;
    font-size: 18px;
    cursor: pointer;
}

.quantity-input {
    width: 60px;
    height: 40px;
    text-align: center;
    border: 1px solid #ddd;
    font-size: 16px;
}

.btn-lg {
    padding: 15px 30px;
    font-size: 18px;
}

.related-products {
    margin-top: 30px;
    padding: 0 20px;
}

.related-products .section-title {
    font-size: 24px;
    margin-bottom: 15px;
    color: var(--dark-color);
}

.products-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.product-card {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10), 0 1.5px 8px rgba(0,0,0,0.08);
    transition: transform 0.22s cubic-bezier(.4,0,.2,1), box-shadow 0.22s cubic-bezier(.4,0,.2,1);
    max-width: 320px;
    margin: 0 auto;
    border: 1.5px solid #e3e3e3;
}

.product-card:hover {
    transform: translateY(-7px) scale(1.03);
    box-shadow: 0 8px 32px rgba(0,0,0,0.13), 0 2px 12px rgba(0,0,0,0.10);
    border-color: #b6e2c6;
}

.product-img-wrap {
    width: 100%;
    padding-top: 70%; /* 7:10 aspect ratio for a modern look */
    position: relative;
    overflow: hidden;
    background: #f7f7f7;
}

.product-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1);
    border-radius: 0 0 12px 12px;
}

.product-card:hover .product-img {
    transform: scale(1.07);
}

.product-content {
    padding: 18px 16px 14px 16px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-height: 140px;
    height: 100%;
    justify-content: space-between;
}

.product-content .product-info-block {
    width: 100%;
}

.product-content .product-actions-block {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 12px;
}

.product-content .btn {
    padding: 10px 0;
    font-size: 0.97rem;
    width: 100%;
    margin-bottom: 0;
    border-radius: 7px;
    border: 1.5px solid #00754a;
    background: #fff;
    color: #00754a;
    font-weight: 600;
    transition: background 0.18s, color 0.18s;
    box-sizing: border-box;
}

.product-content .btn.btn-primary {
    background: #00754a;
    color: #fff;
    border: 1.5px solid #00754a;
}

.product-content .btn.btn-primary:hover {
    background: #005c3c;
    color: #fff;
}

.product-content .btn.btn-secondary:hover {
    background: #e3f9ed;
    color: #00754a;
}

@media screen and (max-width: 900px) {
    .products-container {
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 16px;
    }
    .product-card {
        max-width: 100%;
    }
}
@media screen and (max-width: 600px) {
    .products-container {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .product-content {
        padding: 12px 8px 10px 8px;
        min-height: 100px;
    }
    .product-content h3 {
        font-size: 1rem;
    }
    .product-content p {
        font-size: 0.92rem;
    }
}

.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.62);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
    backdrop-filter: blur(3.5px);
    -webkit-backdrop-filter: blur(3.5px);
}
.animated-modal {
    animation: modalAppear 0.38s cubic-bezier(.68,-0.55,.27,1.55);
    outline: none;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.modal-accent {
    width: 100%;
    height: 8px;
    background: linear-gradient(90deg,#ff6b6b 0%,#ffb86b 100%);
    border-radius: 18px 18px 0 0;
    position: absolute;
    top: 0; left: 0;
    z-index: 1;
}
@keyframes modalAppear {
    from { opacity: 0; transform: translateY(-60px) scale(0.92); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
#successAnimation .fa-bounce {
    animation: bounce 0.7s;
}
@keyframes bounce {
    0%   { transform: scale(0.7); opacity: 0; }
    60%  { transform: scale(1.2); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
@keyframes shake {
    0% { transform: translateX(0); }
    20% { transform: translateX(-10px); }
    40% { transform: translateX(10px); }
    60% { transform: translateX(-8px); }
    80% { transform: translateX(8px); }
    100% { transform: translateX(0); }
}
.modal-content.shake {
    animation: shake 0.4s;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Debug log helper
    function debugLog(msg, data) {
        if (data !== undefined) {
            console.log('[DEBUG]', msg, data);
        } else {
            console.log('[DEBUG]', msg);
        }
    }

    const quantityInput = document.querySelector('.quantity-input');
    const decrementBtn = document.querySelector('.decrement-btn');
    const incrementBtn = document.querySelector('.increment-btn');
    const addToCartBtn = document.querySelector('.add-to-cart-with-qty');

    debugLog('incrementBtn:', incrementBtn);
    debugLog('decrementBtn:', decrementBtn);
    debugLog('quantityInput:', quantityInput);
    debugLog('addToCartBtn:', addToCartBtn);

    if (quantityInput) {
        quantityInput.value = "1";
    }

    if (incrementBtn) {
        incrementBtn.onclick = function(e) {
            debugLog('Increment button clicked');
            const currentValue = parseInt(quantityInput.value) || 1;
            const newValue = currentValue + 1;
            debugLog('Current value before increment:', currentValue);
            if (newValue <= 99) {
                quantityInput.value = newValue;
                debugLog('Value after increment:', newValue);
            }
        };
    }

    if (decrementBtn) {
        decrementBtn.onclick = function(e) {
            debugLog('Decrement button clicked');
            const currentValue = parseInt(quantityInput.value) || 1;
            const newValue = currentValue - 1;
            debugLog('Current value before decrement:', currentValue);
            if (newValue >= 1) {
                quantityInput.value = newValue;
                debugLog('Value after decrement:', newValue);
            }
        };
    }

    if (quantityInput) {
        quantityInput.oninput = function(e) {
            debugLog('Quantity input changed', this.value);
            this.value = this.value.replace(/[^0-9]/g, '');
            let value = parseInt(this.value) || 1;
            value = Math.max(1, Math.min(99, value));
            this.value = value;
            debugLog('Quantity input sanitized', value);
        };
    }

    if (addToCartBtn) {
        const productId = addToCartBtn.getAttribute('data-id');
        addToCartBtn.onclick = function(e) {
            e.preventDefault();
            const quantity = parseInt(quantityInput.value) || 1;
            // Collect selected options
            const optionCheckboxes = document.querySelectorAll('.option-checkbox:checked');
            const selectedOptions = Array.from(optionCheckboxes).map(cb => cb.value);
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('options', JSON.stringify(selectedOptions));
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    showNotification('Product added to cart!');
                } else {
                    showNotification(data.message || 'Error adding to cart', 'error');
                }
            })
            .catch(error => {
                showNotification('Error adding to cart', 'error');
            });
        };
    }
});

function showNotification(message, type = 'success') {
    const banner = document.getElementById('notification-banner');
    const msg = document.getElementById('notification-message');
    if (!banner || !msg) return;
    msg.textContent = message;
    banner.style.background = (type === 'error') ? '#dc3545' : '#28a745';
    banner.style.opacity = '1';
    banner.style.display = 'block';
    setTimeout(() => {
        banner.style.opacity = '0';
        setTimeout(() => { banner.style.display = 'none'; }, 400);
    }, 2200);
}
</script>

<?php
require_once 'includes/footer.php';
?> 