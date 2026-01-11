<?php
$pageTitle = 'Menu Items';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Category.php';
require_once $basePath . '/model/MenuItem.php';

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
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/menu_items.css">
</head> <body> <div class="sidebar"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"> <span class="icon"></span> Dashboard
            </a> <a href="staff.php"> <span class="icon"></span> Staff
            </a> <a href="tables.php"> <span class="icon"></span> Tables
            </a> <a href="categories.php"> <span class="icon"></span> Categories
            </a> <a href="menu_items.php" class="active"> <span class="icon"></span> Menu Items
            </a> <a href="new_order.php"> <span class="icon"></span> New Order
            </a> <a href="orders.php"> <span class="icon"></span> Orders
            </a> <a href="order_history.php"> <span class="icon"></span> Order History
            </a> <a href="kitchen.php"> <span class="icon"></span> Kitchen
            </a> <a href="billing.php"> <span class="icon"></span> Billing
            </a> <a href="customers.php"> <span class="icon"></span> Customers
            </a> <a href="reservations.php"> <span class="icon"></span> Reservations
            </a> <a href="reports.php"> <span class="icon"></span> Reports
            </a> <a href="logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;"> <span class="icon"></span> Logout
            </a> </div> </div> <div class="main-content"> <div class="top-header"> <div class="welcome-text">Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span></div> <div class="top-header-right"> <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> </div> <div class="content-area"> <?php if ($success): ?> <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?> <?php if ($error): ?> <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <?php if (count($categories) === 0): ?> <div class="warning-box"> ⚠ No categories found! Please <a href="categories.php">add categories</a> first before adding menu items.
                </div> <?php else: ?> <!-- Add/Edit Menu Item Form --> <div class="card"> <h2><?php echo $editItem ? ' Edit Menu Item' : ' Add New Menu Item'; ?></h2> <form method="POST" action="" id="menuItemForm"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="<?php echo $editItem ? 'update' : 'create'; ?>"> <?php if ($editItem): ?> <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>"> <?php endif; ?> <div class="form-row"> <div class="form-group"> <label for="name">Item Name *</label> <input type="text" id="name" name="name" 
                                   value="<?php echo $editItem ? htmlspecialchars($editItem['name']) : ''; ?>" 
                                   placeholder="Enter item name" required> </div> <div class="form-group"> <label for="category_id">Category *</label> <select id="category_id" name="category_id" required> <option value="">-- Select Category --</option> <?php foreach ($categories as $category): ?> <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($editItem && $editItem['category_id'] == $category['id']) ? 'selected' : ''; ?>> <?php echo htmlspecialchars($category['name']); ?> </option> <?php endforeach; ?> </select> </div> </div> <div class="form-row"> <div class="form-group"> <label for="price">Price ($) *</label> <input type="number" id="price" name="price" step="0.01" min="0.01"
                                   value="<?php echo $editItem ? $editItem['price'] : ''; ?>" 
                                   placeholder="Enter price" required> </div> <div class="form-group"> <label for="status">Status</label> <select id="status" name="status"> <option value="available" <?php echo ($editItem && $editItem['status'] === 'available') ? 'selected' : ''; ?>>Available</option> <option value="unavailable" <?php echo ($editItem && $editItem['status'] === 'unavailable') ? 'selected' : ''; ?>>Unavailable</option> </select> </div> </div> <div class="form-group"> <label for="description">Description (Optional)</label> <textarea id="description" name="description" 
                                  placeholder="Enter item description"><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea> </div> <div class="form-buttons"> <button type="submit" class="btn btn-primary"> <?php echo $editItem ? 'Update Item' : 'Add Item'; ?> </button> <?php if ($editItem): ?> <a href="menu_items.php" class="btn btn-secondary">Cancel</a> <?php endif; ?> </div> </form> </div> <?php endif; ?> <!-- Menu Items Table --> <div class="card"> <h2> All Menu Items (<?php echo count($menuItems); ?>)</h2> <?php if (count($menuItems) > 0): ?> <table> <thead> <tr> <th>Name</th> <th>Category</th> <th>Price</th> <th>Status</th> <th>Actions</th> </tr> </thead> <tbody> <?php foreach ($menuItems as $item): ?> <tr> <td> <strong><?php echo htmlspecialchars($item['name']); ?></strong> <?php if ($item['description']): ?> <br><small style="color: #666;"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</small> <?php endif; ?> </td> <td> <span class="category-badge"><?php echo htmlspecialchars($item['category_name']); ?></span> </td> <td> <span class="price">$<?php echo number_format($item['price'], 2); ?></span> </td> <td> <span class="badge <?php echo $item['status'] === 'available' ? 'badge-success' : 'badge-danger'; ?>"> <?php echo ucfirst($item['status']); ?> </span> </td> <td class="action-buttons"> <a href="menu_items.php?edit=<?php echo $item['id']; ?>" class="btn btn-edit btn-sm">Edit</a> <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="delete"> <input type="hidden" name="id" value="<?php echo $item['id']; ?>"> <button type="submit" class="btn btn-danger btn-sm">Delete</button> </form> </td> </tr> <?php endforeach; ?> </tbody> </table> <?php else: ?> <div class="empty-message"> <div class="empty-icon"></div> <p>No menu items found. Add your first item above.</p> </div> <?php endif; ?> </div> </div> </div>  <script src="../asset/js/common.js"></script>
<script src="../asset/js/ajax.js"></script>
<script src="../asset/js/menu_items.js"></script>
</body> </html>