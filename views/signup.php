<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../controllers/AuthController.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Restaurant Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 0;
        }

        .signup-container {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            width: 480px;
        }

        .signup-logo {
            width: 70px;
            height: 70px;
            background-color: #012754;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px auto;
            color: white;
            font-size: 28px;
            font-weight: bold;
        }

        .signup-container h1 {
            text-align: center;
            margin-bottom: 8px;
            color: #012754;
            font-size: 24px;
        }

        .signup-container > p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group label span {
            color: #d32f2f;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f9f9f9;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #012754;
            background-color: white;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .signup-btn {
            width: 100%;
            padding: 14px;
            background-color: #012754;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }

        .signup-btn:hover {
            background-color: #011c3d;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #ffcdd2;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #012754;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .field-error {
            color: #d32f2f;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-logo">R</div>
        <h1>Create Account</h1>
        <p>Fill in the details to register</p>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="signupForm">
            <?php echo csrfField(); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name <span>*</span></label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($formData['full_name']); ?>" 
                           placeholder="Enter full name" required>
                    <div class="field-error" id="full_name_error"></div>
                </div>

                <div class="form-group">
                    <label for="username">Username <span>*</span></label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($formData['username']); ?>" 
                           placeholder="Enter username" required>
                    <div class="field-error" id="username_error"></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email <span>*</span></label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($formData['email']); ?>" 
                           placeholder="Enter email" required>
                    <div class="field-error" id="email_error"></div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($formData['phone']); ?>" 
                           placeholder="Enter phone number">
                </div>
            </div>
            
            <div class="form-group">
                <label for="role">Role <span>*</span></label>
                <select id="role" name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin" <?php echo $formData['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="manager" <?php echo $formData['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                    <option value="server" <?php echo $formData['role'] === 'server' ? 'selected' : ''; ?>>Server</option>
                    <option value="kitchen" <?php echo $formData['role'] === 'kitchen' ? 'selected' : ''; ?>>Kitchen</option>
                </select>
                <div class="field-error" id="role_error"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span>*</span></label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                    <div class="password-hint">Minimum 6 characters</div>
                    <div class="field-error" id="password_error"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span>*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                    <div class="field-error" id="confirm_password_error"></div>
                </div>
            </div>
            
            <button type="submit" class="signup-btn">Sign Up</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="index.php">Login</a>
        </div>
    </div>

    <script>
        // Client-side validation
        document.getElementById('signupForm').onsubmit = function(e) {
            var isValid = true;
            
            // Reset errors
            var errors = document.querySelectorAll('.field-error');
            for (var i = 0; i < errors.length; i++) {
                errors[i].style.display = 'none';
            }
            
            // Full name validation
            var fullName = document.getElementById('full_name').value.trim();
            if (fullName === '') {
                showError('full_name_error', 'Full name is required');
                isValid = false;
            } else if (fullName.length < 2) {
                showError('full_name_error', 'Full name must be at least 2 characters');
                isValid = false;
            }
            
            // Username validation
            var username = document.getElementById('username').value.trim();
            if (username === '') {
                showError('username_error', 'Username is required');
                isValid = false;
            } else if (username.length < 3) {
                showError('username_error', 'Username must be at least 3 characters');
                isValid = false;
            } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                showError('username_error', 'Username can only contain letters, numbers and underscore');
                isValid = false;
            }
            
            // Email validation
            var email = document.getElementById('email').value.trim();
            if (email === '') {
                showError('email_error', 'Email is required');
                isValid = false;
            } else if (!isValidEmail(email)) {
                showError('email_error', 'Please enter a valid email');
                isValid = false;
            }
            
            // Role validation
            var role = document.getElementById('role').value;
            if (role === '') {
                showError('role_error', 'Please select a role');
                isValid = false;
            }
            
            // Password validation
            var password = document.getElementById('password').value;
            if (password === '') {
                showError('password_error', 'Password is required');
                isValid = false;
            } else if (password.length < 6) {
                showError('password_error', 'Password must be at least 6 characters');
                isValid = false;
            }
            
            // Confirm password validation
            var confirmPassword = document.getElementById('confirm_password').value;
            if (confirmPassword === '') {
                showError('confirm_password_error', 'Please confirm your password');
                isValid = false;
            } else if (password !== confirmPassword) {
                showError('confirm_password_error', 'Passwords do not match');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            return true;
        };
        
        function showError(id, message) {
            var errorEl = document.getElementById(id);
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
        
        function isValidEmail(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    </script>
</body>
</html>