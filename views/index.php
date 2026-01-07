<?php
// Get base path
$basePath = dirname(__DIR__);

require_once $basePath . '/config/session.php';
require_once $basePath . '/config/security.php';
require_once $basePath . '/controllers/AuthController.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if (isPost()) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $auth = new AuthController();
        $result = $auth->login($_POST['username'], $_POST['password']);
        
        if ($result['success']) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = $result['error'];
        }
    }
}

// Check for flash messages
$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'success') {
        $success = $flash['message'];
    } else {
        $error = $flash['message'];
    }
}

// Check URL parameters
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management - Login</title>
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
        }

        .login-container {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            width: 420px;
        }

        .login-logo {
            width: 80px;
            height: 80px;
            background-color: #012754;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px auto;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }

        .login-container h1 {
            text-align: center;
            margin-bottom: 8px;
            color: #012754;
            font-size: 24px;
        }

        .login-container > p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f9f9f9;
        }

        .form-group input:focus {
            outline: none;
            border-color: #012754;
            background-color: white;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background-color: #012754;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }

        .login-btn:hover {
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

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #a5d6a7;
        }

        .form-links {
            text-align: right;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .form-links a {
            color: #012754;
            text-decoration: none;
            font-size: 14px;
        }

        .form-links a:hover {
            text-decoration: underline;
        }

        .signup-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }

        .signup-link a {
            color: #012754;
            text-decoration: none;
            font-weight: bold;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">R</div>
        <h1>Restaurant Manager</h1>
        <p>Please login to continue</p>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <?php echo csrfField(); ?>
            
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" placeholder="Enter username or email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>

            <div class="form-links">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>

        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').onsubmit = function(e) {
            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value;
            
            if (username === '') {
                alert('Please enter username or email');
                e.preventDefault();
                return false;
            }
            
            if (password === '') {
                alert('Please enter password');
                e.preventDefault();
                return false;
            }
            
            return true;
        };
    </script>
</body>
</html>