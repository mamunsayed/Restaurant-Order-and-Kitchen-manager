<?php
$pageTitle = 'Menu Items';
$basePath = dirname(__DIR__);

require_once $basePath . '/config/session.php';
require_once $basePath . '/config/security.php';
require_once $basePath . '/models/Category.php';
require_once $basePath . '/models/MenuItem.php';

// Check login & role
requireLogin();
requireRole(['admin', 'manager']);

$categoryModel = new Category();
$menuItemModel = new MenuItem();

$error = '';
$success = '';

// Handle form submission
if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Create menu item
        if ($action === 'create') {
            $data = array(
                'category_id' => $_POST['category_id'] ?? 0,
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'image' => $_POST['image'] ?? '',
                'status' => $_POST['status'] ?? 'available'
            );
            
            if (empty($data['name'])) {
                $error = 'Item name is required';
            } elseif (empty($data['category_id'])) {
                $error = 'Please select a category';
            } elseif ($data['price'] <= 0) {
                $error = 'Please enter a valid price';
            } else {
                $result = $menuItemModel->create($data);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        // Update menu item
        if ($action === 'update') {
            $id = $_POST['id'] ?? 0;
            $data = array(
                'category_id' => $_POST['category_id'] ?? 0,
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'image' => $_POST['image'] ?? '',
                'status' => $_POST['status'] ?? 'available'
            );
            
            if (empty($data['name'])) {
                $error = 'Item name is required';
            } elseif (empty($data['category_id'])) {
                $error = 'Please select a category';
            } elseif ($data['price'] <= 0) {
                $error = 'Please enter a valid price';
            } else {
                $result = $menuItemModel->update($id, $data);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        // Delete menu item
        if ($action === 'delete') {
            $id = $_POST['id'] ?? 0;
            $result = $menuItemModel->delete($id);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Get item for editing
$editItem = null;
if (isset($_GET['edit'])) {
    $editItem = $menuItemModel->getById($_GET['edit']);
}

// Get all categories for dropdown
$categories = $categoryModel->getActive();

// Get all menu items
$menuItems = $menuItemModel->getAll();

// Get current user
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Restaurant Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: #012754;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            padding: 0;
            overflow-y: auto;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            padding: 25px 20px;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo-icon {
            width: 45px;
            height: 45px;
            background-color: white;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #012754;
            font-size: 20px;
            font-weight: bold;
        }

        .sidebar-logo h2 {
            font-size: 18px;
            font-weight: bold;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 14px 20px;
            font-size: 15px;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255,255,255,0.08);
            color: white;
            border-left-color: rgba(255,255,255,0.3);
        }

        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.12);
            color: white;
            border-left-color: white;
            font-weight: bold;
        }

        .sidebar-menu a .icon {
            width: 22px;
            text-align: center;
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 0;
            background-color: #f0f2f5;
            min-height: 100vh;
        }

        .top-header {
            background-color: white;
            padding: 20px 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text {
            font-size: 18px;
            color: #333;
        }

        .welcome-text span {
            font-weight: bold;
            color: #012754;
        }

        .top-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-role {
            font-size: 14px;
            color: #666;
            font-weight: 500;
            text-transform: capitalize;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: #012754;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 16px;
            font-weight: bold;
        }

        .content-area {
            padding: 30px;
        }

        /* Card */
        .card {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border: 1px solid #eee;
        }

        .card h2 {
            margin-bottom: 20px;
            color: #012754;
            font-size: 18px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f9f9f9;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #012754;
            background-color: white;
        }

        .form-group textarea {
            height: 100px;
            resize: none;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #012754;
            color: white;
        }

        .btn-primary:hover {
            background-color: #011c3d;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-danger {
            background-color: #d32f2f;
            color: white;
        }

        .btn-danger:hover {
            background-color: #b71c1c;
        }

        .btn-edit {
            background-color: #1976D2;
            color: white;
        }

        .btn-edit:hover {
            background-color: #1565C0;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #012754;
            font-size: 14px;
        }

        table tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .alert-warning {
            background-color: #fff8e1;
            color: #f57c00;
            border: 1px solid #ffcc02;
        }

        /* Status Badge */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .badge-danger {
            background-color: #ffebee;
            color: #c62828;
        }

        .badge-info {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        /* Empty Message */
        .empty-message {
            text-align: center;
            color: #666;
            padding: 40px;
            font-size: 15px;
        }

        .empty-message .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        /* Item Image */
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .no-image {
            width: 60px;
            height: 60px;
            background-color: #e0e0e0;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 11px;
            color: #666;
        }

        /* Price */
        .price {
            color: #2e7d32;
            font-weight: bold;
            font-size: 16px;
        }

        /* Category Badge */
        .category-badge {
            background-color: #e3f2fd;
            color: #012754;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        /* Warning Message */
        .warning-box {
            background-color: #fff8e1;
            border: 1px solid #ffcc02;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #856404;
            font-size: 14px;
        }

        .warning-box a {
            color: #012754;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">R</div>
            <h2>Restaurant</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php">
                <span class="icon">📊</span> Dashboard
            </a>
            <a href="staff.php">
                <span class="icon">👥</span> Staff
            </a>
            <a href="tables.php">
                <span class="icon">🪑</span> Tables
            </a>
            <a href="categories.php">
                <span class="icon">📁</span> Categories
            </a>
            <a href="menu_items.php" class="active">
                <span class="icon">🍔</span> Menu Items
            </a>
            <a href="new_order.php">
                <span class="icon">➕</span> New Order
            </a>
            <a href="orders.php">
                <span class="icon">📋</span> Orders
            </a>
            <a href="order_history.php">
                <span class="icon">📜</span> Order History
            </a>
            <a href="kitchen.php">
                <span class="icon">👨‍🍳</span> Kitchen
            </a>
            <a href="billing.php">
                <span class="icon">💰</span> Billing
            </a>
            <a href="customers.php">
                <span class="icon">👤</span> Customers
            </a>
            <a href="reservations.php">
                <span class="icon">🎫</span> Reservations
            </a>
            <a href="reports.php">
                <span class="icon">📈</span> Reports
            </a>
            <a href="logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <span class="icon">🚪</span> Logout
            </a>
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
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (count($categories) === 0): ?>
                <div class="warning-box">
                    ⚠️ No categories found! Please <a href="categories.php">add categories</a> first before adding menu items.
                </div>
            <?php else: ?>

            <!-- Add/Edit Menu Item Form -->
            <div class="card">
                <h2><?php echo $editItem ? '✏️ Edit Menu Item' : '🍔 Add New Menu Item'; ?></h2>
                <form method="POST" action="" id="menuItemForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="<?php echo $editItem ? 'update' : 'create'; ?>">
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Item Name *</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo $editItem ? htmlspecialchars($editItem['name']) : ''; ?>" 
                                   placeholder="Enter item name" required>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($editItem && $editItem['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price ($) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0.01"
                                   value="<?php echo $editItem ? $editItem['price'] : ''; ?>" 
                                   placeholder="Enter price" required>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="available" <?php echo ($editItem && $editItem['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                                <option value="unavailable" <?php echo ($editItem && $editItem['status'] === 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" 
                                  placeholder="Enter item description"><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image">Image URL (Optional)</label>
                        <input type="text" id="image" name="image" 
                               value="<?php echo $editItem ? htmlspecialchars($editItem['image']) : ''; ?>" 
                               placeholder="Enter image URL">
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editItem ? 'Update Item' : 'Add Item'; ?>
                        </button>
                        <?php if ($editItem): ?>
                            <a href="menu_items.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php endif; ?>

            <!-- Menu Items Table -->
            <div class="card">
                <h2>📋 All Menu Items (<?php echo count($menuItems); ?>)</h2>
                <?php if (count($menuItems) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menuItems as $item): ?>
                        <tr>
                            <td>
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" class="item-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">🍽️</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <?php if ($item['description']): ?>
                                    <br><small style="color: #666;"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="category-badge"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            </td>
                            <td>
                                <span class="price">$<?php echo number_format($item['price'], 2); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $item['status'] === 'available' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="menu_items.php?edit=<?php echo $item['id']; ?>" class="btn btn-edit btn-sm">Edit</a>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-message">
                    <div class="empty-icon">🍔</div>
                    <p>No menu items found. Add your first item above.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('menuItemForm')?.addEventListener('submit', function(e) {
            var name = document.getElementById('name').value.trim();
            var category = document.getElementById('category_id').value;
            var price = document.getElementById('price').value;
            
            if (name === '') {
                alert('Please enter item name');
                e.preventDefault();
                return false;
            }
            
            if (category === '') {
                alert('Please select a category');
                e.preventDefault();
                return false;
            }
            
            if (price === '' || parseFloat(price) <= 0) {
                alert('Please enter a valid price');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>