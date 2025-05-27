<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$name = $email = $password = $confirm_password = $address = $phone = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form inputs
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = clean($_POST['address'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    
    // Validate inputs
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email already exists using prepared statement
        $email_check_query = "SELECT * FROM users WHERE email = ?";
        $email_stmt = $conn->prepare($email_check_query);
        $email_stmt->bind_param("s", $email);
        $email_stmt->execute();
        $result = $email_stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $errors['email'] = 'Email already exists. Please use a different email or login';
        }
        
        $email_stmt->close();
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($address)) {
        $errors['address'] = 'Address is required';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    }
    
    // Register user if no errors
    if (empty($errors)) {
        $hashed_password = hashPassword($password);
        
        // Use prepared statement to prevent SQL injection
        $query = "INSERT INTO users (name, email, password, address, phone) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $address, $phone);
            
            if ($stmt->execute()) {
                // Get the newly created user
                $user_id = $conn->insert_id;
                
                // Use prepared statement for select
                $user_query = "SELECT * FROM users WHERE id = ?";
                $user_stmt = $conn->prepare($user_query);
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user = $user_result->fetch_assoc();
                $user_stmt->close();
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Redirect to home page
                setFlashMessage('success', 'Registration successful! Welcome to FoodDelivery.');
                redirect('index.php');
            } else {
                $errors['db'] = 'Registration failed: ' . $stmt->error;
            }
            
            $stmt->close();
        } else {
            $errors['db'] = 'Registration failed: ' . $conn->error;
        }
    }
}
?>

<div class="auth-container">
    <h2>Create an Account</h2>
    
    <?php displayFlashMessage(); ?>
    
    <?php if (!empty($errors['db'])): ?>
        <div class="alert alert-danger"><?php echo $errors['db']; ?></div>
    <?php endif; ?>
    
    <form action="" method="POST" novalidate>
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" name="name" id="name" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>" required>
            <?php if (isset($errors['name'])): ?>
                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required>
            <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" required>
            <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" required>
            <?php if (isset($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="address">Delivery Address</label>
            <textarea name="address" id="address" rows="3" class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" required><?php echo $address; ?></textarea>
            <?php if (isset($errors['address'])): ?>
                <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" name="phone" id="phone" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" value="<?php echo $phone; ?>" required>
            <?php if (isset($errors['phone'])): ?>
                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </div>
        
        <p class="text-center">Already have an account? <a href="login.php">Login here</a></p>
    </form>
</div>

<style>
.auth-container {
    max-width: 500px;
    margin: 0 auto;
    background-color: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.auth-container h2 {
    margin-bottom: 30px;
    text-align: center;
    color: var(--dark-color);
}

.form-group {
    margin-bottom: 20px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.form-control.is-invalid {
    border-color: var(--danger-color);
}

.invalid-feedback {
    color: var(--danger-color);
    font-size: 14px;
    margin-top: 5px;
}

.btn-block {
    display: block;
    width: 100%;
}

.text-center {
    text-align: center;
    margin-top: 20px;
}
</style>

<?php
require_once 'includes/footer.php';
?> 