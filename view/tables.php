<?php
$pageTitle = 'Tables';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Table.php';

// Check login & role
requireLogin();
requireRole(['admin', 'manager']);

$tableModel = new Table();
$error = '';
$success = '';

// Handle form submission
if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Create table
        if ($action === 'create') {
            $data = array(
                'table_number' => $_POST['table_number'] ?? 0,
                'capacity' => $_POST['capacity'] ?? 4,
                'location' => $_POST['location'] ?? '',
                'status' => $_POST['status'] ?? 'available'
            );
            
            if (empty($data['table_number']) || $data['table_number'] <= 0) {
                $error = 'Please enter a valid table number';
            } elseif (empty($data['capacity']) || $data['capacity'] <= 0) {
                $error = 'Please enter a valid capacity';
            } else {
                $result = $tableModel->create($data);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        // Update table
        if ($action === 'update') {
            $id = $_POST['id'] ?? 0;
            $data = array(
                'table_number' => $_POST['table_number'] ?? 0,
                'capacity' => $_POST['capacity'] ?? 4,
                'location' => $_POST['location'] ?? '',
                'status' => $_POST['status'] ?? 'available'
            );
            
            if (empty($data['table_number']) || $data['table_number'] <= 0) {
                $error = 'Please enter a valid table number';
            } elseif (empty($data['capacity']) || $data['capacity'] <= 0) {
                $error = 'Please enter a valid capacity';
            } else {
                $result = $tableModel->update($id, $data);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        // Update status only
        if ($action === 'update_status') {
            $id = $_POST['id'] ?? 0;
            $status = $_POST['status'] ?? 'available';
            
            $result = $tableModel->updateStatus($id, $status);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
        
        // Delete table
        if ($action === 'delete') {
            $id = $_POST['id'] ?? 0;
            $result = $tableModel->delete($id);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Get table for editing
$editTable = null;
if (isset($_GET['edit'])) {
    $editTable = $tableModel->getById($_GET['edit']);
}

// Get all tables
$tables = $tableModel->getAll();

// Get counts
$tableCounts = $tableModel->countByStatus();
$totalCapacity = $tableModel->getTotalCapacity();

// Get current user
$currentUser = getCurrentUser();
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/tables.css">
</head> <body> <div class="sidebar"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"><span class="icon"></span> Dashboard</a> <a href="staff.php"><span class="icon"></span> Staff</a> <a href="tables.php" class="active"><span class="icon"></span> Tables</a> <a href="categories.php"><span class="icon"></span> Categories</a> <a href="menu_items.php"><span class="icon"></span> Menu Items</a> <a href="new_order.php"><span class="icon"></span> New Order</a> <a href="orders.php"><span class="icon"></span> Orders</a> <a href="order_history.php"><span class="icon"></span> Order History</a> <a href="kitchen.php"><span class="icon"></span> Kitchen</a> <a href="billing.php"><span class="icon"></span> Billing</a> <a href="customers.php"><span class="icon"></span> Customers</a> <a href="reservations.php"><span class="icon"></span> Reservations</a> <a href="reports.php"><span class="icon"></span> Reports</a> <a href="logout.php"><span class="icon"></span> Logout</a> </div> </div> <div class="main-content"> <div class="top-header"> <div class="welcome-text">Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span></div> <div class="top-header-right"> <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> </div> <div class="content-area"> <?php if ($success): ?> <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?> <?php if ($error): ?> <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <!-- Stats Row --> <div class="stats-row"> <div class="stat-card available"> <div class="stat-icon"></div> <h3><?php echo $tableCounts['available']; ?></h3> <p>Available</p> </div> <div class="stat-card occupied"> <div class="stat-icon"></div> <h3><?php echo $tableCounts['occupied']; ?></h3> <p>Occupied</p> </div> <div class="stat-card reserved"> <div class="stat-icon"></div> <h3><?php echo $tableCounts['reserved']; ?></h3> <p>Reserved</p> </div> <div class="stat-card total"> <div class="stat-icon"></div> <h3><?php echo $tableCounts['total']; ?></h3> <p>Total Tables</p> </div> </div> <!-- Add/Edit Table Form --> <div class="card"> <h2><?php echo $editTable ? ' Edit Table' : ' Add New Table'; ?></h2> <form method="POST" action="" id="tableForm"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="<?php echo $editTable ? 'update' : 'create'; ?>"> <?php if ($editTable): ?> <input type="hidden" name="id" value="<?php echo $editTable['id']; ?>"> <?php endif; ?> <div class="form-row"> <div class="form-group"> <label for="table_number">Table Number *</label> <input type="number" id="table_number" name="table_number" min="1"
                                   value="<?php echo $editTable ? $editTable['table_number'] : ''; ?>" 
                                   placeholder="Enter table number" required> </div> <div class="form-group"> <label for="capacity">Capacity (Seats) *</label> <input type="number" id="capacity" name="capacity" min="1" max="20"
                                   value="<?php echo $editTable ? $editTable['capacity'] : '4'; ?>" 
                                   placeholder="Enter capacity" required> </div> </div> <div class="form-row"> <div class="form-group"> <label for="location">Location</label> <select id="location" name="location"> <option value="">-- Select Location --</option> <option value="Window Side" <?php echo ($editTable && $editTable['location'] === 'Window Side') ? 'selected' : ''; ?>>Window Side</option> <option value="Center" <?php echo ($editTable && $editTable['location'] === 'Center') ? 'selected' : ''; ?>>Center</option> <option value="Corner" <?php echo ($editTable && $editTable['location'] === 'Corner') ? 'selected' : ''; ?>>Corner</option> <option value="Private" <?php echo ($editTable && $editTable['location'] === 'Private') ? 'selected' : ''; ?>>Private Area</option> <option value="Outdoor" <?php echo ($editTable && $editTable['location'] === 'Outdoor') ? 'selected' : ''; ?>>Outdoor</option> </select> </div> <div class="form-group"> <label for="status">Status</label> <select id="status" name="status"> <option value="available" <?php echo ($editTable && $editTable['status'] === 'available') ? 'selected' : ''; ?>>Available</option> <option value="occupied" <?php echo ($editTable && $editTable['status'] === 'occupied') ? 'selected' : ''; ?>>Occupied</option> <option value="reserved" <?php echo ($editTable && $editTable['status'] === 'reserved') ? 'selected' : ''; ?>>Reserved</option> </select> </div> </div> <div class="form-buttons"> <button type="submit" class="btn btn-primary"> <?php echo $editTable ? 'Update Table' : 'Add Table'; ?> </button> <?php if ($editTable): ?> <a href="tables.php" class="btn btn-secondary">Cancel</a> <?php endif; ?> </div> </form> </div> <!-- Tables View --> <div class="card"> <h2> All Tables</h2> <!-- List View --> <div id="listView"> <?php if (count($tables) > 0): ?> <table> <thead> <tr> <th>Table #</th> <th>Capacity</th> <th>Location</th> <th>Status</th> <th>Actions</th> </tr> </thead> <tbody> <?php foreach ($tables as $table): ?> <tr> <td><strong>Table <?php echo $table['table_number']; ?></strong></td> <td> <?php echo $table['capacity']; ?> Seats</td> <td><?php echo $table['location'] ? $table['location'] : '-'; ?></td> <td> <span class="badge <?php 
                                        if ($table['status'] === 'available') echo 'badge-success';
                                        elseif ($table['status'] === 'occupied') echo 'badge-danger';
                                        else echo 'badge-info';
                                    ?>"> <?php echo ucfirst($table['status']); ?> </span> </td> <td class="action-buttons"> <a href="tables.php?edit=<?php echo $table['id']; ?>" class="btn btn-edit btn-sm">Edit</a> <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure?');"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="delete"> <input type="hidden" name="id" value="<?php echo $table['id']; ?>"> <button type="submit" class="btn btn-danger btn-sm">Delete</button> </form> </td> </tr> <?php endforeach; ?> </tbody> </table> <?php else: ?> <div class="empty-message"> <div class="empty-icon"></div> <p>No tables found. Add your first table above.</p> </div> <?php endif; ?> </div> </div> </div> </div>  <script src="../asset/js/common.js"></script>
<script src="../asset/js/ajax.js"></script>
<script src="../asset/js/tables.js"></script>
</body> </html>