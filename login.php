<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$email = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form inputs
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // Attempt login if no errors
    if (empty($errors)) {
        // Use prepared statement to prevent SQL injection
        $query = "SELECT * FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if (verifyPassword($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    // Log successful login
                    debug_log("User logged in successfully: ID={$user['id']}, Email={$user['email']}, Admin={$user['is_admin']}");
                    
                    // Redirect to appropriate page
                    if ($user['is_admin']) {
                        redirect('admin/index.php');
                    } else {
                        // Check if there was a previous page
                        if (isset($_SESSION['redirect_after_login'])) {
                            $redirect = $_SESSION['redirect_after_login'];
                            unset($_SESSION['redirect_after_login']);
                            redirect($redirect);
                        } else {
                            redirect('index.php');
                        }
                    }
                } else {
                    $errors['login'] = 'Invalid email or password';
                    debug_log("Failed login attempt - password mismatch for email: {$email}");
                }
            } else {
                $errors['login'] = 'Invalid email or password';
                debug_log("Failed login attempt - email not found: {$email}");
            }
            
            $stmt->close();
        } else {
            $errors['login'] = 'Login failed: ' . $conn->error;
            debug_log("Login preparation failed: " . $conn->error);
        }
    }
}
?>

<div class="auth-container">
    <h2>Login to Your Account</h2>
    
    <?php displayFlashMessage(); ?>
    
    <?php if (!empty($errors['login'])): ?>
        <div class="alert alert-danger"><?php echo $errors['login']; ?></div>
    <?php endif; ?>
    
    <form action="" method="POST" novalidate>
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
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </div>
        
        <p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
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