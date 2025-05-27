<?php
require_once 'includes/header.php';
require_once '../includes/functions.php';
require_once 'includes/functions.php';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle product deletion
    if (isset($_POST['delete_product'])) {
        $product_id = (int)$_POST['product_id'];
        
        // Delete the product
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Product deleted successfully');
                debug_log("Product deleted: ID=$product_id");
            } else {
                setFlashMessage('danger', 'Error deleting product: ' . $stmt->error);
                debug_log("Error deleting product: " . $stmt->error);
            }
            
            $stmt->close();
        }
    }
    
    // Handle product addition/update
    if (isset($_POST['save_product'])) {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $name = clean($_POST['name'] ?? '');
        $description = clean($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category_id = (int)($_POST['category_id'] ?? 0);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Validate inputs
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Product name is required';
        }
        
        if (empty($description)) {
            $errors['description'] = 'Product description is required';
        }
        
        if ($price <= 0) {
            $errors['price'] = 'Price must be greater than zero';
        }
        
        if ($category_id <= 0) {
            $errors['category_id'] = 'Please select a category';
        }
        
        // Handle image upload
        $image = '';
        if (isset($_POST['image_select']) && !empty($_POST['image_select'])) {
            // Use selected image from assets/images
            $image = 'assets/images/' . $_POST['image_select'];
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                $errors['image'] = 'Only JPG, JPEG, PNG, and GIF files are allowed';
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = __DIR__ . '/../uploads/products/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        $errors['image'] = 'Failed to create upload directory: ' . error_get_last()['message'];
                    }
                }
                
                // Generate unique filename
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image = 'uploads/products/' . $new_filename;
                } else {
                    $errors['image'] = 'Error uploading image: ' . error_get_last()['message'];
                }
            }
        } elseif ($product_id > 0 && isset($edit_product) && !empty($edit_product['image'])) {
            // Keep existing image when editing
            $image = $edit_product['image'];
        }
        
        // Save product if no errors
        if (empty($errors)) {
            if ($product_id > 0) {
                // Update existing product
                $query = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, is_available = ?";
                $params = [$name, $description, $price, $category_id, $is_available];
                $types = "ssdii";
                
                // Add image to update if new one was uploaded
                if (!empty($image)) {
                    $query .= ", image = ?";
                    $params[] = $image;
                    $types .= "s";
                }
                
                $query .= " WHERE id = ?";
                $params[] = $product_id;
                $types .= "i";
                
                $stmt = $conn->prepare($query);
                
                if ($stmt) {
                    $stmt->bind_param($types, ...$params);
                    
                    if ($stmt->execute()) {
                        setFlashMessage('success', 'Product updated successfully');
                        debug_log("Product updated: ID=$product_id");
                    } else {
                        setFlashMessage('danger', 'Error updating product: ' . $stmt->error);
                        debug_log("Error updating product: " . $stmt->error);
                    }
                    
                    $stmt->close();
                }
            } else {
                // Add new product
                $query = "INSERT INTO products (name, description, price, category_id, is_available, image) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                
                if ($stmt) {
                    $stmt->bind_param("ssdiis", $name, $description, $price, $category_id, $is_available, $image);
                    
                    if ($stmt->execute()) {
                        setFlashMessage('success', 'Product added successfully');
                        debug_log("Product added: ID=" . $conn->insert_id);
                    } else {
                        setFlashMessage('danger', 'Error adding product: ' . $stmt->error);
                        debug_log("Error adding product: " . $stmt->error);
                    }
                    
                    $stmt->close();
                }
            }
        } else {
            // Display validation errors
            foreach ($errors as $error) {
                setFlashMessage('danger', $error);
            }
        }
    }
}

// Get product for editing if ID is provided
$edit_product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $product_id = (int)$_GET['edit'];
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $edit_product = $result->fetch_assoc();
        }
        
        $stmt->close();
    }
}

// Get all categories for dropdown
$categories = getAllCategories($conn);

// Get all products
$query = "SELECT p.*, c.name as category_name 
          FROM products p
          JOIN categories c ON p.category_id = c.id
          ORDER BY p.name";
