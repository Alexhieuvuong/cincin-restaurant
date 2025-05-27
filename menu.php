<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get categories for filter
$categories = getAllCategories($conn);

// Handle search
$search_term = isset($_GET['search']) ? clean($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Get products based on filters
if (!empty($search_term)) {
    $products = searchProducts($conn, $search_term);
    $title = "Search Results for: " . $search_term;
} elseif ($category_id > 0) {
    $products = getProductsByCategory($conn, $category_id);
    // Get category name
    $category_query = "SELECT name FROM categories WHERE id = $category_id";
    $category_result = $conn->query($category_query);
    $category_name = ($category_result && $category_result->num_rows > 0) ? $category_result->fetch_assoc()['name'] : 'Category';
    $title = $category_name;
} else {
    $products = getAllProducts($conn);
    $title = "IL NOSTRO MENU";
}
?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 1rem;
    }

    .menu-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .menu-header {
        text-align: center;
        margin-bottom: 4rem;
    }

    .menu-title {
        font-size: clamp(2.5rem, 5vw, 4rem);
        font-weight: 900;
        color: #2c5530;
        letter-spacing: 0.1em;
        margin-bottom: 1rem;
        position: relative;
        text-transform: uppercase;
        background: linear-gradient(90deg, #2c5530 60%, #FFD600 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .menu-title::after {
        content: '';
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, #f4c430, #e6b800);
        position: absolute;
        bottom: -0.5rem;
        left: 50%;
        transform: translateX(-50%);
        border-radius: 2px;
    }

    .menu-subtitle {
        font-size: 1.2rem;
        color: #6c757d;
        font-weight: 400;
        margin-top: 1.5rem;
    }

    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .menu-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.2);
        position: relative;
    }

    .menu-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .card-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .menu-card:hover .card-image {
        transform: scale(1.05);
    }

    .card-content {
        padding: 2rem;
        text-align: center;
    }

    .card-icon {
        width: 50px;
        height: 50px;
        margin: 0 auto 1.5rem;
        background: linear-gradient(135deg, #2c5530, #4a8c5a);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .card-title {
        font-size: 1.8rem;
        font-weight: 600;
        color: #2c5530;
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .card-description {
        color: #6c757d;
        line-height: 1.6;
        font-size: 1rem;
    }

    .card-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(44, 85, 48, 0.9), rgba(74, 140, 90, 0.9));
        opacity: 0;
        transition: opacity 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 500;
        cursor: pointer;
    }

    .menu-card:hover .card-overlay {
        opacity: 1;
    }

    @media (max-width: 768px) {
        .menu-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        body {
            padding: 1rem;
        }
    }

    .floating-element {
        position: absolute;
        width: 20px;
        height: 20px;
        background: rgba(244, 196, 48, 0.3);
        border-radius: 50%;
        animation: float 6s ease-in-out infinite;
    }

    .floating-element:nth-child(1) {
        top: 10%;
        left: 10%;
        animation-delay: 0s;
    }

    .floating-element:nth-child(2) {
        top: 20%;
        right: 15%;
        animation-delay: 2s;
    }

    .floating-element:nth-child(3) {
        bottom: 30%;
        left: 20%;
        animation-delay: 4s;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }
    
    /* Search form styles */
    .search-container {
        max-width: 600px;
        margin: 1rem auto 3rem;
    }

    .search-form {
        display: flex;
        background: white;
        border-radius: 50px;
        overflow: hidden;
        padding: 5px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .search-form input {
        flex: 1;
        border: none;
        padding: 12px 20px;
        font-size: 16px;
        outline: none;
        background: transparent;
    }

    .search-form button {
        background: linear-gradient(135deg, #2c5530, #4a8c5a);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .search-form button:hover {
        background: linear-gradient(135deg, #3a6a40, #5a9c6a);
        transform: translateY(-2px);
    }
    
    /* Category sidebar adjustments */
    .category-sidebar {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .category-sidebar h3 {
        color: #2c5530;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 0.5rem;
    }
    
    .category-sidebar h3:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background: linear-gradient(90deg, #f4c430, #e6b800);
        border-radius: 2px;
    }
    
    .category-list {
        list-style: none;
    }
    
    .category-list li {
        margin-bottom: 0.75rem;
    }
    
    .category-list a {
        display: block;
        padding: 10px 15px;
        color: #444;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .category-list a:hover,
    .category-list a.active {
        background: linear-gradient(45deg, rgba(44, 85, 48, 0.9), rgba(74, 140, 90, 0.9));
        color: white;
        transform: translateX(5px);
    }
    
    /* Main content container */
    .main-container {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    @media (max-width: 768px) {
        .main-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="floating-element"></div>
<div class="floating-element"></div>
<div class="floating-element"></div>

<div class="menu-container">
    <div class="menu-header">
        <h1 class="menu-title"><?php echo $title; ?></h1>
        <p class="menu-subtitle">Scopri le nostre specialità</p>
        
        <!-- Search Form -->
        <div class="search-container">
            <form action="menu.php" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search for food..." value="<?php echo $search_term; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <div class="main-container">
        <!-- Category Sidebar -->
        <div class="category-sidebar">
            <h3>Categories</h3>
            <ul class="category-list">
                <li><a href="menu.php" <?php echo (!$category_id && empty($search_term)) ? 'class="active"' : ''; ?>>All Categories</a></li>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="menu.php?category=<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'class="active"' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Menu Grid -->
        <?php if (!empty($search_term) || $category_id > 0): ?>
            <!-- Products Grid -->
            <div class="menu-grid">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
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
                            $img_file = isset($product['image']) ? trim($product['image']) : '';
                            if ($img_file) {
                                if (strpos($img_file, 'assets/images/') === 0 || strpos($img_file, 'uploads/products/') === 0) {
                                    $img_path = $img_file;
                                } else {
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
                        ?>
                        <div class="menu-card">
                            <img src="<?php echo $img_url; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="card-image">
                            <div class="card-content">
                                <div class="card-icon">🍽️</div>
                                <h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="card-description"><?php echo substr($product['description'], 0, 80) . (strlen($product['description']) > 80 ? '...' : ''); ?></p>
                            </div>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="card-overlay">
                                <span>View Details →</span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products" style="grid-column: 1/-1; text-align: center; padding: 50px 20px;">
                        <h2>No products found</h2>
                        <?php if (!empty($search_term)): ?>
                            <p>No results for '<?php echo $search_term; ?>'. Try a different search term.</p>
                        <?php elseif ($category_id > 0): ?>
                            <p>No products available in this category right now.</p>
                        <?php else: ?>
                            <p>No products available right now.</p>
                        <?php endif; ?>
                        <a href="menu.php" class="btn" style="display: inline-block; margin-top: 20px; padding: 12px 30px; background: linear-gradient(135deg, #2c5530, #4a8c5a); color: white; text-decoration: none; border-radius: 30px;">View All Menu</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Default Category Cards -->
            <div class="menu-grid">
                <div class="menu-card">
                    <img src="assets/images/bevande.jpg" alt="Bevande" class="card-image">
                    <div class="card-content">
                        <div class="card-icon">☕</div>
                        <h3 class="card-title"><a href="menu.php?category=6" style="color:inherit;text-decoration:none;">Bevande</a></h3>
                        <p class="card-description">Caffè, tè, drink e molto altro per ogni momento della giornata.</p>
                    </div>
                    <a href="menu.php?category=6" class="card-overlay" style="opacity: 0;">
                        <span>Esplora le bevande →</span>
                    </a>
                </div>

                <div class="menu-card">
                    <img src="assets/images/food.jpg" alt="Food" class="card-image">
                    <div class="card-content">
                        <div class="card-icon">🍽️</div>
                        <h3 class="card-title">Food</h3>
                        <p class="card-description">Piatti caldi, panini, insalate e delizie per tutti i gusti.</p>
                    </div>
                    <a href="menu.php?category=1" class="card-overlay">
                        <span>Scopri i piatti →</span>
                    </a>
                </div>

                <div class="menu-card">
                    <img src="assets/images/dessert.jpg" alt="Dessert" class="card-image">
                    <div class="card-content">
                        <div class="card-icon">🍰</div>
                        <h3 class="card-title"><a href="menu.php?category=5" style="color:inherit;text-decoration:none;">Dessert</a></h3>
                        <p class="card-description">Dolci, torte e dessert per concludere in dolcezza.</p>
                    </div>
                    <a href="menu.php?category=5" class="card-overlay" style="opacity: 0;">
                        <span>Vedi i dessert →</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for add-to-cart buttons if they exist
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    if (addToCartBtns.length > 0) {
        addToCartBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                addToCart(productId, 1);
            });
        });
    }
    
    // Animation for card overlays
    const menuCards = document.querySelectorAll('.menu-card');
    menuCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.querySelector('.card-overlay').style.opacity = '1';
        });
        
        card.addEventListener('mouseleave', function() {
            this.querySelector('.card-overlay').style.opacity = '0';
        });
    });
});

// Add to cart function
function addToCart(productId, quantity) {
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = data.cart_count;
                cartCountElement.style.display = 'inline-block';
            }
            
            // Show success message
            alert('Product added to cart!');
        } else {
            alert(data.message || 'Error adding to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding to cart');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?> 