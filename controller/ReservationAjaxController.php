<?php
// AJAX controller for Reservation operations
// Handles creation, updating and deletion of reservations via JSON payloads.

$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/config/validation.php';
require_once $basePath . '/model/Reservation.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Emit JSON response and halt.
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

// -------------------- RESERVATION ACTIONS --------------------

// Create reservation
if (isset($_REQUEST['reservationCreate'])) {
    requireAjaxLogin();
    // admin, manager or cashier may create reservations
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('reservationCreate');

    $table_id = !empty($data['table_id']) ? intval($data['table_id']) : 0;
    $customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : null;
    $customer_name = trim($data['customer_name'] ?? '');
    $customer_phone = trim($data['customer_phone'] ?? '');
    $guest_count = intval($data['guest_count'] ?? 0);
    $reservation_date = trim($data['reservation_date'] ?? '');
    $reservation_time = trim($data['reservation_time'] ?? '');
    $notes = trim($data['notes'] ?? '');

    if ($table_id <= 0) {
        jsonResponse(false, 'Table required');
    }
    if ($customer_name === '') {
        jsonResponse(false, 'Customer name required');
    }
    if ($customer_phone === '') {
        jsonResponse(false, 'Customer phone required');
    }
    if ($guest_count <= 0) {
        jsonResponse(false, 'Guest count required');
    }
    if ($reservation_date === '' || $reservation_time === '') {
        jsonResponse(false, 'Date & time required');
    }

    $model = new Reservation();
    $res = $model->create([
        'table_id'        => $table_id,
        'customer_id'     => $customer_id,
        'customer_name'   => $customer_name,
        'customer_phone'  => $customer_phone,
        'guest_count'     => $guest_count,
        'reservation_date'=> $reservation_date,
        'reservation_time'=> $reservation_time,
        'notes'           => $notes,
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Update reservation
if (isset($_REQUEST['reservationUpdate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('reservationUpdate');

    $id = intval($data['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid reservation');
    }

    $table_id = !empty($data['table_id']) ? intval($data['table_id']) : 0;
    $customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : null;
    $customer_name = trim($data['customer_name'] ?? '');
    $customer_phone = trim($data['customer_phone'] ?? '');
    $guest_count = intval($data['guest_count'] ?? 0);
    $reservation_date = trim($data['reservation_date'] ?? '');
    $reservation_time = trim($data['reservation_time'] ?? '');
    $notes = trim($data['notes'] ?? '');
    $status = trim($data['status'] ?? 'pending');

    if ($table_id <= 0) {
        jsonResponse(false, 'Table required');
    }
    if ($customer_name === '') {
        jsonResponse(false, 'Customer name required');
    }
    if ($customer_phone === '') {
        jsonResponse(false, 'Customer phone required');
    }
    if ($guest_count <= 0) {
        jsonResponse(false, 'Guest count required');
    }
    if ($reservation_date === '' || $reservation_time === '') {
        jsonResponse(false, 'Date & time required');
    }

    $model = new Reservation();
    $res = $model->update($id, [
        'table_id'        => $table_id,
        'customer_id'     => $customer_id,
        'customer_name'   => $customer_name,
        'customer_phone'  => $customer_phone,
        'guest_count'     => $guest_count,
        'reservation_date'=> $reservation_date,
        'reservation_time'=> $reservation_time,
        'notes'           => $notes,
        'status'          => $status,
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

// Delete reservation
if (isset($_REQUEST['reservationDelete'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('reservationDelete');
    $id = intval($data['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid reservation');
    }

    $model = new Reservation();
    $res = $model->delete($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Deleted'));
}

jsonResponse(false, 'No action');
