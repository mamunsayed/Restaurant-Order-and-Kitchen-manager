<?php
session_start();

$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/database.php';
require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/validation.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/controller/AuthController.php';

header('Content-Type: application/json');

function jsonOut($arr){
    echo json_encode($arr);
    exit;
}

// CSRF validate (for POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf)) {
        jsonOut(['success'=>false,'message'=>'Invalid CSRF token']);
    }
}

$action = $_POST['action'] ?? '';

$auth = new AuthController();

if ($action === 'request') {
    $email = trim($_POST['email'] ?? '');
    $res = $auth->requestPasswordReset($email);

    // In local environment, return reset link if generated
    if ($res['success'] && isset($res['token'])) {
        $token = $res['token'];
        $res['reset_link'] = '../view/reset_password.php?token=' . urlencode($token);
        unset($res['token']); // remove raw token from response body? keep via reset_link
    }
    jsonOut($res);
}

if ($action === 'reset') {
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';
    $res = $auth->resetPasswordWithToken($token, $password, $cpassword);
    jsonOut($res);
}

jsonOut(['success'=>false,'message'=>'Invalid request']);
