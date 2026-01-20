<?php
require_once __DIR__ . '/../model/config/session.php';
require_once __DIR__ . '/../model/config/security.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$token = trim($_GET['token'] ?? '');
if ($token === '') {
    header("Location: index.php?error=Invalid reset link");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    
<link rel="stylesheet" href="../asset/css/reset_password.css">
</head>
<body>
<div class="login-container">
    <div class="login-logo">R</div>
    <h2>Reset Password</h2>

    <form id="resetForm" onsubmit="return submitReset();">
        <?php echo csrfField(); ?>
        <input type="hidden" id="token" value="<?php echo xss_clean($token); ?>">

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" required>
            <small id="passErr" style="color:#dc3545;"></small>
        </div>

        <div class="form-group">
            <label for="cpassword">Confirm Password</label>
            <input type="password" id="cpassword" required>
            <small id="cpassErr" style="color:#dc3545;"></small>
        </div>

        <button type="submit" class="login-btn">Reset Password</button>
    </form>

    <div class="signup-link">
        <a href="index.php">Back to Login</a>
    </div>
</div>


<script src=\"../asset/js/ajax.js\"></script>
<script src="../asset/js/reset_password.js"></script>
</body>
</html>
