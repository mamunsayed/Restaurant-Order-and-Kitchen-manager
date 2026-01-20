<?php
$pageTitle = 'Categories';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Category.php';

// Check login & role
requireLogin();
requireRole(['admin', 'manager']);

$categoryModel = new Category();
$error = '';
$success = '';

// Handle form submission
if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Create category
        if ($action === 'create') {
            $data = array(
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
'status' => $_POST['status'] ?? 'active'
            );
            
            if (empty($data['name'])) {
                $error = 'Category name is required';
            } else {
                $result = $categoryModel->create($data);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        // Update category
        if ($action === 'update') {
            $id = $_POST['id'] ?? 0;
            $data = array(
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
'status' => $_POST['status'] ?? 'active'
            );
            
            if (empty($data['name'])) {
                $error = 'Category name is required';
            } else {
                $result = $categoryModel->update($id, $data);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        // Delete category
        if ($action === 'delete') {
            $id = $_POST['id'] ?? 0;
            $result = $categoryModel->delete($id);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $editCategory = $categoryModel->getById($_GET['edit']);
}

// Get all categories
$categories = $categoryModel->getAll();

// Get current user
$currentUser = getCurrentUser();
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/categories.css">
</head> <body> <div class="sidebar"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"> <span class="icon"></span> Dashboard
            </a> <a href="staff.php"> <span class="icon"></span> Staff
            </a> <a href="tables.php"> <span class="icon"></span> Tables
            </a> <a href="categories.php" class="active"> <span class="icon"></span> Categories
            </a> <a href="menu_items.php"> <span class="icon"></span> Menu Items
            </a> <a href="new_order.php"> <span class="icon"></span> New Order
            </a> <a href="orders.php"> <span class="icon"></span> Orders
            </a> <a href="order_history.php"> <span class="icon"></span> Order History
            </a> <a href="kitchen.php"> <span class="icon"></span> Kitchen
            </a> <a href="billing.php"> <span class="icon"></span> Billing
            </a> <a href="customers.php"> <span class="icon"></span> Customers
            </a> <a href="reservations.php"> <span class="icon"></span> Reservations
            </a> <a href="reports.php"> <span class="icon"></span> Reports
            </a> <a href="logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;"> <span class="icon"></span> Logout
            </a> </div> </div> <div class="main-content"> <div class="top-header"> <div class="welcome-text">Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span></div> <div class="top-header-right"> <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> </div> <div class="content-area"> <?php if ($success): ?> <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?> <?php if ($error): ?> <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <!-- Add/Edit Category Form --> <div class="card"> <h2><?php echo $editCategory ? ' Edit Category' : ' Add New Category'; ?></h2> <form method="POST" action="" id="categoryForm"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="<?php echo $editCategory ? 'update' : 'create'; ?>"> <?php if ($editCategory): ?> <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>"> <?php endif; ?> <div class="form-group"> <label for="name">Category Name *</label> <input type="text" id="name" name="name" 
                               value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>" 
                               placeholder="Enter category name" required> </div> <div class="form-group"> <label for="description">Description (Optional)</label> <textarea id="description" name="description" 
                                  placeholder="Enter description"><?php echo $editCategory ? htmlspecialchars($editCategory['description']) : ''; ?></textarea> </div>  <div class="form-group"> <label for="status">Status</label> <select id="status" name="status"> <option value="active" <?php echo ($editCategory && $editCategory['status'] === 'active') ? 'selected' : ''; ?>>Active</option> <option value="inactive" <?php echo ($editCategory && $editCategory['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option> </select> </div> <div class="form-buttons"> <button type="submit" class="btn btn-primary"> <?php echo $editCategory ? 'Update Category' : 'Add Category'; ?> </button> <?php if ($editCategory): ?> <a href="categories.php" class="btn btn-secondary">Cancel</a> <?php endif; ?> </div> </form> </div> <!-- Categories Table --> <div class="card"> <h2> All Categories</h2> <?php if (count($categories) > 0): ?> <table> <thead> <tr> <th>Name</th> <th>Description</th> <th>Status</th> <th>Actions</th> </tr> </thead> <tbody> <?php foreach ($categories as $category): ?> <tr> <td><?php echo htmlspecialchars($category['name']); ?></td> <td><?php echo htmlspecialchars($category['description'] ?: '-'); ?></td> <td> <span class="badge <?php echo $category['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>"> <?php echo ucfirst($category['status']); ?> </span> </td> <td class="action-buttons"> <a href="categories.php?edit=<?php echo $category['id']; ?>" class="btn btn-edit btn-sm">Edit</a> <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="delete"> <input type="hidden" name="id" value="<?php echo $category['id']; ?>"> <button type="submit" class="btn btn-danger btn-sm">Delete</button> </form> </td> </tr> <?php endforeach; ?> </tbody> </table> <?php else: ?> <div class="empty-message"> <div class="empty-icon"></div> <p>No categories found. Add your first category above.</p> </div> <?php endif; ?> </div> </div> </div>  <script src="../asset/js/ajax.js"></script>
<script src="../asset/js/categories.js"></script>
</body> </html>