<?php
$pageTitle = 'Staff Management';
$basePath = dirname(__DIR__);

require_once $basePath . '/config/session.php';
require_once $basePath . '/config/security.php';
require_once $basePath . '/models/Staff.php';

// Check login & role
requireLogin();
requireRole(['admin', 'manager']);

$staffModel = new Staff();
$error = '';
$success = '';

// Handle form submission
if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        // Create staff
        if ($action === 'create') {
            $data = array(
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'email' => isset($_POST['email']) ? $_POST['email'] : '',
                'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
                'position' => isset($_POST['position']) ? $_POST['position'] : '',
                'salary' => isset($_POST['salary']) ? $_POST['salary'] : 0,
                'hire_date' => isset($_POST['hire_date']) ? $_POST['hire_date'] : '',
                'address' => isset($_POST['address']) ? $_POST['address'] : '',
                'status' => isset($_POST['status']) ? $_POST['status'] : 'active',
                'user_id' => null
            );
            
            if (empty($data['name'])) {
                $error = 'Staff name is required';
            } elseif (empty($data['position'])) {
                $error = 'Position is required';
            } else {
                $result = $staffModel->create($data);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        // Update staff
        if ($action === 'update') {
            $id = isset($_POST['id']) ? $_POST['id'] : 0;
            $data = array(
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'email' => isset($_POST['email']) ? $_POST['email'] : '',
                'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
                'position' => isset($_POST['position']) ? $_POST['position'] : '',
                'salary' => isset($_POST['salary']) ? $_POST['salary'] : 0,
                'hire_date' => isset($_POST['hire_date']) ? $_POST['hire_date'] : '',
                'address' => isset($_POST['address']) ? $_POST['address'] : '',
                'status' => isset($_POST['status']) ? $_POST['status'] : 'active',
                'user_id' => null
            );
            
            if (empty($data['name'])) {
                $error = 'Staff name is required';
            } elseif (empty($data['position'])) {
                $error = 'Position is required';
            } else {
                $result = $staffModel->update($id, $data);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        // Delete staff
        if ($action === 'delete') {
            $id = isset($_POST['id']) ? $_POST['id'] : 0;
            $result = $staffModel->delete($id);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Get staff for editing
$editStaff = null;
if (isset($_GET['edit'])) {
    $editStaff = $staffModel->getById($_GET['edit']);
}

// Get all staff
$staffList = $staffModel->getAll();

// Get counts
$staffCounts = $staffModel->countByStatus();
$totalSalary = $staffModel->getTotalSalary();

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
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background-color: white; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #eee; }
        .stat-card .stat-icon { font-size: 28px; margin-bottom: 10px; }
        .stat-card h3 { font-size: 24px; margin-bottom: 5px; color: #012754; }
        .stat-card p { color: #666; font-size: 13px; }
        .card { background-color: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #eee; }
        .card h2 { margin-bottom: 20px; color: #012754; font-size: 18px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; color: #333; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background-color: #f9f9f9; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #012754; background-color: white; }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        .form-buttons { display: flex; gap: 12px; margin-top: 25px; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #012754; color: white; }
        .btn-primary:hover { background-color: #011c3d; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-danger { background-color: #d32f2f; color: white; }
        .btn-edit { background-color: #1976D2; color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-success { background-color: #e8f5e9; color: #2e7d32; }
        .badge-danger { background-color: #ffebee; color: #c62828; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background-color: #f8f9fa; color: #012754; font-size: 14px; }
        table tr:hover { background-color: #f8f9fa; }
        .action-buttons { display: flex; gap: 8px; }
        .empty-message { text-align: center; color: #666; padding: 40px; }
        .empty-message .empty-icon { font-size: 48px; margin-bottom: 15px; }
        .staff-avatar { width: 40px; height: 40px; background-color: #012754; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold; }
        .staff-info { display: flex; align-items: center; gap: 12px; }
        .position-badge { background-color: #e3f2fd; color: #012754; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .salary { color: #2e7d32; font-weight: bold; }
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
            <a href="staff.php" class="active"><span class="icon">👥</span> Staff</a>
            <a href="tables.php"><span class="icon">🪑</span> Tables</a>
            <a href="categories.php"><span class="icon">📁</span> Categories</a>
            <a href="menu_items.php"><span class="icon">🍔</span> Menu Items</a>
            <a href="new_order.php"><span class="icon">➕</span> New Order</a>
            <a href="orders.php"><span class="icon">📋</span> Orders</a>
            <a href="kitchen.php"><span class="icon">👨‍🍳</span> Kitchen</a>
            <a href="billing.php"><span class="icon">💰</span> Billing</a>
            <a href="customers.php"><span class="icon">👤</span> Customers</a>
            <a href="reservations.php"><span class="icon">🎫</span> Reservations</a>
            <a href="reports.php"><span class="icon">📈</span> Reports</a>
            <a href="logout.php"><span class="icon">🚪</span> Logout</a>
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
            <?php if ($success != ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error != ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <h3><?php echo $staffCounts['active']; ?></h3>
                    <p>Active Staff</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <h3><?php echo $staffCounts['inactive']; ?></h3>
                    <p>Inactive</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <h3><?php echo $staffCounts['total']; ?></h3>
                    <p>Total Staff</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <h3>$<?php echo number_format($totalSalary, 0); ?></h3>
                    <p>Monthly Salary</p>
                </div>
            </div>

            <!-- Form -->
            <div class="card">
                <h2><?php echo $editStaff ? '✏️ Edit Staff' : '👥 Add New Staff'; ?></h2>
                <form method="POST" action="" id="staffForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="<?php echo $editStaff ? 'update' : 'create'; ?>">
                    <?php if ($editStaff): ?>
                        <input type="hidden" name="id" value="<?php echo $editStaff['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" value="<?php echo $editStaff ? htmlspecialchars($editStaff['name']) : ''; ?>" placeholder="Enter full name" required>
                        </div>
                        <div class="form-group">
                            <label>Position *</label>
                            <select name="position" required>
                                <option value="">-- Select Position --</option>
                                <option value="Manager" <?php echo ($editStaff && $editStaff['position'] == 'Manager') ? 'selected' : ''; ?>>Manager</option>
                                <option value="Chef" <?php echo ($editStaff && $editStaff['position'] == 'Chef') ? 'selected' : ''; ?>>Chef</option>
                                <option value="Cook" <?php echo ($editStaff && $editStaff['position'] == 'Cook') ? 'selected' : ''; ?>>Cook</option>
                                <option value="Server" <?php echo ($editStaff && $editStaff['position'] == 'Server') ? 'selected' : ''; ?>>Server</option>
                                <option value="Cashier" <?php echo ($editStaff && $editStaff['position'] == 'Cashier') ? 'selected' : ''; ?>>Cashier</option>
                                <option value="Cleaner" <?php echo ($editStaff && $editStaff['position'] == 'Cleaner') ? 'selected' : ''; ?>>Cleaner</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo $editStaff ? htmlspecialchars($editStaff['email']) : ''; ?>" placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo $editStaff ? htmlspecialchars($editStaff['phone']) : ''; ?>" placeholder="Enter phone">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Salary ($)</label>
                            <input type="number" name="salary" min="0" step="0.01" value="<?php echo $editStaff ? $editStaff['salary'] : ''; ?>" placeholder="Monthly salary">
                        </div>
                        <div class="form-group">
                            <label>Hire Date</label>
                            <input type="date" name="hire_date" value="<?php echo $editStaff ? $editStaff['hire_date'] : ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo $editStaff ? htmlspecialchars($editStaff['address']) : ''; ?>" placeholder="Enter address">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" <?php echo ($editStaff && $editStaff['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($editStaff && $editStaff['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $editStaff ? 'Update Staff' : 'Add Staff'; ?></button>
                        <?php if ($editStaff): ?>
                            <a href="staff.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Staff List -->
            <div class="card">
                <h2>📋 All Staff (<?php echo count($staffList); ?>)</h2>
                <?php if (count($staffList) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Contact</th>
                            <th>Salary</th>
                            <th>Hire Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffList as $staff): ?>
                        <tr>
                            <td>
                                <div class="staff-info">
                                    <div class="staff-avatar"><?php echo strtoupper(substr($staff['name'], 0, 1)); ?></div>
                                    <strong><?php echo htmlspecialchars($staff['name']); ?></strong>
                                </div>
                            </td>
                            <td><span class="position-badge"><?php echo htmlspecialchars($staff['position']); ?></span></td>
                            <td>
                                <?php if ($staff['email']): ?>📧 <?php echo htmlspecialchars($staff['email']); ?><br><?php endif; ?>
                                <?php if ($staff['phone']): ?>📱 <?php echo htmlspecialchars($staff['phone']); ?><?php endif; ?>
                                <?php if (!$staff['email'] && !$staff['phone']): ?>-<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($staff['salary'] > 0): ?>
                                    <span class="salary">$<?php echo number_format($staff['salary'], 2); ?></span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td><?php echo $staff['hire_date'] ? date('M d, Y', strtotime($staff['hire_date'])) : '-'; ?></td>
                            <td>
                                <span class="badge <?php echo $staff['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($staff['status']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="staff.php?edit=<?php echo $staff['id']; ?>" class="btn btn-edit btn-sm">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this staff?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $staff['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-message">
                    <div class="empty-icon">👥</div>
                    <p>No staff found. Add your first staff member above.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('staffForm').onsubmit = function(e) {
        var name = document.querySelector('input[name="name"]').value.trim();
        var position = document.querySelector('select[name="position"]').value;
        
        if (name === '') {
            alert('Please enter staff name');
            e.preventDefault();
            return false;
        }
        
        if (position === '') {
            alert('Please select a position');
            e.preventDefault();
            return false;
        }
        
        return true;
    };
    </script>
</body>
</html>