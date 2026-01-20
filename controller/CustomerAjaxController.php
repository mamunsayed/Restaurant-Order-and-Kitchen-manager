<?php
// AJAX controller for Customer operations
// Handles create, update and delete actions for customers via JSON payloads.

$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/config/validation.php';
require_once $basePath . '/model/Customer.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

/**
 * Helper to emit a JSON response and terminate execution.
 *
 * @param bool   $ok  Whether the operation succeeded
 * @param string $msg Message to send back to the client
 * @param array  $extra Additional fields to merge into the response
 */
function jsonResponse($ok, $msg, $extra = []) {
    echo json_encode(array_merge([
        'success' => $ok ? true : false,
        'message' => $msg,
    ], $extra));
    exit;
}

/**
 * Ensure a user is logged in for AJAX requests.
 * If not, respond with an error.
 */
function requireAjaxLogin() {
    requireLogin();
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'Not logged in');
    }
}

/**
 * Enforce that the current user has one of the given roles.
 *
 * @param array $roles Allowed roles
 */
function requireAjaxRole($roles) {
    requireRole($roles);
}

/**
 * Read and decode a JSON payload from a specified POST key.
 * Validates that the CSRF token is correct.
 *
 * @param string $key The POST key containing the JSON payload
 * @return array The decoded payload
 */
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

// -------------------- CUSTOMER ACTIONS --------------------

// Create a new customer
if (isset($_REQUEST['customerCreate'])) {
    requireAjaxLogin();
    // admin, manager and cashier can create customers
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('customerCreate');

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');

    if ($name === '') {
        jsonResponse(false, 'Customer name required');
    }

    $model = new Customer();
    $res = $model->create([
        'name'    => $name,
        'email'   => $email,
        'phone'   => $phone,
        'address' => $address,
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Update existing customer
if (isset($_REQUEST['customerUpdate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('customerUpdate');

    $id = intval($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');

    if ($id <= 0) {
        jsonResponse(false, 'Invalid customer');
    }
    if ($name === '') {
        jsonResponse(false, 'Customer name required');
    }

    $model = new Customer();
    $res = $model->update($id, [
        'name'    => $name,
        'email'   => $email,
        'phone'   => $phone,
        'address' => $address,
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Delete a customer
if (isset($_REQUEST['customerDelete'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('customerDelete');
    $id = intval($data['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid customer');
    }

    $model = new Customer();
    $res = $model->delete($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Deleted'));
}

// Default fallback
jsonResponse(false, 'No action');
