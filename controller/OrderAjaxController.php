<?php
// AJAX controller for Order operations
// Handles order item modifications, status updates and kitchen interactions.

$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/config/validation.php';
require_once $basePath . '/model/Order.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Output a JSON response and exit.
 *
 * @param bool   $ok
 * @param string $msg
 * @param array  $extra
 */
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

/**
 * Parse a JSON payload from a given request key and verify CSRF token.
 *
 * @param string $key
 * @return array
 */
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

// -------------------- ORDER ACTIONS --------------------

// Add an item to an existing order
if (isset($_REQUEST['orderAddItem'])) {
    requireAjaxLogin();
    // admin, manager and cashier may add items
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('orderAddItem');

    $order_id = intval($data['order_id'] ?? 0);
    $menu_item_id = intval($data['menu_item_id'] ?? 0);
    $quantity = intval($data['quantity'] ?? 1);

    if ($order_id <= 0) {
        jsonResponse(false, 'Invalid order');
    }
    if ($menu_item_id <= 0) {
        jsonResponse(false, 'Invalid item');
    }
    if ($quantity <= 0) {
        $quantity = 1;
    }

    $model = new Order();
    $res = $model->addItem($order_id, $menu_item_id, $quantity);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Added'));
}

// Update quantity of an order item
if (isset($_REQUEST['orderUpdateQuantity'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('orderUpdateQuantity');

    $item_id = intval($data['item_id'] ?? 0);
    $quantity = intval($data['quantity'] ?? 1);

    if ($item_id <= 0) {
        jsonResponse(false, 'Invalid item');
    }
    if ($quantity <= 0) {
        $quantity = 1;
    }

    $model = new Order();
    $res = $model->updateItemQuantity($item_id, $quantity);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Updated'));
}

// Remove an item from an order
if (isset($_REQUEST['orderRemoveItem'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('orderRemoveItem');

    $item_id = intval($data['item_id'] ?? 0);
    if ($item_id <= 0) {
        jsonResponse(false, 'Invalid item');
    }

    $model = new Order();
    $res = $model->removeItem($item_id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Removed'));
}

// Update overall order status (e.g., completed, cancelled, served)
if (isset($_REQUEST['orderUpdateStatus'])) {
    requireAjaxLogin();
    // Only admin or manager may update order status
    requireAjaxRole(['admin','manager']);
    $data = readPayload('orderUpdateStatus');

    $id = intval($data['id'] ?? 0);
    $status = trim($data['status'] ?? '');

    if ($id <= 0) {
        jsonResponse(false, 'Invalid order');
    }
    if ($status === '') {
        jsonResponse(false, 'Status required');
    }

    $model = new Order();
    $res = $model->updateStatus($id, $status);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Updated'));
}

// Update status of an individual order item
if (isset($_REQUEST['orderItemUpdateStatus'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('orderItemUpdateStatus');

    $id = intval($data['id'] ?? 0);
    $status = trim($data['status'] ?? '');

    if ($id <= 0) {
        jsonResponse(false, 'Invalid item');
    }
    if ($status === '') {
        jsonResponse(false, 'Status required');
    }

    $model = new Order();
    $res = $model->updateItemStatus($id, $status);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Updated'));
}

// Send order to kitchen (changes status to in-kitchen)
if (isset($_REQUEST['orderSendToKitchen'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('orderSendToKitchen');

    $id = intval($data['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid order');
    }

    $model = new Order();
    $res = $model->sendToKitchen($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Sent'));
}

// Mark all items in an order as ready
if (isset($_REQUEST['orderMarkAllReady'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('orderMarkAllReady');

    $id = intval($data['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid order');
    }

    $model = new Order();
    $res = $model->markAllReady($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Updated'));
}

jsonResponse(false, 'No action');
