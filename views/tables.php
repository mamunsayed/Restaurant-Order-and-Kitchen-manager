<?php
$pageTitle = 'Tables';
$basePath = dirname(__DIR__);

require_once $basePath . '/config/session.php';
require_once $basePath . '/config/security.php';
require_once $basePath . '/models/Table.php';

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
        
        .sidebar { width: 260px; background-color: #012754; color: white; position: fixed; top: 0; left: 0; height: 100vh; padding: 0; overflow-y: auto; }
        .sidebar-logo { display: flex; align-items: center; padding: 25px 20px; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-logo-icon { width: 45px; height: 45px; background-color: white; border-radius: 10px; display: flex; justify-content: center; align-items: center; color: #012754; font-size: 20px; font-weight: bold; }
        .sidebar-logo h2 { font-size: 18px; font-weight: bold; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.7); text-decoration: none; padding: 14px 20px; font-size: 15px; border-left: 3px solid transparent; }
        .sidebar-menu a:hover { background-color: rgba(255,255,255,0.08); color: white; border-left-color: rgba(255,255,255,0.3); }
        .sidebar-menu a.active { background-color: rgba(255,255,255,0.12); color: white; border-left-color: white; font-weight: bold; }
        .sidebar-menu a .icon { width: 22px; text-align: center; font-size: 16px; }
        
        .main-content { margin-left: 260px; padding: 0; background-color: #f0f2f5; min-height: 100vh; }
        .top-header { background-color: white; padding: 20px 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .welcome-text { font-size: 18px; color: #333; }
        .welcome-text span { font-weight: bold; color: #012754; }
        .top-header-right { display: flex; align-items: center; gap: 12px; }
        .user-role { font-size: 14px; color: #666; font-weight: 500; text-transform: capitalize; }
        .user-avatar { width: 40px; height: 40px; background-color: #012754; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 16px; font-weight: bold; }
        
        .content-area { padding: 30px; }
        
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background-color: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; border: 1px solid #eee; }
        .stat-card .stat-icon { font-size: 28px; margin-bottom: 10px; }
        .stat-card h3 { font-size: 24px; margin-bottom: 5px; }
        .stat-card p { color: #666; font-size: 13px; }
        .stat-card.available h3 { color: #2e7d32; }
        .stat-card.occupied h3 { color: #d32f2f; }
        .stat-card.reserved h3 { color: #1565c0; }
        .stat-card.total h3 { color: #012754; }
        
        .card { background-color: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 30px; border: 1px solid #eee; }
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
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-danger { background-color: #d32f2f; color: white; }
        .btn-danger:hover { background-color: #b71c1c; }
        .btn-edit { background-color: #1976D2; color: white; }
        .btn-edit:hover { background-color: #1565C0; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-success { background-color: #e8f5e9; color: #2e7d32; }
        .badge-danger { background-color: #ffebee; color: #c62828; }
        .badge-info { background-color: #e3f2fd; color: #1565c0; }
        
        .tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .table-card { background-color: white; border-radius: 12px; padding: 20px; text-align: center; border: 2px solid #eee; }
        .table-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .table-card.available { border-color: #a5d6a7; background-color: #f1f8e9; }
        .table-card.occupied { border-color: #ef9a9a; background-color: #ffebee; }
        .table-card.reserved { border-color: #90caf9; background-color: #e3f2fd; }
        .table-card .table-icon { font-size: 48px; margin-bottom: 10px; }
        .table-card h3 { font-size: 20px; color: #333; margin-bottom: 8px; }
        .table-card .table-info { font-size: 13px; color: #666; margin-bottom: 5px; }
        .table-card .table-status { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; margin: 10px 0; color: white; }
        .table-card .table-status.available { background-color: #2e7d32; }
        .table-card .table-status.occupied { background-color: #d32f2f; }
        .table-card .table-status.reserved { background-color: #1565c0; }
        .table-card .table-actions { display: flex; gap: 8px; justify-content: center; margin-top: 15px; }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background-color: #f8f9fa; font-weight: bold; color: #012754; font-size: 14px; }
        table tr:hover { background-color: #f8f9fa; }
        .action-buttons { display: flex; gap: 8px; }
        
        .empty-message { text-align: center; color: #666; padding: 40px; font-size: 15px; }
        .empty-message .empty-icon { font-size: 48px; margin-bottom: 15px; }
        
        .view-toggle { display: flex; gap: 10px; margin-bottom: 20px; }
        .view-toggle button { padding: 10px 20px; border: 2px solid #ddd; background-color: white; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .view-toggle button.active { border-color: #012754; background-color: #012754; color: white; }
        .view-toggle button:hover:not(.active) { border-color: #012754; }
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
            <a href="tables.php" class="active"><span class="icon">🪑</span> Tables</a>
            <a href="categories.php"><span class="icon">📁</span> Categories</a>
            <a href="menu_items.php"><span class="icon">🍔</span> Menu Items</a>
            <a href="new_order.php"><span class="icon">➕</span> New Order</a>
            <a href="orders.php"><span class="icon">📋</span> Orders</a>
            <a href="order_history.php"><span class="icon">📜</span> Order History</a>
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
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card available">
                    <div class="stat-icon">✅</div>
                    <h3><?php echo $tableCounts['available']; ?></h3>
                    <p>Available</p>
                </div>
                <div class="stat-card occupied">
                    <div class="stat-icon">🍽️</div>
                    <h3><?php echo $tableCounts['occupied']; ?></h3>
                    <p>Occupied</p>
                </div>
                <div class="stat-card reserved">
                    <div class="stat-icon">📅</div>
                    <h3><?php echo $tableCounts['reserved']; ?></h3>
                    <p>Reserved</p>
                </div>
                <div class="stat-card total">
                    <div class="stat-icon">🪑</div>
                    <h3><?php echo $tableCounts['total']; ?></h3>
                    <p>Total Tables</p>
                </div>
            </div>

            <!-- Add/Edit Table Form -->
            <div class="card">
                <h2><?php echo $editTable ? '✏️ Edit Table' : '🪑 Add New Table'; ?></h2>
                <form method="POST" action="" id="tableForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="<?php echo $editTable ? 'update' : 'create'; ?>">
                    <?php if ($editTable): ?>
                        <input type="hidden" name="id" value="<?php echo $editTable['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="table_number">Table Number *</label>
                            <input type="number" id="table_number" name="table_number" min="1"
                                   value="<?php echo $editTable ? $editTable['table_number'] : ''; ?>" 
                                   placeholder="Enter table number" required>
                        </div>
                        <div class="form-group">
                            <label for="capacity">Capacity (Seats) *</label>
                            <input type="number" id="capacity" name="capacity" min="1" max="20"
                                   value="<?php echo $editTable ? $editTable['capacity'] : '4'; ?>" 
                                   placeholder="Enter capacity" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <select id="location" name="location">
                                <option value="">-- Select Location --</option>
                                <option value="Window Side" <?php echo ($editTable && $editTable['location'] === 'Window Side') ? 'selected' : ''; ?>>Window Side</option>
                                <option value="Center" <?php echo ($editTable && $editTable['location'] === 'Center') ? 'selected' : ''; ?>>Center</option>
                                <option value="Corner" <?php echo ($editTable && $editTable['location'] === 'Corner') ? 'selected' : ''; ?>>Corner</option>
                                <option value="Private" <?php echo ($editTable && $editTable['location'] === 'Private') ? 'selected' : ''; ?>>Private Area</option>
                                <option value="Outdoor" <?php echo ($editTable && $editTable['location'] === 'Outdoor') ? 'selected' : ''; ?>>Outdoor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="available" <?php echo ($editTable && $editTable['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                                <option value="occupied" <?php echo ($editTable && $editTable['status'] === 'occupied') ? 'selected' : ''; ?>>Occupied</option>
                                <option value="reserved" <?php echo ($editTable && $editTable['status'] === 'reserved') ? 'selected' : ''; ?>>Reserved</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editTable ? 'Update Table' : 'Add Table'; ?>
                        </button>
                        <?php if ($editTable): ?>
                            <a href="tables.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tables View -->
            <div class="card">
                <h2>📋 All Tables</h2>
                
                <div class="view-toggle">
                    <button id="gridViewBtn" class="active" onclick="showGridView()">🔲 Grid View</button>
                    <button id="listViewBtn" onclick="showListView()">📋 List View</button>
                </div>

                <!-- Grid View -->
                <div id="gridView" class="tables-grid">
                    <?php if (count($tables) > 0): ?>
                        <?php foreach ($tables as $table): ?>
                        <div class="table-card <?php echo $table['status']; ?>">
                            <div class="table-icon">🪑</div>
                            <h3>Table <?php echo $table['table_number']; ?></h3>
                            <div class="table-info">👥 <?php echo $table['capacity']; ?> Seats</div>
                            <div class="table-info">📍 <?php echo $table['location'] ? $table['location'] : 'Not Set'; ?></div>
                            <div class="table-status <?php echo $table['status']; ?>">
                                <?php echo ucfirst($table['status']); ?>
                            </div>
                            <div class="table-actions">
                                <a href="tables.php?edit=<?php echo $table['id']; ?>" class="btn btn-edit btn-sm">Edit</a>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $table['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-message" style="grid-column: 1/-1;">
                            <div class="empty-icon">🪑</div>
                            <p>No tables found. Add your first table above.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- List View -->
                <div id="listView" style="display: none;">
                    <?php if (count($tables) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Table #</th>
                                <th>Capacity</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $table): ?>
                            <tr>
                                <td><strong>Table <?php echo $table['table_number']; ?></strong></td>
                                <td>👥 <?php echo $table['capacity']; ?> Seats</td>
                                <td><?php echo $table['location'] ? $table['location'] : '-'; ?></td>
                                <td>
                                    <span class="badge <?php 
                                        if ($table['status'] === 'available') echo 'badge-success';
                                        elseif ($table['status'] === 'occupied') echo 'badge-danger';
                                        else echo 'badge-info';
                                    ?>">
                                        <?php echo ucfirst($table['status']); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="tables.php?edit=<?php echo $table['id']; ?>" class="btn btn-edit btn-sm">Edit</a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $table['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-message">
                            <div class="empty-icon">🪑</div>
                            <p>No tables found. Add your first table above.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showGridView() {
            document.getElementById('gridView').style.display = 'grid';
            document.getElementById('listView').style.display = 'none';
            document.getElementById('gridViewBtn').classList.add('active');
            document.getElementById('listViewBtn').classList.remove('active');
        }

        function showListView() {
            document.getElementById('gridView').style.display = 'none';
            document.getElementById('listView').style.display = 'block';
            document.getElementById('gridViewBtn').classList.remove('active');
            document.getElementById('listViewBtn').classList.add('active');
        }

        document.getElementById('tableForm').onsubmit = function(e) {
            var tableNumber = document.getElementById('table_number').value;
            var capacity = document.getElementById('capacity').value;
            
            if (tableNumber === '' || parseInt(tableNumber) <= 0) {
                alert('Please enter a valid table number');
                e.preventDefault();
                return false;
            }
            
            if (capacity === '' || parseInt(capacity) <= 0) {
                alert('Please enter a valid capacity');
                e.preventDefault();
                return false;
            }
            
            return true;
        };
    </script>
</body>
</html>