<?php
require_once __DIR__ . '/../model/config/session.php';
require_once __DIR__ . '/../model/config/security.php';
require_once __DIR__ . '/../controller/AuthController.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$formData = array(
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'role' => ''
);

// Handle registration form submission
if (isPost()) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Store form data for repopulation
        $formData = array(
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'full_name' => $_POST['full_name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'role' => $_POST['role'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        );
        
        $auth = new AuthController();
        $result = $auth->register($formData);
        
        if ($result['success']) {
            redirect('index.php', 'Registration successful! Please login.', 'success');
        } else {
            $error = $result['error'];
        }
    }
}
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Sign Up - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/signup.css">
</head> <body> <div class="signup-container"> <div class="signup-logo">R</div> <h1>Create Account</h1> <p>Fill in the details to register</p> <?php if ($error): ?> <div class="error-message"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <form method="POST" action="" id="signupForm"> <?php echo csrfField(); ?> <div class="form-row"> <div class="form-group"> <label for="full_name">Full Name <span>*</span></label> <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($formData['full_name']); ?>" 
                           placeholder="Enter full name" required> <div class="field-error" id="full_name_error"></div> </div> <div class="form-group"> <label for="username">Username <span>*</span></label> <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($formData['username']); ?>" 
                           placeholder="Enter username" required> <div class="field-error" id="username_error"></div> </div> </div> <div class="form-row"> <div class="form-group"> <label for="email">Email <span>*</span></label> <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($formData['email']); ?>" 
                           placeholder="Enter email" required> <div class="field-error" id="email_error"></div> </div> <div class="form-group"> <label for="phone">Phone</label> <input type="text" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($formData['phone']); ?>" 
                           placeholder="Enter phone number"> </div> </div> <div class="form-group"> <label for="role">Role <span>*</span></label> <select id="role" name="role" required> <option value="">-- Select Role --</option> <option value="admin" <?php echo $formData['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option> <option value="manager" <?php echo $formData['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
<option value="cashier">Cashier</option> </select> <div class="field-error" id="role_error"></div> </div> <div class="form-row"> <div class="form-group"> <label for="password">Password <span>*</span></label> <input type="password" id="password" name="password" placeholder="Enter password" required> <div class="password-hint">Minimum 6 characters</div> <div class="field-error" id="password_error"></div> </div> <div class="form-group"> <label for="confirm_password">Confirm Password <span>*</span></label> <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required> <div class="field-error" id="confirm_password_error"></div> </div> </div> <button type="submit" class="signup-btn">Sign Up</button> </form> <div class="login-link"> Already have an account? <a href="index.php">Login</a> </div> </div>  <script src=\"../asset/js/ajax.js\"></script>
<script src="../asset/js/signup.js"></script>
</body> </html>