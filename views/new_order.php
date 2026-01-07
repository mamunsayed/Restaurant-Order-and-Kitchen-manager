<?php
$pageTitle = 'New Order';
$basePath = dirname(__DIR__);

require_once $basePath . '/config/session.php';
require_once $basePath . '/config/security.php';
require_once $basePath . '/models/Order.php';
require_once $basePath . '/models/Table.php';

requireLogin();
requireRole(['admin', 'manager', 'server']);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Restaurant Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; min-height: 100vh; }
        .sidebar { width: 260px; background-color: #012754; color: white; position: fixed; top: 0; left: 0; height: 100vh; overflow-y: auto; }
        .sidebar-logo { display: flex; align-items: center; padding: 25px 20px; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-logo-icon { width: 45px; height: 45px; background-color: white; border-radius: 10px; display: flex; justify-content: center; align-items: center; color: #012754; font-size: 20px; font-weight: bold; }
        .sidebar-logo h2 { font-size: 18px; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.7); text-decoration: none; padding: 14px 20px; font-size: 15px; border-left: 3px solid transparent; }
        .sidebar-menu a:hover { background-color: rgba(255,255,255,0.08); color: white; }
        .sidebar-menu a.active { background-color: rgba(255,255,255,0.12); color: white; border-left-color: white; font-weight: bold; }
        .sidebar-menu a .icon { width: 22px; text-align: center; }
        .main-content { margin-left: 260px; background-color: #f0f2f5; min-height: 100vh; }
        .top-header { background-color: white; padding: 20px 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .welcome-text { font-size: 18px; color: #333; }
        .welcome-text span { font-weight: bold; color: #012754; }
        .top-header-right { display: flex; align-items: center; gap: 12px; }
        .user-role { font-size: 14px; color: #666; text-transform: capitalize; }
        .user-avatar { width: 40px; height: 40px; background-color: #012754; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold; }
        .content-area { padding: 30px; }
        .card { background-color: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #eee; }
        .card h2 { margin-bottom: 20px; color: #012754; font-size: 18px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background-color: #f9f9f9; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #012754; background-color: white; }
        .form-group textarea { height: 80px; resize: none; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #012754; color: white; }
        .btn-primary:hover { background-color: #011c3d; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-warning { background-color: #ff9800; color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        /* Order Type Selection */
        .order-type-grid { display: flex; gap: 15px; margin-bottom: 20px; }
        .order-type-btn { flex: 1; padding: 25px 20px; border: 2px solid #ddd; background-color: #f9f9f9; border-radius: 12px; cursor: pointer; text-align: center; transition: all 0.2s; }
        .order-type-btn:hover { border-color: #012754; background-color: #e8eaf6; }
        .order-type-btn.selected { border-color: #012754; background-color: #012754; color: white; }
        .order-type-btn .type-icon { font-size: 32px; margin-bottom: 10px; display: block; }
        .order-type-btn .type-name { font-size: 16px; font-weight: bold; }
        
        /* Table Grid */
        .table-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px; }
        .table-btn { padding: 20px; border: 2px solid #ddd; background-color: #f9f9f9; border-radius: 12px; cursor: pointer; text-align: center; transition: all 0.2s; }
        .table-btn:hover:not(.occupied) { border-color: #012754; background-color: #e8eaf6; }
        .table-btn.selected { border-color: #012754; background-color: #012754; color: white; }
        .table-btn.occupied { border-color: #ef9a9a; background-color: #ffebee; color: #c62828; cursor: not-allowed; }
        .table-btn.reserved { border-color: #90caf9; background-color: #e3f2fd; color: #1565c0; }
        .table-btn .table-number { font-size: 20px; font-weight: bold; }
        .table-btn .table-info { font-size: 12px; margin-top: 5px; opacity: 0.8; }
        
        /* Legend */
        .legend { display: flex; gap: 25px; margin-bottom: 20px; font-size: 13px; }
        .legend-item { display: flex; align-items: center; gap: 8px; }
        .legend-box { width: 20px; height: 20px; border-radius: 5px; border: 2px solid; }
        .legend-box.available { border-color: #ddd; background-color: #f9f9f9; }
        .legend-box.selected-legend { border-color: #012754; background-color: #012754; }
        .legend-box.occupied-legend { border-color: #ef9a9a; background-color: #ffebee; }
        .legend-box.reserved-legend { border-color: #90caf9; background-color: #e3f2fd; }
        
        /* Active Orders */
        .active-order-card { background-color: #fff8e1; border: 1px solid #ffcc02; padding: 18px 20px; border-radius: 10px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .active-order-info h4 { color: #333; margin-bottom: 5px; font-size: 16px; }
        .active-order-info p { color: #666; font-size: 13px; }
        
        .delivery-field { display: none; }
        .delivery-field.show { display: block; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">R</div>
            <h2>Restaurant</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><span class="icon">📊</span> Dashboard</a>
            <a href="staff.php"><span class="icon">👥</span> Staff</a>
            <a href="tables.php"><span class="icon">🪑</span> Tables</a>
            <a href="categories.php"><span class="icon">📁</span> Categories</a>
            <a href="menu_items.php"><span class="icon">🍔</span> Menu Items</a>
            <a href="new_order.php" class="active"><span class="icon">➕</span> New Order</a>
            <a href="orders.php"><span class="icon">📋</span> Orders</a>
            <a href="kitchen.php"><span class="icon">👨‍🍳</span> Kitchen</a>
            <a href="billing.php"><span class="icon">💰</span> Billing</a>
            <a href="customers.php"><span class="icon">👤</span> Customers</a>
            <a href="reservations.php"><span class="icon">🎫</span> Reservations</a>
            <a href="reports.php"><span class="icon">📈</span> Reports</a>
            <a href="logout.php"><span class="icon">🚪</span> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div class="welcome-text">Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span></div>
            <div class="top-header-right">
                <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="content-area">
            <?php if ($error != ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Active Orders -->
            <?php if (count($activeOrders) > 0): ?>
            <div class="card">
                <h2>📋 Active Orders (<?php echo count($activeOrders); ?>)</h2>
                <?php foreach ($activeOrders as $order): ?>
                <div class="active-order-card">
                    <div class="active-order-info">
                        <h4>Table <?php echo $order['table_number'] ? $order['table_number'] : 'N/A'; ?> - <?php echo ucfirst($order['order_type']); ?></h4>
                        <p>Order #<?php echo $order['id']; ?> | Items: <?php echo count($order['items']); ?> | Status: <?php echo ucfirst($order['status']); ?></p>
                    </div>
                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-warning btn-sm">Continue Order</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- New Order Form -->
            <div class="card">
                <h2>➕ Create New Order</h2>
                <form method="POST" action="" id="orderForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="create_order">
                    <input type="hidden" name="table_id" id="selectedTable" value="">
                    <input type="hidden" name="order_type" id="selectedOrderType" value="dine-in">

                    <!-- Order Type -->
                    <div class="form-group">
                        <label>Order Type</label>
                        <div class="order-type-grid">
                            <div class="order-type-btn selected" data-type="dine-in" onclick="selectOrderType('dine-in')">
                                <span class="type-icon">🍽️</span>
                                <span class="type-name">Dine In</span>
                            </div>
                            <div class="order-type-btn" data-type="takeaway" onclick="selectOrderType('takeaway')">
                                <span class="type-icon">🥡</span>
                                <span class="type-name">Takeaway</span>
                            </div>
                            <div class="order-type-btn" data-type="delivery" onclick="selectOrderType('delivery')">
                                <span class="type-icon">🚗</span>
                                <span class="type-name">Delivery</span>
                            </div>
                        </div>
                    </div>

                    <!-- Table Selection -->
                    <div class="form-group" id="tableSection">
                        <label>Select Table</label>
                        <div class="table-grid">
                            <?php foreach ($tables as $table): ?>
                            <div class="table-btn <?php echo $table['status']; ?>" 
                                 data-id="<?php echo $table['id']; ?>"
                                 <?php if ($table['status'] == 'available'): ?>
                                 onclick="selectTable(<?php echo $table['id']; ?>)"
                                 <?php endif; ?>>
                                <div class="table-number">Table <?php echo $table['table_number']; ?></div>
                                <div class="table-info"><?php echo $table['capacity']; ?> seats</div>
                                <div class="table-info"><?php echo ucfirst($table['status']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="legend">
                            <div class="legend-item"><div class="legend-box available"></div> Available</div>
                            <div class="legend-item"><div class="legend-box selected-legend"></div> Selected</div>
                            <div class="legend-item"><div class="legend-box occupied-legend"></div> Occupied</div>
                            <div class="legend-item"><div class="legend-box reserved-legend"></div> Reserved</div>
                        </div>
                    </div>

                    <!-- Delivery Address -->
                    <div class="form-group delivery-field" id="deliveryField">
                        <label>Delivery Address *</label>
                        <textarea name="delivery_address" placeholder="Enter delivery address"></textarea>
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" placeholder="Any special instructions..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Start Order →</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    var selectedTable = null;
    var selectedOrderType = 'dine-in';

    function selectOrderType(type) {
        selectedOrderType = type;
        document.getElementById('selectedOrderType').value = type;

        // Update UI
        var btns = document.querySelectorAll('.order-type-btn');
        for (var i = 0; i < btns.length; i++) {
            btns[i].classList.remove('selected');
            if (btns[i].getAttribute('data-type') === type) {
                btns[i].classList.add('selected');
            }
        }

        // Show/hide table section
        var tableSection = document.getElementById('tableSection');
        var deliveryField = document.getElementById('deliveryField');

        if (type === 'dine-in') {
            tableSection.style.display = 'block';
            deliveryField.classList.remove('show');
        } else if (type === 'delivery') {
            tableSection.style.display = 'none';
            deliveryField.classList.add('show');
            selectedTable = null;
            document.getElementById('selectedTable').value = '';
        } else {
            tableSection.style.display = 'none';
            deliveryField.classList.remove('show');
            selectedTable = null;
            document.getElementById('selectedTable').value = '';
        }
    }

    function selectTable(tableId) {
        selectedTable = tableId;
        document.getElementById('selectedTable').value = tableId;

        // Update UI
        var btns = document.querySelectorAll('.table-btn');
        for (var i = 0; i < btns.length; i++) {
            if (btns[i].classList.contains('available') || btns[i].classList.contains('selected')) {
                btns[i].classList.remove('selected');
                if (!btns[i].classList.contains('occupied') && !btns[i].classList.contains('reserved')) {
                    btns[i].classList.add('available');
                }
            }
            if (btns[i].getAttribute('data-id') == tableId) {
                btns[i].classList.remove('available');
                btns[i].classList.add('selected');
            }
        }
    }

    document.getElementById('orderForm').onsubmit = function(e) {
        if (selectedOrderType === 'dine-in' && !selectedTable) {
            alert('Please select a table');
            e.preventDefault();
            return false;
        }

        if (selectedOrderType === 'delivery') {
            var address = document.querySelector('textarea[name="delivery_address"]').value.trim();
            if (address === '') {
                alert('Please enter delivery address');
                e.preventDefault();
                return false;
            }
        }

        return true;
    };
    </script>
</body>
</html>