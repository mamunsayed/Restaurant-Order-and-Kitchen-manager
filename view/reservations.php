<?php
$pageTitle = 'Reservations';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Reservation.php';
require_once $basePath . '/model/Table.php';

requireLogin();
requireRole(['admin', 'manager']);

$reservationModel = new Reservation();
$tableModel = new Table();

$error = '';
$success = '';

if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'create') {
            $data = array(
                'table_id' => isset($_POST['table_id']) ? $_POST['table_id'] : null,
                'customer_name' => isset($_POST['customer_name']) ? $_POST['customer_name'] : '',
                'customer_phone' => isset($_POST['customer_phone']) ? $_POST['customer_phone'] : '',
                'guest_count' => isset($_POST['guest_count']) ? $_POST['guest_count'] : 2,
                'reservation_date' => isset($_POST['reservation_date']) ? $_POST['reservation_date'] : '',
                'reservation_time' => isset($_POST['reservation_time']) ? $_POST['reservation_time'] : '',
                'notes' => isset($_POST['notes']) ? $_POST['notes'] : ''
            );
            if (empty($data['customer_name']) || empty($data['customer_phone'])) {
                $error = 'Customer name and phone are required';
            } elseif (empty($data['reservation_date']) || empty($data['reservation_time'])) {
                $error = 'Date and time are required';
            } else {
                $result = $reservationModel->create($data);
                $success = $result['success'] ? $result['message'] : '';
                $error = !$result['success'] ? $result['error'] : '';
            }
        }
        
        if ($action === 'update_status') {
            $id = intval($_POST['id']);
            $status = $_POST['status'];
            $result = $reservationModel->updateStatus($id, $status);
            $success = $result['success'] ? $result['message'] : '';
            $error = !$result['success'] ? $result['error'] : '';
        }
        
        if ($action === 'delete') {
            $id = intval($_POST['id']);
            $result = $reservationModel->delete($id);
            $success = $result['success'] ? $result['message'] : '';
            $error = !$result['success'] ? $result['error'] : '';
        }
    }
}

