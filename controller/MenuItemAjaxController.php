<?php
// AJAX controller for MenuItem operations
// Handles create, update, delete actions via JSON payload

$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/config/validation.php';
require_once $basePath . '/model/MenuItem.php';

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

// Create menu item
if (isset($_REQUEST['menuCreate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('menuCreate');

    $name = trim($data['name'] ?? '');
    $category_id = intval($data['category_id'] ?? 0);
    $price = floatval($data['price'] ?? 0);
    $description = trim($data['description'] ?? '');
    $status = $data['status'] ?? 'available';

    if ($name === '') jsonResponse(false, 'Item name is required');
    if ($category_id <= 0) jsonResponse(false, 'Category is required');
    if ($price <= 0) jsonResponse(false, 'Valid price required');

    $m = new MenuItem();
    $res = $m->create([
        'name'=>$name,
        'category_id'=>$category_id,
        'price'=>$price,
        'description'=>$description,
        'status'=>$status
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Update menu item
if (isset($_REQUEST['menuUpdate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('menuUpdate');

    $id = intval($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $category_id = intval($data['category_id'] ?? 0);
    $price = floatval($data['price'] ?? 0);
    $description = trim($data['description'] ?? '');
    $status = $data['status'] ?? 'available';

    if ($id <= 0) jsonResponse(false, 'Invalid item');
    if ($name === '') jsonResponse(false, 'Item name is required');
    if ($category_id <= 0) jsonResponse(false, 'Category is required');
    if ($price <= 0) jsonResponse(false, 'Valid price required');

    $m = new MenuItem();
    $res = $m->update($id, [
        'name'=>$name,
        'category_id'=>$category_id,
        'price'=>$price,
        'description'=>$description,
        'status'=>$status
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Delete menu item
if (isset($_REQUEST['menuDelete'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('menuDelete');

    $id = intval($data['id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid item');

    $m = new MenuItem();
    $res = $m->delete($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Deleted'));
}

jsonResponse(false, 'No action');