<?php
// Get base path
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/controller/AuthController.php';

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
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Restaurant Management - Login</title>  <link rel="stylesheet" href="../asset/css/index.css">
</head> <body> <div class="login-container"> <div class="login-logo">R</div> <h1>Restaurant Manager</h1> <p>Please login to continue</p> <?php if ($error): ?> <div class="error-message"><?php echo $error; ?></div> <?php endif; ?> <?php if ($success): ?> <div class="success-message"><?php echo $success; ?></div> <?php endif; ?> <form method="POST" action="" id="loginForm"> <?php echo csrfField(); ?> <div class="form-group"> <label for="username">Username or Email</label> <input type="text" id="username" name="username" placeholder="Enter username or email" required> </div> <div class="form-group"> <label for="password">Password</label> <input type="password" id="password" name="password" placeholder="Enter password" required> </div> <div class="form-links"> <a href="forgot_password.php">Forgot Password?</a> </div> <button type="submit" class="login-btn">Login</button> </form> <div class="signup-link"> Don't have an account? <a href="signup.php">Sign Up</a> </div> </div>  <script src=\"../asset/js/ajax.js\"></script>
<script src="../asset/js/index.js"></script>
</body> </html>