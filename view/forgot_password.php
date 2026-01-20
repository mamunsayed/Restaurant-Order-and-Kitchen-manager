<?php
require_once __DIR__ . '/../model/config/session.php';
require_once __DIR__ . '/../model/config/security.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'success') $success = $flash['message'];
    else $error = $flash['message'];
}

if (isset($_GET['error'])) $error = xss_clean($_GET['error']);
if (isset($_GET['success'])) $success = xss_clean($_GET['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    
<link rel="stylesheet" href="../asset/css/forgot_password.css">
</head>
<body>
<div class="login-container">
    <div class="login-logo">R</div>
    <h2>Forgot Password</h2>

    <?php if ($error): ?>
        <div class="error-message"><?php echo xss_clean($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message"><?php echo xss_clean($success); ?></div>
    <?php endif; ?>

    <form id="forgotForm" onsubmit="return submitForgot();">
        <?php echo csrfField(); ?>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" required>
            <small id="emailErr" style="color:#dc3545;"></small>
        </div>
        <button type="submit" class="login-btn">Send Reset Link</button>
    </form>

    <div class="signup-link">
        <a href="index.php">Back to Login</a>
    </div>
</div>


<script src=\"../asset/js/ajax.js\"></script>
<script src="../asset/js/forgot_password.js"></script>
</body>
</html>