$reservations = $reservationModel->getAll();
$todayReservations = $reservationModel->getToday();
$tables = $tableModel->getAvailable();
$counts = $reservationModel->countByStatus();
$currentUser = getCurrentUser();
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/reservations.css">
</head> <body> <div class="sidebar"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"><span class="icon"></span> Dashboard</a> <a href="staff.php"><span class="icon"></span> Staff</a> <a href="tables.php"><span class="icon"></span> Tables</a> <a href="categories.php"><span class="icon"></span> Categories</a> <a href="menu_items.php"><span class="icon"></span> Menu Items</a> <a href="new_order.php"><span class="icon"></span> New Order</a> <a href="orders.php"><span class="icon"></span> Orders</a> <a href="kitchen.php"><span class="icon"></span> Kitchen</a> <a href="billing.php"><span class="icon"></span> Billing</a> <a href="customers.php"><span class="icon"></span> Customers</a> <a href="reservations.php" class="active"><span class="icon"></span> Reservations</a> <a href="reports.php"><span class="icon"></span> Reports</a> <a href="logout.php"><span class="icon"></span> Logout</a> </div> </div> <div class="main-content"> <div class="top-header"> <div class="welcome-text"> Reservations</div> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> <div class="content-area"> <?php if ($success != ''): ?> <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?> <?php if ($error != ''): ?> <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <div class="stats-row"> <div class="stat-card"> <div class="stat-icon"></div> <h3><?php echo count($todayReservations); ?></h3> <p>Today</p> </div> <div class="stat-card"> <div class="stat-icon">⏳</div> <h3><?php echo $counts['pending']; ?></h3> <p>Pending</p> </div> <div class="stat-card"> <div class="stat-icon"></div> <h3><?php echo $counts['confirmed']; ?></h3> <p>Confirmed</p> </div> <div class="stat-card"> <div class="stat-icon"></div> <h3><?php echo $counts['completed']; ?></h3> <p>Completed</p> </div> </div> <div class="card"> <h2> New Reservation</h2> <form method="POST" action="" id="reservationForm"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="create"> <div class="form-row"> <div class="form-group"> <label>Customer Name *</label> <input type="text" name="customer_name" placeholder="Enter name" required> </div> <div class="form-group"> <label>Phone *</label> <input type="text" name="customer_phone" placeholder="Enter phone" required> </div> </div> <div class="form-row"> <div class="form-group"> <label>Date *</label> <input type="date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" required> </div> <div class="form-group"> <label>Time *</label> <input type="time" name="reservation_time" required> </div> </div> <div class="form-row"> <div class="form-group"> <label>Guests</label> <input type="number" name="guest_count" value="2" min="1" max="20"> </div> <div class="form-group"> <label>Table (Optional)</label> <select name="table_id"> <option value="">-- Auto Assign --</option> <?php foreach ($tables as $table): ?> <option value="<?php echo $table['id']; ?>">Table <?php echo $table['table_number']; ?> (<?php echo $table['capacity']; ?> seats)</option> <?php endforeach; ?> </select> </div> </div> <div class="form-group"> <label>Notes</label> <textarea name="notes" placeholder="Special requests..."></textarea> </div> <div class="form-buttons"> <button type="submit" class="btn btn-primary">Create Reservation</button> </div> </form> </div> <div class="card"> <h2> All Reservations</h2> <?php if (count($reservations) > 0): ?> <table> <thead> <tr> <th>Customer</th> <th>Date & Time</th> <th>Guests</th> <th>Table</th> <th>Status</th> <th>Actions</th> </tr> </thead> <tbody> <?php foreach ($reservations as $res): ?> <tr> <td> <strong><?php echo htmlspecialchars($res['customer_name']); ?></strong><br> <small><?php echo htmlspecialchars($res['customer_phone']); ?></small> </td> <td> <?php echo date('M d, Y', strtotime($res['reservation_date'])); ?><br> <small><?php echo date('h:i A', strtotime($res['reservation_time'])); ?></small> </td> <td><?php echo $res['guest_count']; ?> guests</td> <td><?php echo $res['table_number'] ? 'Table ' . $res['table_number'] : 'Not assigned'; ?></td> <td><span class="badge badge-<?php echo $res['status']; ?>"><?php echo ucfirst($res['status']); ?></span></td> <td class="action-buttons"> <?php if ($res['status'] == 'pending'): ?> <form method="POST" style="display:inline;"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="update_status"> <input type="hidden" name="id" value="<?php echo $res['id']; ?>"> <input type="hidden" name="status" value="confirmed"> <button type="submit" class="btn btn-success btn-sm">Confirm</button> </form> <?php endif; ?> <?php if ($res['status'] == 'confirmed'): ?> <form method="POST" style="display:inline;"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="update_status"> <input type="hidden" name="id" value="<?php echo $res['id']; ?>"> <input type="hidden" name="status" value="completed"> <button type="submit" class="btn btn-primary btn-sm">Complete</button> </form> <?php endif; ?> <?php if (in_array($res['status'], ['pending', 'confirmed'])): ?> <form method="POST" style="display:inline;"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="update_status"> <input type="hidden" name="id" value="<?php echo $res['id']; ?>"> <input type="hidden" name="status" value="cancelled"> <button type="submit" class="btn btn-danger btn-sm">Cancel</button> </form> <?php endif; ?> </td> </tr> <?php endforeach; ?> </tbody> </table> <?php else: ?> <div class="empty-message"> <p>No reservations found.</p> </div> <?php endif; ?> </div> </div> </div> <script src="../asset/js/common.js"></script>
<script src="../asset/js/ajax.js"></script>
<script src="../asset/js/reservations.js"></script>
</body> </html>