<?php
$pageTitle = 'Kitchen';
$basePath = dirname(__DIR__);

require_once $basePath . '/config/session.php';
require_once $basePath . '/config/security.php';
require_once $basePath . '/models/Order.php';

requireLogin();
requireRole(['admin', 'manager', 'kitchen']);

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
        .user-avatar { width: 40px; height: 40px; background-color: #012754; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold; }
        .content-area { padding: 30px; }
        
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background-color: white; padding: 25px; border-radius: 12px; text-align: center; border: 1px solid #eee; }
        .stat-card .stat-icon { font-size: 32px; margin-bottom: 10px; }
        .stat-card h3 { font-size: 32px; margin-bottom: 5px; }
        .stat-card p { color: #666; font-size: 14px; }
        .stat-card.cooking h3 { color: #1565c0; }
        .stat-card.ready h3 { color: #2e7d32; }
        .stat-card.total h3 { color: #ef6c00; }
        
        .card { background-color: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #eee; }
        .card h2 { margin-bottom: 20px; color: #012754; font-size: 18px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #012754; color: white; }
        .btn-success { background-color: #2e7d32; color: white; }
        .btn-warning { background-color: #ff9800; color: white; }
        .btn-sm { padding: 8px 14px; font-size: 12px; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        /* Order Cards Grid */
        .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        
        .order-card { background-color: white; border-radius: 12px; overflow: hidden; border: 1px solid #eee; }
        .order-card-header { padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .order-card-header.cooking { background-color: #e3f2fd; border-bottom: 2px solid #1565c0; }
        .order-card-header.ready { background-color: #e8f5e9; border-bottom: 2px solid #2e7d32; }
        .order-card-header h3 { font-size: 18px; color: #333; }
        .order-timer { padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .timer-normal { background-color: #e8f5e9; color: #2e7d32; }
        .timer-warning { background-color: #fff3e0; color: #ef6c00; }
        .timer-danger { background-color: #ffebee; color: #c62828; }
        
        .order-card-info { padding: 10px 20px; background-color: #f8f9fa; font-size: 13px; color: #666; display: flex; gap: 15px; border-bottom: 1px solid #eee; }
        
        .order-card-items { padding: 15px 20px; }
        .order-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .order-item:last-child { border-bottom: none; }
        .order-item-info { display: flex; align-items: center; gap: 12px; }
        .order-item-qty { background-color: #012754; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 13px; font-weight: bold; }
        .order-item-name { font-size: 15px; color: #333; }
        
        .item-status { padding: 5px 12px; border-radius: 15px; font-size: 11px; font-weight: bold; cursor: pointer; border: none; }
        .item-cooking { background-color: #e3f2fd; color: #1565c0; }
        .item-cooking:hover { background-color: #1565c0; color: white; }
        .item-ready { background-color: #e8f5e9; color: #2e7d32; }
        
        .order-card-actions { padding: 15px 20px; background-color: #f8f9fa; display: flex; gap: 10px; border-top: 1px solid #eee; }
        .order-card-actions .btn { flex: 1; text-align: center; }
        
        .empty-message { text-align: center; color: #666; padding: 60px; }
        .empty-message .empty-icon { font-size: 64px; margin-bottom: 20px; }
        .empty-message h3 { margin-bottom: 10px; color: #333; }
        
        /* Refresh Button */
        .refresh-btn { position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; background-color: #012754; color: white; border: none; border-radius: 50%; font-size: 24px; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .refresh-btn:hover { background-color: #011c3d; }
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
            <a href="new_order.php"><span class="icon">➕</span> New Order</a>
            <a href="orders.php"><span class="icon">📋</span> Orders</a>
            <a href="kitchen.php" class="active"><span class="icon">👨‍🍳</span> Kitchen</a>
            <a href="billing.php"><span class="icon">💰</span> Billing</a>
            <a href="customers.php"><span class="icon">👤</span> Customers</a>
            <a href="reservations.php"><span class="icon">🎫</span> Reservations</a>
            <a href="reports.php"><span class="icon">📈</span> Reports</a>
            <a href="logout.php"><span class="icon">🚪</span> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div class="welcome-text">👨‍🍳 Kitchen Display</div>
            <div class="top-header-right">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="content-area">
            <?php if ($success != ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error != ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card cooking">
                    <div class="stat-icon">🍳</div>
                    <h3><?php echo $cookingCount; ?></h3>
                    <p>Cooking</p>
                </div>
                <div class="stat-card ready">
                    <div class="stat-icon">✅</div>
                    <h3><?php echo $readyCount; ?></h3>
                    <p>Ready to Serve</p>
                </div>
                <div class="stat-card total">
                    <div class="stat-icon">📦</div>
                    <h3><?php echo count($activeOrders); ?></h3>
                    <p>Active Orders</p>
                </div>
            </div>

            <!-- Cooking Orders -->
            <div class="card">
                <h2>🔥 Cooking Orders (<?php echo count($cookingOrders); ?>)</h2>
                <?php if (count($cookingOrders) > 0): ?>
                <div class="orders-grid">
                    <?php foreach ($cookingOrders as $order): ?>
                    <div class="order-card">
                        <div class="order-card-header cooking">
                            <h3>🍽️ Table <?php echo $order['table_number'] ? $order['table_number'] : 'N/A'; ?></h3>
                            <span class="order-timer timer-normal" id="timer-<?php echo $order['id']; ?>">
                                <?php 
                                $created = strtotime($order['created_at']);
                                $mins = floor((time() - $created) / 60);
                                echo $mins . ' min';
                                ?>
                            </span>
                        </div>
                        <div class="order-card-info">
                            <span>📋 Order #<?php echo $order['id']; ?></span>
                            <span>🏷️ <?php echo ucfirst($order['order_type']); ?></span>
                        </div>
                        <div class="order-card-items">
                            <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <div class="order-item-info">
                                    <span class="order-item-qty"><?php echo $item['quantity']; ?></span>
                                    <span class="order-item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                </div>
                                <?php if ($item['status'] == 'cooking'): ?>
                                <form method="POST" style="display:inline;">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="mark_item_ready">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="item-status item-cooking">Cooking →</button>
                                </form>
                                <?php else: ?>
                                <span class="item-status item-ready">✓ Ready</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-card-actions">
                            <form method="POST" style="flex:1;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="mark_all_ready">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" class="btn btn-success" style="width:100%;">✓ All Ready</button>
                            </form>
                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="flex:1; text-align:center;">👁️ View</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-message">
                    <div class="empty-icon">☕</div>
                    <h3>No Cooking Orders</h3>
                    <p>Waiting for new orders...</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ready Orders -->
            <?php if (count($readyOrders) > 0): ?>
            <div class="card">
                <h2>✅ Ready to Serve (<?php echo count($readyOrders); ?>)</h2>
                <div class="orders-grid">
                    <?php foreach ($readyOrders as $order): ?>
                    <div class="order-card">
                        <div class="order-card-header ready">
                            <h3>🍽️ Table <?php echo $order['table_number'] ? $order['table_number'] : 'N/A'; ?></h3>
                            <span class="order-timer timer-normal">Ready!</span>
                        </div>
                        <div class="order-card-info">
                            <span>📋 Order #<?php echo $order['id']; ?></span>
                            <span>🏷️ <?php echo ucfirst($order['order_type']); ?></span>
                        </div>
                        <div class="order-card-items">
                            <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <div class="order-item-info">
                                    <span class="order-item-qty"><?php echo $item['quantity']; ?></span>
                                    <span class="order-item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                </div>
                                <span class="item-status item-ready">✓ Ready</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-card-actions">
                            <form method="POST" style="flex:1;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="mark_served">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" class="btn btn-success" style="width:100%;">🍽️ Mark Served</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <button class="refresh-btn" onclick="location.reload();" title="Refresh">🔄</button>

    <script>
    // Auto refresh every 30 seconds
    setTimeout(function() {
        location.reload();
    }, 30000);
    </script>
</body>
</html>