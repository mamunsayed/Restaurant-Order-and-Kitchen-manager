<?php
// AJAX controller for Staff operations

$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/config/validation.php';
require_once $basePath . '/model/Staff.php';

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($ok, $msg, $extra = []) {
    echo json_encode(array_merge([
        'success' => $ok ? true : false,
        'message' => $msg,
    ], $extra));
    exit;
}

function requireAjaxLogin() {
    requireLogin();
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'Not logged in');
    }
}

function requireAjaxRole($roles) {
    requireRole($roles);
}

function readPayload($key) {
    if (!isset($_REQUEST[$key])) {
        jsonResponse(false, 'Invalid request');
    }
    $data = json_decode($_REQUEST[$key], true);
    if (!is_array($data)) {
        jsonResponse(false, 'Invalid JSON');
    }
    if (!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
        jsonResponse(false, 'Invalid request. Please try again.');
    }
    return $data;
}

// Create staff
if (isset($_REQUEST['staffCreate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('staffCreate');

    $full_name = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $position = trim($data['position'] ?? '');
    $salary = floatval($data['salary'] ?? 0);
    $hire_date = trim($data['hire_date'] ?? '');
    $address = trim($data['address'] ?? '');
    $status = $data['status'] ?? 'active';

    if ($full_name === '') jsonResponse(false, 'Name required');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(false, 'Valid email required');

    $m = new Staff();
    $res = $m->create([
        'name' => $full_name,
        'email' => $email,
        'phone' => $phone,
        'position' => $position,
        'salary' => $salary,
        'hire_date' => $hire_date,
        'address' => $address,
        'status' => $status,
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Update staff
if (isset($_REQUEST['staffUpdate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('staffUpdate');

    $id = intval($data['id'] ?? 0);
    $full_name = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $position = trim($data['position'] ?? '');
    $salary = floatval($data['salary'] ?? 0);
    $hire_date = trim($data['hire_date'] ?? '');
    $address = trim($data['address'] ?? '');
    $status = $data['status'] ?? 'active';

    if ($id <= 0) jsonResponse(false, 'Invalid staff');
    if ($full_name === '') jsonResponse(false, 'Name required');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(false, 'Valid email required');

    $m = new Staff();
    $res = $m->update($id, [
        'name' => $full_name,
        'email' => $email,
        'phone' => $phone,
        'position' => $position,
        'salary' => $salary,
        'hire_date' => $hire_date,
        'address' => $address,
        'status' => $status,
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Delete staff
if (isset($_REQUEST['staffDelete'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('staffDelete');
    $id = intval($data['id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid staff');

    $m = new Staff();
    $res = $m->delete($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Deleted'));
}

jsonResponse(false, 'No action');