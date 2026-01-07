<?php
$pageTitle = 'Customers';
$basePath = dirname(__DIR__);

require_once $basePath . '/config/session.php';
require_once $basePath . '/config/security.php';
require_once $basePath . '/models/Customer.php';

requireLogin();
requireRole(['admin', 'manager']);

$customerModel = new Customer();
$error = '';
$success = '';

if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'create') {
            $data = array(
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
                'email' => isset($_POST['email']) ? $_POST['email'] : '',
                'address' => isset($_POST['address']) ? $_POST['address'] : ''
            );
            if (empty($data['name'])) {
                $error = 'Customer name is required';
            } else {
                $result = $customerModel->create($data);
                $success = $result['success'] ? $result['message'] : '';
                $error = !$result['success'] ? $result['error'] : '';
            }
        }
        
        if ($action === 'update') {
            $id = intval($_POST['id']);
            $data = array(
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
                'email' => isset($_POST['email']) ? $_POST['email'] : '',
                'address' => isset($_POST['address']) ? $_POST['address'] : ''
            );
            $result = $customerModel->update($id, $data);
            $success = $result['success'] ? $result['message'] : '';
            $error = !$result['success'] ? $result['error'] : '';
        }
        
        if ($action === 'delete') {
            $id = intval($_POST['id']);
            $result = $customerModel->delete($id);
            $success = $result['success'] ? $result['message'] : '';
            $error = !$result['success'] ? $result['error'] : '';
        }
    }
}

$editCustomer = null;
if (isset($_GET['edit'])) {
    $editCustomer = $customerModel->getById($_GET['edit']);
}

$customers = $customerModel->getAll();
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
        .user-avatar { width: 40px; height: 40px; background-color: #012754; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold; }
        .content-area { padding: 30px; }
        .card { background-color: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #eee; }
        .card h2 { margin-bottom: 20px; color: #012754; font-size: 18px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; color: #333; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background-color: #f9f9f9; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #012754; background-color: white; }
        .form-group textarea { height: 80px; resize: none; }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        .form-buttons { display: flex; gap: 12px; margin-top: 25px; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #012754; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-danger { background-color: #d32f2f; color: white; }
        .btn-edit { background-color: #1976D2; color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background-color: #f8f9fa; color: #012754; font-size: 14px; }
        table tr:hover { background-color: #f8f9fa; }
        .action-buttons { display: flex; gap: 8px; }
        .empty-message { text-align: center; color: #666; padding: 40px; }
        .customer-avatar { width: 40px; height: 40px; background-color: #012754; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold; }
        .customer-info { display: flex; align-items: center; gap: 12px; }
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
            <a href="orders.php"><span class="icon">📋</span> Orders</a>
            <a href="kitchen.php"><span class="icon">👨‍🍳</span> Kitchen</a>
            <a href="billing.php"><span class="icon">💰</span> Billing</a>
            <a href="customers.php" class="active"><span class="icon">👤</span> Customers</a>
            <a href="reservations.php"><span class="icon">🎫</span> Reservations</a>
            <a href="reports.php"><span class="icon">📈</span> Reports</a>
            <a href="logout.php"><span class="icon">🚪</span> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div class="welcome-text">Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span></div>
            <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
        </div>

        <div class="content-area">
            <?php if ($success != ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error != ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <h2><?php echo $editCustomer ? '✏️ Edit Customer' : '👤 Add New Customer'; ?></h2>
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="<?php echo $editCustomer ? 'update' : 'create'; ?>">
                    <?php if ($editCustomer): ?>
                        <input type="hidden" name="id" value="<?php echo $editCustomer['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Customer Name *</label>
                            <input type="text" name="name" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['name']) : ''; ?>" placeholder="Enter name" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['phone']) : ''; ?>" placeholder="Enter phone">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['email']) : ''; ?>" placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['address']) : ''; ?>" placeholder="Enter address">
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $editCustomer ? 'Update Customer' : 'Add Customer'; ?></button>
                        <?php if ($editCustomer): ?>
                            <a href="customers.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>📋 All Customers (<?php echo count($customers); ?>)</h2>
                <?php if (count($customers) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Orders</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>
                                <div class="customer-info">
                                    <div class="customer-avatar"><?php echo strtoupper(substr($customer['name'], 0, 1)); ?></div>
                                    <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo $customer['phone'] ? htmlspecialchars($customer['phone']) : '-'; ?></td>
                            <td><?php echo $customer['email'] ? htmlspecialchars($customer['email']) : '-'; ?></td>
                            <td><?php echo $customer['address'] ? htmlspecialchars($customer['address']) : '-'; ?></td>
                            <td><?php echo $customer['total_orders']; ?></td>
                            <td class="action-buttons">
                                <a href="customers.php?edit=<?php echo $customer['id']; ?>" class="btn btn-edit btn-sm">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this customer?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-message">
                    <p>No customers found. Add your first customer above.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>