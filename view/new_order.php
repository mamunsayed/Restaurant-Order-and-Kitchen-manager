<?php
$pageTitle = 'New Order';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Order.php';
require_once $basePath . '/model/Table.php';

requireLogin();
requireRole(['admin', 'manager', 'cashier']);

$orderModel = new Order();
$tableModel = new Table();

$error = '';
$success = '';

// Handle form submission
if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'create_order') {
            $data = array(
                'table_id' => isset($_POST['table_id']) ? $_POST['table_id'] : null,
                'order_type' => isset($_POST['order_type']) ? $_POST['order_type'] : 'dine-in',
                'user_id' => $_SESSION['user_id'],
                'notes' => isset($_POST['notes']) ? $_POST['notes'] : '',
                'delivery_address' => isset($_POST['delivery_address']) ? $_POST['delivery_address'] : ''
            );
            
            if ($data['order_type'] === 'dine-in' && empty($data['table_id'])) {
                $error = 'Please select a table for dine-in order';
            } elseif ($data['order_type'] === 'delivery' && empty($data['delivery_address'])) {
                $error = 'Please enter delivery address';
            } else {
                $result = $orderModel->create($data);
                if ($result['success']) {
                    header("Location: order_details.php?id=" . $result['id']);
                    exit();
                } else {
                    $error = $result['error'];
                }
            }
        }
    }
}

// Get available tables
$tables = $tableModel->getAll();

// Get active orders
$activeOrders = $orderModel->getActiveOrders();

$currentUser = getCurrentUser();
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/new_order.css">
</head> <body> <div class="sidebar"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"><span class="icon"></span> Dashboard</a> <a href="staff.php"><span class="icon"></span> Staff</a> <a href="tables.php"><span class="icon"></span> Tables</a> <a href="categories.php"><span class="icon"></span> Categories</a> <a href="menu_items.php"><span class="icon"></span> Menu Items</a> <a href="new_order.php" class="active"><span class="icon"></span> New Order</a> <a href="orders.php"><span class="icon"></span> Orders</a> <a href="kitchen.php"><span class="icon"></span> Kitchen</a> <a href="billing.php"><span class="icon"></span> Billing</a> <a href="customers.php"><span class="icon"></span> Customers</a> <a href="reservations.php"><span class="icon"></span> Reservations</a> <a href="reports.php"><span class="icon"></span> Reports</a> <a href="logout.php"><span class="icon"></span> Logout</a> </div> </div> <div class="main-content"> <div class="top-header"> <div class="welcome-text">Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span></div> <div class="top-header-right"> <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> </div> <div class="content-area"> <?php if ($error != ''): ?> <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <!-- Active Orders --> <?php if (count($activeOrders) > 0): ?> <div class="card"> <h2> Active Orders (<?php echo count($activeOrders); ?>)</h2> <?php foreach ($activeOrders as $order): ?> <div class="active-order-card"> <div class="active-order-info"> <h4>Table <?php echo $order['table_number'] ? $order['table_number'] : 'N/A'; ?> - <?php echo ucfirst($order['order_type']); ?></h4> <p>Order #<?php echo $order['id']; ?> | Items: <?php echo count($order['items']); ?> | Status: <?php echo ucfirst($order['status']); ?></p> </div> <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-warning btn-sm">Continue Order</a> </div> <?php endforeach; ?> </div> <?php endif; ?> <!-- New Order Form --> <div class="card"> <h2> Create New Order</h2> <form method="POST" action="" id="orderForm"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="create_order"> <input type="hidden" name="table_id" id="selectedTable" value=""> <input type="hidden" name="order_type" id="selectedOrderType" value="dine-in"> <!-- Order Type --> <div class="form-group"> <label>Order Type</label> <div class="order-type-grid"> <div class="order-type-btn selected" data-type="dine-in" onclick="selectOrderType('dine-in')"> <span class="type-icon"></span> <span class="type-name">Dine In</span> </div> <div class="order-type-btn" data-type="takeaway" onclick="selectOrderType('takeaway')"> <span class="type-icon"></span> <span class="type-name">Takeaway</span> </div> <div class="order-type-btn" data-type="delivery" onclick="selectOrderType('delivery')"> <span class="type-icon"></span> <span class="type-name">Delivery</span> </div> </div> </div> <!-- Table Selection --> <div class="form-group" id="tableSection"> <label>Select Table</label> <div class="table-grid"> <?php foreach ($tables as $table): ?> <div class="table-btn <?php echo $table['status']; ?>" 
                                 data-id="<?php echo $table['id']; ?>"
                                 <?php if ($table['status'] == 'available'): ?> onclick="selectTable(<?php echo $table['id']; ?>)"
                                 <?php endif; ?>> <div class="table-number">Table <?php echo $table['table_number']; ?></div> <div class="table-info"><?php echo $table['capacity']; ?> seats</div> <div class="table-info"><?php echo ucfirst($table['status']); ?></div> </div> <?php endforeach; ?> </div> <div class="legend"> <div class="legend-item"><div class="legend-box available"></div> Available</div> <div class="legend-item"><div class="legend-box selected-legend"></div> Selected</div> <div class="legend-item"><div class="legend-box occupied-legend"></div> Occupied</div> <div class="legend-item"><div class="legend-box reserved-legend"></div> Reserved</div> </div> </div> <!-- Delivery Address --> <div class="form-group delivery-field" id="deliveryField"> <label>Delivery Address *</label> <textarea name="delivery_address" placeholder="Enter delivery address"></textarea> </div> <!-- Notes --> <div class="form-group"> <label>Notes (Optional)</label> <textarea name="notes" placeholder="Any special instructions..."></textarea> </div> <button type="submit" class="btn btn-primary">Start Order →</button> </form> </div> </div> </div>  <script src="../asset/js/common.js"></script>
<script src="../asset/js/ajax.js"></script>
<script src="../asset/js/new_order.js"></script>
</body> </html>