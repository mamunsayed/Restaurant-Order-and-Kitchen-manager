<?php
// Get current page name
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">R</div>
        <h2>Restaurant</h2>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        
        <?php if (hasRole(['admin', 'manager'])): ?>
        <a href="staff.php" class="<?php echo $currentPage === 'staff' ? 'active' : ''; ?>">
            <span class="icon">👥</span> Staff
        </a>
        <a href="tables.php" class="<?php echo $currentPage === 'tables' ? 'active' : ''; ?>">
            <span class="icon">🪑</span> Tables
        </a>
        <?php endif; ?>
        
        <?php if (hasRole(['admin', 'manager'])): ?>
        <a href="categories.php" class="<?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
            <span class="icon">📁</span> Categories
        </a>
        <a href="menu_items.php" class="<?php echo $currentPage === 'menu_items' ? 'active' : ''; ?>">
            <span class="icon">🍔</span> Menu Items
        </a>
        <?php endif; ?>
        
        <?php if (hasRole(['admin', 'manager', 'server'])): ?>
        <a href="new_order.php" class="<?php echo $currentPage === 'new_order' ? 'active' : ''; ?>">
            <span class="icon">➕</span> New Order
        </a>
        <a href="orders.php" class="<?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
            <span class="icon">📋</span> Orders
        </a>
        <?php endif; ?>
        
        <a href="order_history.php" class="<?php echo $currentPage === 'order_history' ? 'active' : ''; ?>">
            <span class="icon">📜</span> Order History
        </a>
        
        <?php if (hasRole(['admin', 'manager', 'kitchen'])): ?>
        <a href="kitchen.php" class="<?php echo $currentPage === 'kitchen' ? 'active' : ''; ?>">
            <span class="icon">👨‍🍳</span> Kitchen
        </a>
        <?php endif; ?>
        
        <?php if (hasRole(['admin', 'manager', 'server'])): ?>
        <a href="billing.php" class="<?php echo $currentPage === 'billing' ? 'active' : ''; ?>">
            <span class="icon">💰</span> Billing
        </a>
        <?php endif; ?>
        
        <?php if (hasRole(['admin', 'manager'])): ?>
        <a href="customers.php" class="<?php echo $currentPage === 'customers' ? 'active' : ''; ?>">
            <span class="icon">👤</span> Customers
        </a>
        <a href="reservations.php" class="<?php echo $currentPage === 'reservations' ? 'active' : ''; ?>">
            <span class="icon">🎫</span> Reservations
        </a>
        <a href="reports.php" class="<?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
            <span class="icon">📈</span> Reports
        </a>
        <?php endif; ?>
        
        <a href="logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <span class="icon">🚪</span> Logout
        </a>
    </div>
</div>