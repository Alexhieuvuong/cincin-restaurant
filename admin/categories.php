<?php
require_once 'includes/header.php';
require_once '../includes/functions.php';
require_once 'includes/functions.php';

// Handle add/edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $name = clean($_POST['name'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $image = '';
    $errors = [];

    if (empty($name)) {
        $errors['name'] = 'Category name is required.';
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors['image'] = 'Only JPG, JPEG, PNG, and GIF files are allowed.';
        } else {
            $upload_dir = '../uploads/categories/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = 'uploads/categories/' . $new_filename;
            } else {
                $errors['image'] = 'Error uploading image.';
            }
        }
    }

    if (empty($errors)) {
        if ($category_id > 0) {
            // Update
            $query = "UPDATE categories SET name = ?, description = ?";
            $params = [$name, $description];
            $types = "ss";
            if (!empty($image)) {
                $query .= ", image = ?";
                $params[] = $image;
                $types .= "s";
            }
            $query .= " WHERE id = ?";
            $params[] = $category_id;
            $types .= "i";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Category updated successfully.');
                } else {
                    setFlashMessage('danger', 'Error updating category: ' . $stmt->error);
                }
                $stmt->close();
            }
        } else {
            // Add new
            $query = "INSERT INTO categories (name, description, image) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("sss", $name, $description, $image);
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Category added successfully.');
                } else {
                    setFlashMessage('danger', 'Error adding category: ' . $stmt->error);
                }
                $stmt->close();
            }
        }
    } else {
        foreach ($errors as $error) {
            setFlashMessage('danger', $error);
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    $query = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Category deleted successfully.');
        } else {
            setFlashMessage('danger', 'Error deleting category: ' . $stmt->error);
        }
        $stmt->close();
    }
}

// Get all categories
$categories = getAllCategories($conn);

// Get category for editing
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $cat_id = (int)$_GET['edit'];
    foreach ($categories as $cat) {
        if ($cat['id'] == $cat_id) {
            $edit_category = $cat;
            break;
        }
    }
}
?>

<h1>Categories Management</h1>
<?php displayFlashMessage(); ?>

<div class="admin-actions">
    <button class="btn btn-primary" id="addCategoryBtn">
        <i class="fas fa-plus"></i> Add New Category
    </button>
</div>

<!-- Category Form Modal -->
<div class="modal" id="categoryModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form action="" method="POST" enctype="multipart/form-data" id="categoryForm">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?php echo $edit_category ? $edit_category['name'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3" required><?php echo $edit_category ? $edit_category['description'] : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Category Image</label>
                    <?php if ($edit_category && !empty($edit_category['image'])): ?>
                        <div class="current-image">
                            <img src="../<?php echo $edit_category['image']; ?>" alt="Current category image" style="max-width: 200px; margin-bottom: 10px;">
                            <p>Current image</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                    <small class="form-text">Leave empty to keep current image (if editing)</small>
                </div>
                <div class="form-actions">
                    <button type="submit" name="save_category" class="btn btn-primary">Save Category</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Categories Table -->
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="5" class="text-center">No categories found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo $category['id']; ?></td>
                        <td>
                            <?php if (!empty($category['image'])): ?>
                                <img src="../<?php echo $category['image']; ?>" alt="<?php echo $category['name']; ?>" class="product-thumbnail">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $category['name']; ?></td>
                        <td><?php echo $category['description']; ?></td>
                        <td class="actions">
                            <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary edit-btn">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="" method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
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
    const modal = document.getElementById('categoryModal');
    const addCategoryBtn = document.getElementById('addCategoryBtn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelBtn');
    
    // Show modal when Add Category button is clicked
    addCategoryBtn.addEventListener('click', function() {
        document.getElementById('categoryForm').reset();
        const catIdInput = document.querySelector('input[name="category_id"]');
        if (catIdInput) catIdInput.remove();
        modal.style.display = 'block';
    });
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    cancelBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
    <?php if ($edit_category): ?>
    modal.style.display = 'block';
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?> 