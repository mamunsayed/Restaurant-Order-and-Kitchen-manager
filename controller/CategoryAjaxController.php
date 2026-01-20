<?php
// AJAX controller for Category operations
// Handles create, update, delete actions via JSON payload

$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/config/validation.php';
require_once $basePath . '/model/Category.php';

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($ok, $msg, $extra = []) {
    echo json_encode(array_merge([
        'success' => $ok ? true : false,
        'message' => $msg,
    ], $extra));
    exit;
}

// Ensure user is logged in for AJAX request
function requireAjaxLogin() {
    requireLogin();
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'Not logged in');
    }
}

// Ensure user has one of the required roles
function requireAjaxRole($roles) {
    requireRole($roles);
}

// Read and validate JSON payload from a specific POST key
function readPayload($key) {
    if (!isset($_REQUEST[$key])) {
        jsonResponse(false, 'Invalid request');
    }
    $data = json_decode($_REQUEST[$key], true);
    if (!is_array($data)) {
        jsonResponse(false, 'Invalid JSON');
    }
    // CSRF token validation
    if (!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
        jsonResponse(false, 'Invalid request. Please try again.');
    }
    return $data;
}

// Handle create category
if (isset($_REQUEST['categoryCreate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('categoryCreate');

    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $status = $data['status'] ?? 'active';

    if ($name === '') jsonResponse(false, 'Category name is required');

    $m = new Category();
    $res = $m->create(['name'=>$name,'description'=>$description,'status'=>$status]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Handle update category
if (isset($_REQUEST['categoryUpdate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('categoryUpdate');

    $id = intval($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $status = $data['status'] ?? 'active';

    if ($id <= 0) jsonResponse(false, 'Invalid category');
    if ($name === '') jsonResponse(false, 'Category name is required');

    $m = new Category();
    $res = $m->update($id, ['name'=>$name,'description'=>$description,'status'=>$status]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Handle delete category
if (isset($_REQUEST['categoryDelete'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('categoryDelete');

    $id = intval($data['id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid category');

    $m = new Category();
    $res = $m->delete($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Deleted'));
}

// Default response if no action matched
jsonResponse(false, 'No action');