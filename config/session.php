<?php
// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Check if user is logged in
function isLoggedIn() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
        // Check session timeout
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            // Session expired
            session_unset();
            session_destroy();
            return false;
        }
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

// Check user role
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_string($roles)) {
        $roles = array($roles);
    }
    
    return in_array($_SESSION['user_role'], $roles);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php?error=Please login first");
        exit();
    }
}

// Redirect if not authorized
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        header("Location: dashboard.php?error=Access denied");
        exit();
    }
}

// Get current user info
function getCurrentUser() {
    if (isLoggedIn()) {
        return array(
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['user_role'],
            'email' => $_SESSION['email']
        );
    }
    return null;
}

// Set user session
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['last_activity'] = time();
}

// Destroy session (logout)
function logout() {
    session_unset();
    session_destroy();
    
    // Clear remember me cookie if exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
?>