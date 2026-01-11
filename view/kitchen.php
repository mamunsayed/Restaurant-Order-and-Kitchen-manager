<?php
$pageTitle = 'Kitchen';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Order.php';

requireLogin();
requireRole(['admin', 'manager']);

$orderModel = new Order();

$error = '';
$success = '';

// Handle actions
if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'mark_item_ready') {
            $itemId = intval($_POST['item_id']);
            $result = $orderModel->updateItemStatus($itemId, 'ready');
            if ($result['success']) {
                $success = 'Item marked as ready';
            }
        }
        
        if ($action === 'mark_all_ready') {
            $orderId = intval($_POST['order_id']);
            $result = $orderModel->markAllReady($orderId);
            if ($result['success']) {
                $success = 'All items marked as ready';
            }
        }
        
        if ($action === 'mark_served') {
            $orderId = intval($_POST['order_id']);
            $result = $orderModel->completeOrder($orderId);
            if ($result['success']) {
                $success = 'Order marked as served';
            }
        }
    }
}

// Get kitchen orders
$activeOrders = $orderModel->getActiveOrders();

// Separate by status
$cookingOrders = array();
$readyOrders = array();

foreach ($activeOrders as $order) {
    if ($order['status'] == 'ready') {
        $readyOrders[] = $order;
    } else {
        $cookingOrders[] = $order;
    }
}

// Count items
$cookingCount = 0;
$readyCount = 0;
foreach ($cookingOrders as $order) {
    foreach ($order['items'] as $item) {
        if ($item['status'] == 'cooking') {
            $cookingCount += $item['quantity'];
        }
    }
}
foreach ($readyOrders as $order) {
    foreach ($order['items'] as $item) {
        $readyCount += $item['quantity'];
    }
}

$currentUser = getCurrentUser();
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/kitchen.css">
</head> <body> <div class="sidebar"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"><span class="icon"></span> Dashboard</a> <a href="staff.php"><span class="icon"></span> Staff</a> <a href="tables.php"><span class="icon"></span> Tables</a> <a href="categories.php"><span class="icon"></span> Categories</a> <a href="menu_items.php"><span class="icon"></span> Menu Items</a> <a href="new_order.php"><span class="icon"></span> New Order</a> <a href="orders.php"><span class="icon"></span> Orders</a> <a href="kitchen.php" class="active"><span class="icon"></span> Kitchen</a> <a href="billing.php"><span class="icon"></span> Billing</a> <a href="customers.php"><span class="icon"></span> Customers</a> <a href="reservations.php"><span class="icon"></span> Reservations</a> <a href="reports.php"><span class="icon"></span> Reports</a> <a href="logout.php"><span class="icon"></span> Logout</a> </div> </div> <div class="main-content"> <div class="top-header"> <div class="welcome-text"> Kitchen Display</div> <div class="top-header-right"> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> </div> <div class="content-area"> <?php if ($success != ''): ?> <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?> <?php if ($error != ''): ?> <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <!-- Stats --> <div class="stats-row"> <div class="stat-card cooking"> <div class="stat-icon"></div> <h3><?php echo $cookingCount; ?></h3> <p>Cooking</p> </div> <div class="stat-card ready"> <div class="stat-icon"></div> <h3><?php echo $readyCount; ?></h3> <p>Ready to Serve</p> </div> <div class="stat-card total"> <div class="stat-icon"></div> <h3><?php echo count($activeOrders); ?></h3> <p>Active Orders</p> </div> </div> <!-- Cooking Orders --> <div class="card"> <h2> Cooking Orders (<?php echo count($cookingOrders); ?>)</h2> <?php if (count($cookingOrders) > 0): ?> <div class="orders-grid"> <?php foreach ($cookingOrders as $order): ?> <div class="order-card"> <div class="order-card-header cooking"> <h3> Table <?php echo $order['table_number'] ? $order['table_number'] : 'N/A'; ?></h3> <span class="order-timer timer-normal" id="timer-<?php echo $order['id']; ?>"> <?php 
                                $created = strtotime($order['created_at']);
                                $mins = floor((time() - $created) / 60);
                                echo $mins . ' min';
                                ?> </span> </div> <div class="order-card-info"> <span> Order #<?php echo $order['id']; ?></span> <span> <?php echo ucfirst($order['order_type']); ?></span> </div> <div class="order-card-items"> <?php foreach ($order['items'] as $item): ?> <div class="order-item"> <div class="order-item-info"> <span class="order-item-qty"><?php echo $item['quantity']; ?></span> <span class="order-item-name"><?php echo htmlspecialchars($item['item_name']); ?></span> </div> <?php if ($item['status'] == 'cooking'): ?> <form method="POST" style="display:inline;"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="mark_item_ready"> <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>"> <button type="submit" class="item-status item-cooking">Cooking →</button> </form> <?php else: ?> <span class="item-status item-ready"> Ready</span> <?php endif; ?> </div> <?php endforeach; ?> </div> <div class="order-card-actions"> <form method="POST" style="flex:1;"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="mark_all_ready"> <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>"> <button type="submit" class="btn btn-success" style="width:100%;"> All Ready</button> </form> <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="flex:1; text-align:center;"> View</a> </div> </div> <?php endforeach; ?> </div> <?php else: ?> <div class="empty-message"> <div class="empty-icon">☕</div> <h3>No Cooking Orders</h3> <p>Waiting for new orders...</p> </div> <?php endif; ?> </div> <!-- Ready Orders --> <?php if (count($readyOrders) > 0): ?> <div class="card"> <h2> Ready to Serve (<?php echo count($readyOrders); ?>)</h2> <div class="orders-grid"> <?php foreach ($readyOrders as $order): ?> <div class="order-card"> <div class="order-card-header ready"> <h3> Table <?php echo $order['table_number'] ? $order['table_number'] : 'N/A'; ?></h3> <span class="order-timer timer-normal">Ready!</span> </div> <div class="order-card-info"> <span> Order #<?php echo $order['id']; ?></span> <span> <?php echo ucfirst($order['order_type']); ?></span> </div> <div class="order-card-items"> <?php foreach ($order['items'] as $item): ?> <div class="order-item"> <div class="order-item-info"> <span class="order-item-qty"><?php echo $item['quantity']; ?></span> <span class="order-item-name"><?php echo htmlspecialchars($item['item_name']); ?></span> </div> <span class="item-status item-ready"> Ready</span> </div> <?php endforeach; ?> </div> <div class="order-card-actions"> <form method="POST" style="flex:1;"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="mark_served"> <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>"> <button type="submit" class="btn btn-success" style="width:100%;"> Mark Served</button> </form> </div> </div> <?php endforeach; ?> </div> </div> <?php endif; ?> </div> </div> <button class="refresh-btn" onclick="location.reload();" title="Refresh"></button>  <script src="../asset/js/common.js"></script>
<script src="../asset/js/ajax.js"></script>
<script src="../asset/js/kitchen.js"></script>
</body> </html>