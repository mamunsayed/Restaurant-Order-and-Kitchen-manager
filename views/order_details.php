<?php
$pageTitle = 'Order Details';
$basePath = dirname(__DIR__);

require_once $basePath . '/config/session.php';
require_once $basePath . '/config/security.php';
require_once $basePath . '/models/Order.php';
require_once $basePath . '/models/Category.php';
require_once $basePath . '/models/MenuItem.php';

requireLogin();
requireRole(['admin', 'manager', 'server']);

$orderModel = new Order();
$categoryModel = new Category();
$menuItemModel = new MenuItem();

$error = '';
$success = '';

// Get order ID
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId == 0) {
    header("Location: new_order.php");
    exit();
}

// Handle actions
if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'add_item') {
            $menuItemId = intval($_POST['menu_item_id']);
            $quantity = intval($_POST['quantity']);
            $result = $orderModel->addItem($orderId, $menuItemId, $quantity);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
        
        if ($action === 'update_quantity') {
            $itemId = intval($_POST['item_id']);
            $quantity = intval($_POST['quantity']);
            $result = $orderModel->updateItemQuantity($itemId, $quantity);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
        
        if ($action === 'remove_item') {
            $itemId = intval($_POST['item_id']);
            $result = $orderModel->removeItem($itemId);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
        
        if ($action === 'send_to_kitchen') {
            $result = $orderModel->sendToKitchen($orderId);
            if ($result['success']) {
                header("Location: kitchen.php");
                exit();
            } else {
                $error = $result['error'];
            }
        }
        
        if ($action === 'complete_order') {
            $result = $orderModel->completeOrder($orderId);
            if ($result['success']) {
                header("Location: orders.php?success=Order completed");
                exit();
            } else {
                $error = $result['error'];
            }
        }
        
        if ($action === 'cancel_order') {
            $result = $orderModel->cancelOrder($orderId);
            if ($result['success']) {
                header("Location: orders.php?success=Order cancelled");
                exit();
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Get order
$order = $orderModel->getById($orderId);

if (!$order) {
    header("Location: new_order.php");
    exit();
}

// Get categories and menu items
$categories = $categoryModel->getActive();
$menuItems = $menuItemModel->getAvailable();

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
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #012754; color: white; }
        .btn-success { background-color: #2e7d32; color: white; }
        .btn-danger { background-color: #d32f2f; color: white; }
        .btn-warning { background-color: #ff9800; color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        /* Order Info */
        .order-info { display: flex; gap: 30px; flex-wrap: wrap; margin-bottom: 10px; }
        .order-info-item { display: flex; flex-direction: column; }
        .order-info-item label { font-size: 12px; color: #666; margin-bottom: 5px; text-transform: uppercase; }
        .order-info-item span { font-size: 16px; font-weight: bold; color: #333; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-active { background-color: #e3f2fd; color: #1565c0; }
        .badge-in-kitchen { background-color: #fff3e0; color: #ef6c00; }
        .badge-ready { background-color: #e8f5e9; color: #2e7d32; }
        
        /* Menu Grid */
        .category-filter { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .filter-btn { padding: 10px 18px; border: 2px solid #ddd; background-color: #f9f9f9; border-radius: 25px; cursor: pointer; font-size: 13px; font-weight: bold; }
        .filter-btn:hover { border-color: #012754; background-color: #e8eaf6; }
        .filter-btn.active { background-color: #012754; color: white; border-color: #012754; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .menu-item-card { border: 1px solid #eee; border-radius: 12px; overflow: hidden; cursor: pointer; background-color: white; transition: all 0.2s; }
        .menu-item-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-color: #012754; }
        .menu-item-image { width: 100%; height: 120px; background-color: #f5f5f5; display: flex; justify-content: center; align-items: center; font-size: 48px; }
        .menu-item-info { padding: 15px; }
        .menu-item-info h4 { font-size: 14px; color: #333; margin-bottom: 6px; }
        .menu-item-info p { font-size: 16px; color: #2e7d32; font-weight: bold; }
        
        /* Order Items */
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background-color: #f8f9fa; color: #012754; font-size: 14px; }
        .qty-controls { display: flex; align-items: center; gap: 10px; }
        .qty-btn { width: 32px; height: 32px; border: 1px solid #ddd; background-color: #f9f9f9; border-radius: 6px; cursor: pointer; font-size: 18px; }
        .qty-btn:hover { background-color: #012754; color: white; border-color: #012754; }
        .qty-value { font-size: 16px; font-weight: bold; min-width: 30px; text-align: center; }
        .btn-remove { background-color: #ffebee; color: #d32f2f; padding: 6px 12px; border: 1px solid #ffcdd2; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .btn-remove:hover { background-color: #d32f2f; color: white; }
        
        /* Order Total */
        .order-total { background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 15px; }
        .total-row.grand-total { font-size: 20px; font-weight: bold; color: #2e7d32; border-top: 2px solid #ddd; padding-top: 15px; margin-top: 10px; }
        
        /* Action Buttons */
        .action-buttons { display: flex; gap: 15px; margin-top: 25px; }
        
        .empty-message { text-align: center; color: #666; padding: 40px; }
        .empty-message .empty-icon { font-size: 48px; margin-bottom: 15px; }
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
            <a href="logout.php"><span class="icon">🚪</span> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div class="welcome-text">Order #<?php echo $order['id']; ?></div>
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

            <!-- Order Info -->
            <div class="card">
                <h2>📝 Order Information</h2>
                <div class="order-info">
                    <div class="order-info-item">
                        <label>Order ID</label>
                        <span>#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="order-info-item">
                        <label>Table</label>
                        <span><?php echo $order['table_number'] ? 'Table ' . $order['table_number'] : 'N/A'; ?></span>
                    </div>
                    <div class="order-info-item">
                        <label>Type</label>
                        <span><?php echo ucfirst($order['order_type']); ?></span>
                    </div>
                    <div class="order-info-item">
                        <label>Server</label>
                        <span><?php echo htmlspecialchars($order['server_name']); ?></span>
                    </div>
                    <div class="order-info-item">
                        <label>Status</label>
                        <span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Add Items (only if order is active) -->
            <?php if ($order['status'] == 'active'): ?>
            <div class="card">
                <h2>🍔 Add Items</h2>
                
                <div class="category-filter">
                    <button class="filter-btn active" onclick="filterMenu('all')">All</button>
                    <?php foreach ($categories as $cat): ?>
                    <button class="filter-btn" onclick="filterMenu(<?php echo $cat['id']; ?>)"><?php echo htmlspecialchars($cat['name']); ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="menu-grid" id="menuGrid">
                    <?php foreach ($menuItems as $item): ?>
                    <div class="menu-item-card" data-category="<?php echo $item['category_id']; ?>" onclick="addItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                        <div class="menu-item-image">🍽️</div>
                        <div class="menu-item-info">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>$<?php echo number_format($item['price'], 2); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Order Items -->
            <div class="card">
                <h2>🛒 Order Items (<?php echo count($order['items']); ?>)</h2>
                
                <?php if (count($order['items']) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <?php if ($order['status'] == 'active'): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <?php if ($order['status'] == 'active'): ?>
                                <div class="qty-controls">
                                    <form method="POST" style="display:inline;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                                        <button type="submit" class="qty-btn">-</button>
                                    </form>
                                    <span class="qty-value"><?php echo $item['quantity']; ?></span>
                                    <form method="POST" style="display:inline;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                        <button type="submit" class="qty-btn">+</button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <?php echo $item['quantity']; ?>
                                <?php endif; ?>
                            </td>
                            <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                            <?php if ($order['status'] == 'active'): ?>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this item?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="remove_item">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="order-total">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Tax (5%):</span>
                        <span>$<?php echo number_format($order['tax'], 2); ?></span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($order['total'], 2); ?></span>
                    </div>
                </div>

                <div class="action-buttons">
                    <?php if ($order['status'] == 'active' && count($order['items']) > 0): ?>
                    <form method="POST" style="display:inline;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="send_to_kitchen">
                        <button type="submit" class="btn btn-primary">🍳 Send to Kitchen</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] == 'ready'): ?>
                    <form method="POST" style="display:inline;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="complete_order">
                        <button type="submit" class="btn btn-success">✅ Complete Order</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if (in_array($order['status'], ['active', 'in-kitchen'])): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this order?');">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="cancel_order">
                        <button type="submit" class="btn btn-danger">❌ Cancel Order</button>
                    </form>
                    <?php endif; ?>
                    
                    <a href="billing.php?order_id=<?php echo $order['id']; ?>" class="btn btn-warning">💰 Generate Bill</a>
                </div>

                <?php else: ?>
                <div class="empty-message">
                    <div class="empty-icon">🛒</div>
                    <p>No items added yet. Click on menu items above to add them.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hidden form for adding items -->
    <form id="addItemForm" method="POST" style="display:none;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="add_item">
        <input type="hidden" name="menu_item_id" id="addItemId">
        <input type="hidden" name="quantity" value="1">
    </form>

    <script>
    function filterMenu(categoryId) {
        var btns = document.querySelectorAll('.filter-btn');
        for (var i = 0; i < btns.length; i++) {
            btns[i].classList.remove('active');
        }
        event.target.classList.add('active');

        var cards = document.querySelectorAll('.menu-item-card');
        for (var i = 0; i < cards.length; i++) {
            if (categoryId === 'all' || cards[i].getAttribute('data-category') == categoryId) {
                cards[i].style.display = 'block';
            } else {
                cards[i].style.display = 'none';
            }
        }
    }

    function addItem(itemId, itemName) {
        if (confirm('Add "' + itemName + '" to order?')) {
            document.getElementById('addItemId').value = itemId;
            document.getElementById('addItemForm').submit();
        }
    }
    </script>
</body>
</html>