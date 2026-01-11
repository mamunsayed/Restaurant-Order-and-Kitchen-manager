<?php
// Unified AJAX controller (TicketTrack-style)
// Handles JSON payloads sent as application/x-www-form-urlencoded with a single key per action.

$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/config/validation.php';

// Models
require_once $basePath . '/model/Category.php';
require_once $basePath . '/model/MenuItem.php';
require_once $basePath . '/model/Table.php';
require_once $basePath . '/model/Staff.php';
require_once $basePath . '/model/Customer.php';
require_once $basePath . '/model/Reservation.php';
require_once $basePath . '/model/Order.php';

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($ok, $msg, $extra = []) {
    echo json_encode(array_merge([
        'success' => $ok ? true : false,
        'message' => $msg
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
    // CSRF (required for state-changing actions)
    if (!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
        jsonResponse(false, 'Invalid request. Please try again.');
    }
    return $data;
}

// -------------------- CATEGORY --------------------
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

// -------------------- MENU ITEM --------------------
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

// -------------------- TABLE --------------------
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

// -------------------- STAFF --------------------
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

// -------------------- CUSTOMER --------------------
if (isset($_REQUEST['customerCreate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('customerCreate');

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');

    if ($name === '') jsonResponse(false, 'Customer name required');

    $m = new Customer();
    $res = $m->create(['name'=>$name,'email'=>$email,'phone'=>$phone,'address'=>$address]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

if (isset($_REQUEST['customerUpdate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('customerUpdate');

    $id = intval($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');

    if ($id <= 0) jsonResponse(false, 'Invalid customer');
    if ($name === '') jsonResponse(false, 'Customer name required');

    $m = new Customer();
    $res = $m->update($id, ['name'=>$name,'email'=>$email,'phone'=>$phone,'address'=>$address]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

if (isset($_REQUEST['customerDelete'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('customerDelete');
    $id = intval($data['id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid customer');

    $m = new Customer();
    $res = $m->delete($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Deleted'));
}

// -------------------- RESERVATION --------------------
if (isset($_REQUEST['reservationCreate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('reservationCreate');

    $table_id = !empty($data['table_id']) ? intval($data['table_id']) : null;
    $customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : null;
    $customer_name = trim($data['customer_name'] ?? '');
    $customer_phone = trim($data['customer_phone'] ?? '');
    $guest_count = intval($data['guest_count'] ?? 0);
    $reservation_date = trim($data['reservation_date'] ?? '');
    $reservation_time = trim($data['reservation_time'] ?? '');
    $notes = trim($data['notes'] ?? '');

    if ($table_id === null || $table_id <= 0) jsonResponse(false,'Table required');
    if ($customer_name === '') jsonResponse(false,'Customer name required');
    if ($customer_phone === '') jsonResponse(false,'Customer phone required');
    if ($guest_count <= 0) jsonResponse(false,'Guest count required');
    if ($reservation_date==='' || $reservation_time==='') jsonResponse(false,'Date & time required');

    $m = new Reservation();
    $res = $m->create([
        'table_id'=>$table_id,
        'customer_id'=>$customer_id,
        'customer_name'=>$customer_name,
        'customer_phone'=>$customer_phone,
        'guest_count'=>$guest_count,
        'reservation_date'=>$reservation_date,
        'reservation_time'=>$reservation_time,
        'notes'=>$notes
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

if (isset($_REQUEST['reservationUpdate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('reservationUpdate');

    $id = intval($data['id'] ?? 0);
    if ($id<=0) jsonResponse(false,'Invalid reservation');

    $table_id = !empty($data['table_id']) ? intval($data['table_id']) : null;
    $customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : null;
    $customer_name = trim($data['customer_name'] ?? '');
    $customer_phone = trim($data['customer_phone'] ?? '');
    $guest_count = intval($data['guest_count'] ?? 0);
    $reservation_date = trim($data['reservation_date'] ?? '');
    $reservation_time = trim($data['reservation_time'] ?? '');
    $notes = trim($data['notes'] ?? '');
    $status = trim($data['status'] ?? 'pending');

    if ($table_id === null || $table_id <= 0) jsonResponse(false,'Table required');
    if ($customer_name === '') jsonResponse(false,'Customer name required');
    if ($customer_phone === '') jsonResponse(false,'Customer phone required');
    if ($guest_count <= 0) jsonResponse(false,'Guest count required');
    if ($reservation_date==='' || $reservation_time==='') jsonResponse(false,'Date & time required');

    $m = new Reservation();
    $res = $m->update($id, [
        'table_id'=>$table_id,
        'customer_id'=>$customer_id,
        'customer_name'=>$customer_name,
        'customer_phone'=>$customer_phone,
        'guest_count'=>$guest_count,
        'reservation_date'=>$reservation_date,
        'reservation_time'=>$reservation_time,
        'notes'=>$notes,
        'status'=>$status
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Done'));
}

if (isset($_REQUEST['reservationDelete'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('reservationDelete');
    $id = intval($data['id'] ?? 0);
    if ($id<=0) jsonResponse(false,'Invalid reservation');

    $m = new Reservation();
    $res = $m->delete($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Deleted'));
}

// -------------------- ORDER --------------------
if (isset($_REQUEST['orderCreate'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('orderCreate');

    $order_type = trim($data['order_type'] ?? 'dine_in');
    $table_id = !empty($data['table_id']) ? intval($data['table_id']) : null;
    $customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : null;
    $notes = trim($data['notes'] ?? '');
    $delivery_address = trim($data['delivery_address'] ?? '');
    $m = new Order();
    $res = $m->create([
        'table_id'=>$table_id,
        'customer_id'=>$customer_id,
        'user_id'=>intval($_SESSION['user_id']),
        'order_type'=>$order_type,
        'notes'=>$notes,
        'delivery_address'=>$delivery_address,
    ]);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Order created'), ['id' => $res['id'] ?? null]);
}


if (isset($_REQUEST['orderAddItem'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('orderAddItem');

    $order_id = intval($data['order_id'] ?? 0);
    $menu_item_id = intval($data['menu_item_id'] ?? 0);
    $quantity = intval($data['quantity'] ?? 1);

    if ($order_id<=0) jsonResponse(false,'Invalid order');
    if ($menu_item_id<=0) jsonResponse(false,'Invalid item');
    if ($quantity<=0) $quantity = 1;

    $m = new Order();
    $res = $m->addItem($order_id, $menu_item_id, $quantity);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Added'));
}

if (isset($_REQUEST['orderUpdateQuantity'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('orderUpdateQuantity');

    $item_id = intval($data['item_id'] ?? 0);
    $quantity = intval($data['quantity'] ?? 1);

    if ($item_id<=0) jsonResponse(false,'Invalid item');
    if ($quantity<=0) $quantity = 1;

    $m = new Order();
    $res = $m->updateItemQuantity($item_id, $quantity);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Updated'));
}

if (isset($_REQUEST['orderRemoveItem'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('orderRemoveItem');

    $item_id = intval($data['item_id'] ?? 0);
    if ($item_id<=0) jsonResponse(false,'Invalid item');

    $m = new Order();
    $res = $m->removeItem($item_id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Removed'));
}

if (isset($_REQUEST['orderUpdateStatus'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('orderUpdateStatus');

    $id = intval($data['id'] ?? 0);
    $status = trim($data['status'] ?? '');
    if ($id<=0) jsonResponse(false,'Invalid order');
    if ($status==='') jsonResponse(false,'Status required');

    $m = new Order();
    $res = $m->updateStatus($id, $status);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Updated'));
}

if (isset($_REQUEST['orderItemUpdateStatus'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('orderItemUpdateStatus');

    $id = intval($data['id'] ?? 0);
    $status = trim($data['status'] ?? '');
    if ($id<=0) jsonResponse(false,'Invalid item');
    if ($status==='') jsonResponse(false,'Status required');

    $m = new Order();
    $res = $m->updateItemStatus($id, $status);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Updated'));
}

if (isset($_REQUEST['orderSendToKitchen'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager','cashier']);
    $data = readPayload('orderSendToKitchen');

    $id = intval($data['id'] ?? 0);
    if ($id<=0) jsonResponse(false,'Invalid order');

    $m = new Order();
    $res = $m->sendToKitchen($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Sent'));
}


if (isset($_REQUEST['orderMarkAllReady'])) {
    requireAjaxLogin();
    requireAjaxRole(['admin','manager']);
    $data = readPayload('orderMarkAllReady');

    $id = intval($data['id'] ?? 0);
    if ($id<=0) jsonResponse(false,'Invalid order');

    $m = new Order();
    $res = $m->markAllReady($id);
    jsonResponse($res['success'] ?? false, ($res['message'] ?? 'Updated'));
}

jsonResponse(false, 'No action');
