<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

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

// Total Staff
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
        LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}

$conn->close();
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>
    
    <div class="content-area">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <h3><?php echo $todayOrders; ?></h3>
                    <p>Today's Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>$<?php echo number_format($todayRevenue, 2); ?></h3>
                    <p>Today's Revenue</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <div class="stat-info">
                    <h3><?php echo $activeOrders; ?></h3>
                    <p>Active Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🍔</div>
                <div class="stat-info">
                    <h3><?php echo $totalItems; ?></h3>
                    <p>Menu Items</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🪑</div>
                <div class="stat-info">
                    <h3><?php echo $availableTables; ?></h3>
                    <p>Available Tables</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?php echo $totalStaff; ?></h3>
                    <p>Total Staff</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <h2>⚡ Quick Actions</h2>
            <div class="quick-actions">
                <?php if (hasRole(['admin', 'manager', 'server'])): ?>
                <a href="new_order.php" class="action-btn">
                    <span class="action-icon">➕</span>
                    <span>New Order</span>
                </a>
                <?php endif; ?>
                
                <?php if (hasRole(['admin', 'manager', 'kitchen'])): ?>
                <a href="kitchen.php" class="action-btn">
                    <span class="action-icon">👨‍🍳</span>
                    <span>Kitchen</span>
                </a>
                <?php endif; ?>
                
                <?php if (hasRole(['admin', 'manager'])): ?>
                <a href="menu_items.php" class="action-btn">
                    <span class="action-icon">🍔</span>
                    <span>Menu Items</span>
                </a>
                
                <a href="tables.php" class="action-btn">
                    <span class="action-icon">🪑</span>
                    <span>Tables</span>
                </a>
                
                <a href="reports.php" class="action-btn">
                    <span class="action-icon">📈</span>
                    <span>Reports</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="card">
            <h2>📋 Recent Orders</h2>
            <?php if (count($recentOrders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Table</th>
                        <th>Type</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo $order['table_number'] ? 'Table ' . $order['table_number'] : 'N/A'; ?></td>
                        <td><?php echo ucfirst($order['order_type']); ?></td>
                        <td>$<?php echo number_format($order['total'], 2); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $order['status'] === 'completed' ? 'success' : 
                                    ($order['status'] === 'active' ? 'info' : 
                                    ($order['status'] === 'in-kitchen' ? 'warning' : 'danger')); 
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-message">
                <div class="empty-icon">📋</div>
                <p>No orders yet today</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Dashboard Specific Styles */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background-color: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        gap: 20px;
        border: 1px solid #eee;
    }
    
    .stat-icon {
        font-size: 36px;
    }
    
    .stat-info h3 {
        font-size: 28px;
        color: #012754;
        margin-bottom: 5px;
    }
    
    .stat-info p {
        font-size: 14px;
        color: #666;
    }
    
    .quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 25px 30px;
        background-color: #f8f9fa;
        border-radius: 12px;
        text-decoration: none;
        color: #333;
        border: 1px solid #eee;
        min-width: 120px;
        transition: all 0.2s;
    }
    
    .action-btn:hover {
        background-color: #012754;
        color: white;
        border-color: #012754;
    }
    
    .action-icon {
        font-size: 32px;
        margin-bottom: 10px;
    }
    
    .action-btn span:last-child {
        font-size: 14px;
        font-weight: bold;
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>