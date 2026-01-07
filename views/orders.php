<?php
$pageTitle = 'All Orders';
$basePath = dirname(__DIR__);

require_once $basePath . '/config/session.php';
require_once $basePath . '/config/security.php';
require_once $basePath . '/models/Order.php';

requireLogin();
requireRole(['admin', 'manager', 'server']);

$orderModel = new Order();

// Get filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Get orders
if ($statusFilter) {
    $orders = $orderModel->getByStatus($statusFilter);
} else {
    $orders = $orderModel->getAll();
}

// Get stats
$stats = $orderModel->getStats();

// Success/Error messages
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

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
        
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background-color: white; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #eee; }
        .stat-card .stat-icon { font-size: 28px; margin-bottom: 10px; }
        .stat-card h3 { font-size: 24px; margin-bottom: 5px; color: #012754; }
        .stat-card p { color: #666; font-size: 13px; }
        
        .card { background-color: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #eee; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .card-header h2 { color: #012754; font-size: 18px; }
        
        .filter-tabs { display: flex; gap: 10px; }
        .filter-tab { padding: 8px 16px; border: 2px solid #ddd; background-color: white; border-radius: 20px; cursor: pointer; font-size: 13px; text-decoration: none; color: #333; }
        .filter-tab:hover { border-color: #012754; }
        .filter-tab.active { background-color: #012754; color: white; border-color: #012754; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #012754; color: white; }
        .btn-success { background-color: #2e7d32; color: white; }
        .btn-warning { background-color: #ff9800; color: white; }
        .btn-info { background-color: #1976D2; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background-color: #f8f9fa; color: #012754; font-size: 14px; }
        table tr:hover { background-color: #f8f9fa; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .badge-active { background-color: #e3f2fd; color: #1565c0; }
        .badge-in-kitchen { background-color: #fff3e0; color: #ef6c00; }
        .badge-ready { background-color: #e8f5e9; color: #2e7d32; }
        .badge-completed { background-color: #f3e5f5; color: #7b1fa2; }
        .badge-cancelled { background-color: #ffebee; color: #c62828; }
        
        .order-type { padding: 4px 10px; border-radius: 15px; font-size: 11px; background-color: #f0f0f0; }
        
        .empty-message { text-align: center; color: #666; padding: 40px; }
        .empty-message .empty-icon { font-size: 48px; margin-bottom: 15px; }
        
        .action-buttons { display: flex; gap: 8px; }
        
        .price { color: #2e7d32; font-weight: bold; }
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
            <a href="orders.php" class="active"><span class="icon">📋</span> Orders</a>
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
                <a href="new_order.php" class="btn btn-primary">➕ New Order</a>
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
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <h3><?php echo $stats['today_orders']; ?></h3>
                    <p>Today's Orders</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔥</div>
                    <h3><?php echo $stats['active_orders']; ?></h3>
                    <p>Active Orders</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <h3><?php echo $stats['completed_today']; ?></h3>
                    <p>Completed Today</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <h3>$<?php echo number_format($stats['today_revenue'], 2); ?></h3>
                    <p>Today's Revenue</p>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-header">
                    <h2>📋 All Orders</h2>
                    <div class="filter-tabs">
                        <a href="orders.php" class="filter-tab <?php echo $statusFilter == '' ? 'active' : ''; ?>">All</a>
                        <a href="orders.php?status=active" class="filter-tab <?php echo $statusFilter == 'active' ? 'active' : ''; ?>">Active</a>
                        <a href="orders.php?status=in-kitchen" class="filter-tab <?php echo $statusFilter == 'in-kitchen' ? 'active' : ''; ?>">In Kitchen</a>
                        <a href="orders.php?status=ready" class="filter-tab <?php echo $statusFilter == 'ready' ? 'active' : ''; ?>">Ready</a>
                        <a href="orders.php?status=completed" class="filter-tab <?php echo $statusFilter == 'completed' ? 'active' : ''; ?>">Completed</a>
                        <a href="orders.php?status=cancelled" class="filter-tab <?php echo $statusFilter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                    </div>
                </div>

                <?php if (count($orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Table</th>
                            <th>Type</th>
                            <th>Server</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td><?php echo $order['table_number'] ? 'Table ' . $order['table_number'] : '-'; ?></td>
                            <td><span class="order-type"><?php echo ucfirst($order['order_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($order['server_name']); ?></td>
                            <td>
                                <?php 
                                $itemCount = 0;
                                $orderItems = (new Order())->getOrderItems($order['id']);
                                foreach($orderItems as $item) {
                                    $itemCount += $item['quantity'];
                                }
                                echo $itemCount . ' items';
                                ?>
                            </td>
                            <td><span class="price">$<?php echo number_format($order['total'], 2); ?></span></td>
                            <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst(str_replace('-', ' ', $order['status'])); ?></span></td>
                            <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">View</a>
                                <?php if ($order['status'] == 'completed'): ?>
                                <a href="billing.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success btn-sm">Bill</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-message">
                    <div class="empty-icon">📋</div>
                    <p>No orders found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>