$result = $conn->query($query);
$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<h1>Products Management</h1>

<?php displayFlashMessage(); ?>

<div class="admin-actions">
    <button class="btn btn-primary" id="addProductBtn">
        <i class="fas fa-plus"></i> Add New Product
    </button>
</div>

<!-- Product Form Modal -->
<div class="modal" id="productModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form action="" method="POST" enctype="multipart/form-data" id="productForm">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?php echo $edit_product ? $edit_product['name'] : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3" required><?php echo $edit_product ? $edit_product['description'] : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price ($)</label>
                    <input type="number" name="price" id="price" class="form-control" step="0.01" min="0.01" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select name="category_id" id="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($edit_product && $edit_product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <?php if ($edit_product && !empty($edit_product['image'])): ?>
                        <div class="current-image">
                            <img src="../<?php echo $edit_product['image']; ?>" alt="Current product image" style="max-width: 200px; margin-bottom: 10px;">
                            <p>Current image</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Image Selection Dropdown -->
                    <select name="image_select" id="image_select" class="form-control" style="margin-bottom: 10px;">
                        <option value="">Select an image from assets</option>
                        <?php
                        $assets_dir = __DIR__ . '/../assets/images/';
                        if (is_dir($assets_dir)) {
                            $files = scandir($assets_dir);
                            foreach ($files as $file) {
                                if ($file != '.' && $file != '..' && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                                    $selected = ($edit_product && $edit_product['image'] == 'assets/images/' . $file) ? 'selected' : '';
                                    echo "<option value=\"$file\" $selected>$file</option>";
                                }
                            }
                        }
                        ?>
                    </select>
                    
                    <p style="margin: 10px 0;">OR</p>
                    
                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                    <small class="form-text">Leave empty to keep current image (if editing)</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_available" value="1" <?php echo ($edit_product && $edit_product['is_available']) ? 'checked' : ''; ?>>
                        Available for purchase
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="save_product" class="btn btn-primary">Save Product</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="7" class="text-center">No products found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <?php if (!empty($product['image'])): ?>
                                <img src="../<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-thumbnail">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['category_name']; ?></td>
                        <td><?php echo formatPrice($product['price']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $product['is_available'] ? 'available' : 'unavailable'; ?>">
                                <?php echo $product['is_available'] ? 'Available' : 'Unavailable'; ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary edit-btn">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="" method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="delete_product" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.admin-actions {
    margin-bottom: 20px;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 80%;
    max-width: 700px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    animation: modalFadeIn 0.3s;
}

@keyframes modalFadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #333;
}

.close {
    font-size: 1.8rem;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}

.close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.form-control:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(78, 115, 223, 0.25);
}

.form-text {
    font-size: 0.85rem;
    color: #777;
    margin-top: 5px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.checkbox-label input {
    margin-right: 8px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.current-image {
    margin-bottom: 15px;
    text-align: center;
}

.current-image img {
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.product-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}

.no-image {
    width: 50px;
    height: 50px;
    background-color: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    font-size: 0.7rem;
    color: #999;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.available {
    background-color: #e8f5e9;
    color: #388e3c;
}

.status-badge.unavailable {
    background-color: #ffebee;
    color: #d32f2f;
}

.actions {
    display: flex;
    gap: 5px;
}

.delete-form {
    display: inline;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const modal = document.getElementById('productModal');
    const addProductBtn = document.getElementById('addProductBtn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelBtn');
    
    // Show modal when Add Product button is clicked
    addProductBtn.addEventListener('click', function() {
        // Reset form
        document.getElementById('productForm').reset();
        // Remove any product_id hidden input (for new products)
        const productIdInput = document.querySelector('input[name="product_id"]');
        if (productIdInput) productIdInput.remove();
        // Show modal
        modal.style.display = 'block';
    });
    
    // Close modal when X is clicked
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when Cancel button is clicked
    cancelBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
    
    // If we're editing a product, show the modal automatically
    <?php if ($edit_product): ?>
    modal.style.display = 'block';
    <?php endif; ?>
});
</script>

<?php
require_once 'includes/footer.php';
?> 