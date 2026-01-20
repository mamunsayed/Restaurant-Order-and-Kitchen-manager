<?php
// AJAX controller for Table operations
// Handles create, update, delete actions via JSON payload

$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/config/validation.php';
require_once $basePath . '/model/Table.php';

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

// Create table
if (isset($_REQUEST['tableCreate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('tableCreate');

    $table_number = trim($data['table_number'] ?? '');
    $capacity = intval($data['capacity'] ?? 0);
    $status = $data['status'] ?? 'available';

    if ($table_number === '') jsonResponse(false, 'Table number required');
    if ($capacity <= 0) jsonResponse(false, 'Valid capacity required');

    $m = new Table();
    $res = $m->create(['table_number'=>$table_number,'capacity'=>$capacity,'status'=>$status]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Update table
if (isset($_REQUEST['tableUpdate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('tableUpdate');

    $id = intval($data['id'] ?? 0);
    $table_number = trim($data['table_number'] ?? '');
    $capacity = intval($data['capacity'] ?? 0);
    $status = $data['status'] ?? 'available';

    if ($id <= 0) jsonResponse(false, 'Invalid table');
    if ($table_number === '') jsonResponse(false, 'Table number required');
    if ($capacity <= 0) jsonResponse(false, 'Valid capacity required');

    $m = new Table();
    $res = $m->update($id, ['table_number'=>$table_number,'capacity'=>$capacity,'status'=>$status]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Delete table
if (isset($_REQUEST['tableDelete'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('tableDelete');
    $id = intval($data['id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid table');

    $m = new Table();
    $res = $m->delete($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Deleted'));
}

jsonResponse(false, 'No action');