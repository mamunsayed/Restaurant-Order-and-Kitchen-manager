<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../model/config/database.php';

// Get statistics
$conn = getConnection();

// Total Orders Today
$todayOrders = 0;
$sql = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $todayOrders = $row['count'];
}

// Total Revenue Today
$todayRevenue = 0;
$sql = "SELECT SUM(total) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed'";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $todayRevenue = $row['total'] ? $row['total'] : 0;
}

// Active Orders
$activeOrders = 0;
$sql = "SELECT COUNT(*) as count FROM orders WHERE status IN ('active', 'in-kitchen')";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $activeOrders = $row['count'];
}

// Total Menu Items
$totalItems = 0;
$sql = "SELECT COUNT(*) as count FROM menu_items WHERE status = 'available'";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $totalItems = $row['count'];
}

// Available Tables
$availableTables = 0;
$sql = "SELECT COUNT(*) as count FROM tables WHERE status = 'available'";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $availableTables = $row['count'];
}

// Total Staff (active users)
$totalStaff = 0;
$sql = "SELECT COUNT(*) as count FROM users WHERE status = 'active'";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $totalStaff = $row['count'];
}

// Recent Orders
$recentOrders = array();
$sql = "SELECT o.*, t.table_number
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.id
        ORDER BY o.created_at DESC
        LIMIT 6";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}

$conn->close();
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="content-area">

        <!-- Stats Cards (Grid View) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $todayOrders; ?></h3>
                    <p>Today's Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>$<?php echo number_format($todayRevenue, 2); ?></h3>
                    <p>Today's Revenue</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $activeOrders; ?></h3>
                    <p>Active Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $totalItems; ?></h3>
                    <p>Menu Items</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $availableTables; ?></h3>
                    <p>Available Tables</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $totalStaff; ?></h3>
                    <p>Total Staff</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h2>Quick Actions</h2>

            <div class="quick-actions">
                <?php if (hasRole(['admin', 'manager', 'cashier'])): ?>
                    <a href="new_order.php" class="action-btn">
                        <span>New Order</span>
                    </a>
                <?php endif; ?>

                <?php if (hasRole(['admin', 'manager'])): ?>
                    <a href="kitchen.php" class="action-btn">
                        <span>Kitchen</span>
                    </a>
                <?php endif; ?>

                <?php if (hasRole(['admin', 'manager'])): ?>
                    <a href="menu_items.php" class="action-btn">
                        <span>Menu Items</span>
                    </a>
                    <a href="tables.php" class="action-btn">
                        <span>Tables</span>
                    </a>
                    <a href="reports.php" class="action-btn">
                        <span>Reports</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Orders (Grid View) -->
        <div class="card">
            <h2>Recent Orders</h2>

            <?php if (count($recentOrders) > 0): ?>
                <div class="recent-orders-grid">
                    <?php foreach ($recentOrders as $order): ?>
                        <?php
                        $badgeClass = $order['status'] === 'completed'
                            ? 'success'
                            : ($order['status'] === 'active'
                                ? 'info'
                                : ($order['status'] === 'in-kitchen' ? 'warning' : 'danger'));
                        ?>
                        <div class="order-card">
                            <div class="order-card-top">
                                <div class="order-id">#<?php echo $order['id']; ?></div>
                                <div class="badge badge-<?php echo $badgeClass; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </div>
                            </div>

                            <div class="order-meta">
                                <div><strong>Table:</strong> <?php echo $order['table_number'] ? 'Table ' . $order['table_number'] : 'N/A'; ?></div>
                                <div><strong>Type:</strong> <?php echo ucfirst($order['order_type']); ?></div>
                                <div><strong>Total:</strong> $<?php echo number_format((float)$order['total'], 2); ?></div>
                                <div><strong>Time:</strong> <?php echo date('h:i A', strtotime($order['created_at'])); ?></div>
                            </div>

                            <div class="order-card-actions">
                                <a class="link-btn" href="order_details.php?id=<?php echo $order['id']; ?>">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-message">
                    <p>No orders yet today</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
