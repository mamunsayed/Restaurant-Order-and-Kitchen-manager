<?php
$pageTitle = 'All Orders';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Order.php';

requireLogin();
requireRole(['admin', 'manager', 'cashier']);

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
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/orders.css">
</head> <body> <div class="sidebar"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"><span class="icon"></span> Dashboard</a> <a href="staff.php"><span class="icon"></span> Staff</a> <a href="tables.php"><span class="icon"></span> Tables</a> <a href="categories.php"><span class="icon"></span> Categories</a> <a href="menu_items.php"><span class="icon"></span> Menu Items</a> <a href="new_order.php"><span class="icon"></span> New Order</a> <a href="orders.php" class="active"><span class="icon"></span> Orders</a> <a href="kitchen.php"><span class="icon"></span> Kitchen</a> <a href="billing.php"><span class="icon"></span> Billing</a> <a href="customers.php"><span class="icon"></span> Customers</a> <a href="reservations.php"><span class="icon"></span> Reservations</a> <a href="reports.php"><span class="icon"></span> Reports</a> <a href="logout.php"><span class="icon"></span> Logout</a> </div> </div> <div class="main-content"> <div class="top-header"> <div class="welcome-text">Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span></div> <div class="top-header-right"> <a href="new_order.php" class="btn btn-primary"> New Order</a> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> </div> <div class="content-area"> <?php if ($success != ''): ?> <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?> <?php if ($error != ''): ?> <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <!-- Stats --> <div class="stats-row"> <div class="stat-card"> <div class="stat-icon"></div> <h3><?php echo $stats['today_orders']; ?></h3> <p>Today's Orders</p> </div> <div class="stat-card"> <div class="stat-icon"></div> <h3><?php echo $stats['active_orders']; ?></h3> <p>Active Orders</p> </div> <div class="stat-card"> <div class="stat-icon"></div> <h3><?php echo $stats['completed_today']; ?></h3> <p>Completed Today</p> </div> <div class="stat-card"> <div class="stat-icon"></div> <h3>$<?php echo number_format($stats['today_revenue'], 2); ?></h3> <p>Today's Revenue</p> </div> </div> <!-- Orders Table --> <div class="card"> <div class="card-header"> <h2> All Orders</h2> <div class="filter-tabs"> <a href="orders.php" class="filter-tab <?php echo $statusFilter == '' ? 'active' : ''; ?>">All</a> <a href="orders.php?status=active" class="filter-tab <?php echo $statusFilter == 'active' ? 'active' : ''; ?>">Active</a> <a href="orders.php?status=in-kitchen" class="filter-tab <?php echo $statusFilter == 'in-kitchen' ? 'active' : ''; ?>">In Kitchen</a> <a href="orders.php?status=ready" class="filter-tab <?php echo $statusFilter == 'ready' ? 'active' : ''; ?>">Ready</a> <a href="orders.php?status=completed" class="filter-tab <?php echo $statusFilter == 'completed' ? 'active' : ''; ?>">Completed</a> <a href="orders.php?status=cancelled" class="filter-tab <?php echo $statusFilter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a> </div> </div> <?php if (count($orders) > 0): ?> <table> <thead> <tr> <th>Order #</th> <th>Table</th> <th>Type</th> <th>Server</th> <th>Items</th> <th>Total</th> <th>Status</th> <th>Time</th> <th>Actions</th> </tr> </thead> <tbody> <?php foreach ($orders as $order): ?> <tr> <td><strong>#<?php echo $order['id']; ?></strong></td> <td><?php echo $order['table_number'] ? 'Table ' . $order['table_number'] : '-'; ?></td> <td><span class="order-type"><?php echo ucfirst($order['order_type']); ?></span></td> <td><?php echo htmlspecialchars($order['server_name']); ?></td> <td> <?php 
                                $itemCount = 0;
                                $orderItems = (new Order())->getOrderItems($order['id']);
                                foreach($orderItems as $item) {
                                    $itemCount += $item['quantity'];
                                }
                                echo $itemCount . ' items';
                                ?> </td> <td><span class="price">$<?php echo number_format($order['total'], 2); ?></span></td> <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst(str_replace('-', ' ', $order['status'])); ?></span></td> <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td> <td class="action-buttons"> <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">View</a> <?php if ($order['status'] == 'completed'): ?> <a href="billing.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success btn-sm">Bill</a> <?php endif; ?> </td> </tr> <?php endforeach; ?> </tbody> </table> <?php else: ?> <div class="empty-message"> <div class="empty-icon"></div> <p>No orders found.</p> </div> <?php endif; ?> </div> </div> </div> </body> </html>