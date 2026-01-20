<?php
$pageTitle = 'Staff Management';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Staff.php';

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
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/staff.css">
</head> <body> <div class="sidebar"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"><span class="icon"></span> Dashboard</a> <a href="staff.php" class="active"><span class="icon"></span> Staff</a> <a href="tables.php"><span class="icon"></span> Tables</a> <a href="categories.php"><span class="icon"></span> Categories</a> <a href="menu_items.php"><span class="icon"></span> Menu Items</a> <a href="new_order.php"><span class="icon"></span> New Order</a> <a href="orders.php"><span class="icon"></span> Orders</a> <a href="kitchen.php"><span class="icon"></span> Kitchen</a> <a href="billing.php"><span class="icon"></span> Billing</a> <a href="customers.php"><span class="icon"></span> Customers</a> <a href="reservations.php"><span class="icon"></span> Reservations</a> <a href="reports.php"><span class="icon"></span> Reports</a> <a href="logout.php"><span class="icon"></span> Logout</a> </div> </div> <div class="main-content"> <div class="top-header"> <div class="welcome-text">Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span></div> <div class="top-header-right"> <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> </div> <div class="content-area"> <?php if ($success != ''): ?> <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?> <?php if ($error != ''): ?> <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <!-- Stats --> <div class="stats-row"> <div class="stat-card"> <div class="stat-icon"></div> <h3><?php echo $staffCounts['active']; ?></h3> <p>Active Staff</p> </div> <div class="stat-card"> <div class="stat-icon"></div> <h3><?php echo $staffCounts['inactive']; ?></h3> <p>Inactive</p> </div> <div class="stat-card"> <div class="stat-icon"></div> <h3><?php echo $staffCounts['total']; ?></h3> <p>Total Staff</p> </div> <div class="stat-card"> <div class="stat-icon"></div> <h3>$<?php echo number_format($totalSalary, 0); ?></h3> <p>Monthly Salary</p> </div> </div> <!-- Form --> <div class="card"> <h2><?php echo $editStaff ? ' Edit Staff' : ' Add New Staff'; ?></h2> <form method="POST" action="" id="staffForm"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="<?php echo $editStaff ? 'update' : 'create'; ?>"> <?php if ($editStaff): ?> <input type="hidden" name="id" value="<?php echo $editStaff['id']; ?>"> <?php endif; ?> <div class="form-row"> <div class="form-group"> <label>Full Name *</label> <input type="text" id="full_name" name="name" value="<?php echo $editStaff ? htmlspecialchars($editStaff['name']) : ''; ?>" placeholder="Enter full name" required> </div> <div class="form-group"> <label>Position *</label> <select id="position" name="position" required> <option value="">-- Select Position --</option> <option value="Manager" <?php echo ($editStaff && $editStaff['position'] == 'Manager') ? 'selected' : ''; ?>>Manager</option> <option value="Chef" <?php echo ($editStaff && $editStaff['position'] == 'Chef') ? 'selected' : ''; ?>>Chef</option> <option value="Cook" <?php echo ($editStaff && $editStaff['position'] == 'Cook') ? 'selected' : ''; ?>>Cook</option> <option value="Server" <?php echo ($editStaff && $editStaff['position'] == 'Server') ? 'selected' : ''; ?>>Server</option> <option value="Cashier" <?php echo ($editStaff && $editStaff['position'] == 'Cashier') ? 'selected' : ''; ?>>Cashier</option> <option value="Cleaner" <?php echo ($editStaff && $editStaff['position'] == 'Cleaner') ? 'selected' : ''; ?>>Cleaner</option> </select> </div> </div> <div class="form-row"> <div class="form-group"> <label>Email</label> <input type="email" id="email" name="email" value="<?php echo $editStaff ? htmlspecialchars($editStaff['email']) : ''; ?>" placeholder="Enter email"> </div> <div class="form-group"> <label>Phone</label> <input type="text" id="phone" name="phone" value="<?php echo $editStaff ? htmlspecialchars($editStaff['phone']) : ''; ?>" placeholder="Enter phone"> </div> </div> <div class="form-row"> <div class="form-group"> <label>Salary ($)</label> <input type="number" id="salary" name="salary" min="0" step="0.01" value="<?php echo $editStaff ? $editStaff['salary'] : ''; ?>" placeholder="Monthly salary"> </div> <div class="form-group"> <label>Hire Date</label> <input type="date" id="hire_date" name="hire_date" value="<?php echo $editStaff ? $editStaff['hire_date'] : ''; ?>"> </div> </div> <div class="form-row"> <div class="form-group"> <label>Address</label> <input type="text" id="address" name="address" value="<?php echo $editStaff ? htmlspecialchars($editStaff['address']) : ''; ?>" placeholder="Enter address"> </div> <div class="form-group"> <label>Status</label> <select id="status" name="status"> <option value="active" <?php echo ($editStaff && $editStaff['status'] == 'active') ? 'selected' : ''; ?>>Active</option> <option value="inactive" <?php echo ($editStaff && $editStaff['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option> </select> </div> </div> <div class="form-buttons"> <button type="submit" class="btn btn-primary"><?php echo $editStaff ? 'Update Staff' : 'Add Staff'; ?></button> <?php if ($editStaff): ?> <a href="staff.php" class="btn btn-secondary">Cancel</a> <?php endif; ?> </div> </form> </div> <!-- Staff List --> <div class="card"> <h2> All Staff (<?php echo count($staffList); ?>)</h2> <?php if (count($staffList) > 0): ?> <table> <thead> <tr> <th>Name</th> <th>Position</th> <th>Contact</th> <th>Salary</th> <th>Hire Date</th> <th>Status</th> <th>Actions</th> </tr> </thead> <tbody> <?php foreach ($staffList as $staff): ?> <tr> <td> <div class="staff-info"> <div class="staff-avatar"><?php echo strtoupper(substr($staff['name'], 0, 1)); ?></div> <strong><?php echo htmlspecialchars($staff['name']); ?></strong> </div> </td> <td><span class="position-badge"><?php echo htmlspecialchars($staff['position']); ?></span></td> <td> <?php if ($staff['email']): ?> <?php echo htmlspecialchars($staff['email']); ?><br><?php endif; ?> <?php if ($staff['phone']): ?> <?php echo htmlspecialchars($staff['phone']); ?><?php endif; ?> <?php if (!$staff['email'] && !$staff['phone']): ?>-<?php endif; ?> </td> <td> <?php if ($staff['salary'] > 0): ?> <span class="salary">$<?php echo number_format($staff['salary'], 2); ?></span> <?php else: ?>-<?php endif; ?> </td> <td><?php echo $staff['hire_date'] ? date('M d, Y', strtotime($staff['hire_date'])) : '-'; ?></td> <td> <span class="badge <?php echo $staff['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>"> <?php echo ucfirst($staff['status']); ?> </span> </td> <td class="action-buttons"> <a href="staff.php?edit=<?php echo $staff['id']; ?>" class="btn btn-edit btn-sm">Edit</a> <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this staff?');"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="delete"> <input type="hidden" name="id" value="<?php echo $staff['id']; ?>"> <button type="submit" class="btn btn-danger btn-sm">Delete</button> </form> </td> </tr> <?php endforeach; ?> </tbody> </table> <?php else: ?> <div class="empty-message"> <div class="empty-icon"></div> <p>No staff found. Add your first staff member above.</p> </div> <?php endif; ?> </div> </div> </div>  <script src="../asset/js/common.js"></script>
<script src="../asset/js/ajax.js"></script>
<script src="../asset/js/staff.js"></script>
</body> </html>