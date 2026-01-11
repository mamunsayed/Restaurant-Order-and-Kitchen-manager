<?php
$pageTitle = 'Order History';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Order.php';

requireLogin();
requireRole(['admin', 'manager', 'cashier']);

$orderModel = new Order();

// Optional filter (history usually = completed + cancelled)
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

if ($statusFilter) {
    $orders = $orderModel->getByStatus($statusFilter);
} else {
    $completed = $orderModel->getByStatus('completed');
    $cancelled = $orderModel->getByStatus('cancelled');
    $orders = array_merge($completed, $cancelled);

    // Sort newest first (array_merge preserves original ordering per query)
    usort($orders, function ($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
}

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
    <link rel="stylesheet" href="../asset/css/orders.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-header">
            <div class="welcome-text">Order History</div>
            <div class="top-header-right">
                <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div>
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

            <div class="card">
                <div class="card-header">
                    <h2>Order History</h2>
                    <div class="filter-tabs">
                        <a href="order_history.php" class="filter-tab <?php echo $statusFilter == '' ? 'active' : ''; ?>">All History</a>
                        <a href="order_history.php?status=completed" class="filter-tab <?php echo $statusFilter == 'completed' ? 'active' : ''; ?>">Completed</a>
                        <a href="order_history.php?status=cancelled" class="filter-tab <?php echo $statusFilter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
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
                                            foreach ($orderItems as $item) {
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
                        <div class="empty-icon"></div>
                        <p>No order history found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
