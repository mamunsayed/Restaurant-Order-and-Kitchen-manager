<?php
$pageTitle = 'Customers';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Customer.php';

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
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/customers.css">
</head> <body> <div class="sidebar"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"><span class="icon"></span> Dashboard</a> <a href="staff.php"><span class="icon"></span> Staff</a> <a href="tables.php"><span class="icon"></span> Tables</a> <a href="categories.php"><span class="icon"></span> Categories</a> <a href="menu_items.php"><span class="icon"></span> Menu Items</a> <a href="new_order.php"><span class="icon"></span> New Order</a> <a href="orders.php"><span class="icon"></span> Orders</a> <a href="kitchen.php"><span class="icon"></span> Kitchen</a> <a href="billing.php"><span class="icon"></span> Billing</a> <a href="customers.php" class="active"><span class="icon"></span> Customers</a> <a href="reservations.php"><span class="icon"></span> Reservations</a> <a href="reports.php"><span class="icon"></span> Reports</a> <a href="logout.php"><span class="icon"></span> Logout</a> </div> </div> <div class="main-content"> <div class="top-header"> <div class="welcome-text">Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span></div> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> <div class="content-area"> <?php if ($success != ''): ?> <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?> <?php if ($error != ''): ?> <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <div class="card"> <h2><?php echo $editCustomer ? ' Edit Customer' : ' Add New Customer'; ?></h2> <form method="POST" action="" id="customerForm"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="<?php echo $editCustomer ? 'update' : 'create'; ?>"> <?php if ($editCustomer): ?> <input type="hidden" name="id" value="<?php echo $editCustomer['id']; ?>"> <?php endif; ?> <div class="form-row"> <div class="form-group"> <label>Customer Name *</label> <input type="text" name="name" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['name']) : ''; ?>" placeholder="Enter name" required> </div> <div class="form-group"> <label>Phone</label> <input type="text" name="phone" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['phone']) : ''; ?>" placeholder="Enter phone"> </div> </div> <div class="form-row"> <div class="form-group"> <label>Email</label> <input type="email" name="email" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['email']) : ''; ?>" placeholder="Enter email"> </div> <div class="form-group"> <label>Address</label> <input type="text" name="address" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['address']) : ''; ?>" placeholder="Enter address"> </div> </div> <div class="form-buttons"> <button type="submit" class="btn btn-primary"><?php echo $editCustomer ? 'Update Customer' : 'Add Customer'; ?></button> <?php if ($editCustomer): ?> <a href="customers.php" class="btn btn-secondary">Cancel</a> <?php endif; ?> </div> </form> </div> <div class="card"> <h2> All Customers (<?php echo count($customers); ?>)</h2> <?php if (count($customers) > 0): ?> <table> <thead> <tr> <th>Customer</th> <th>Phone</th> <th>Email</th> <th>Address</th> <th>Orders</th> <th>Actions</th> </tr> </thead> <tbody> <?php foreach ($customers as $customer): ?> <tr> <td> <div class="customer-info"> <div class="customer-avatar"><?php echo strtoupper(substr($customer['name'], 0, 1)); ?></div> <strong><?php echo htmlspecialchars($customer['name']); ?></strong> </div> </td> <td><?php echo $customer['phone'] ? htmlspecialchars($customer['phone']) : '-'; ?></td> <td><?php echo $customer['email'] ? htmlspecialchars($customer['email']) : '-'; ?></td> <td><?php echo $customer['address'] ? htmlspecialchars($customer['address']) : '-'; ?></td> <td><?php echo $customer['total_orders']; ?></td> <td class="action-buttons"> <a href="customers.php?edit=<?php echo $customer['id']; ?>" class="btn btn-edit btn-sm">Edit</a> <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this customer?');"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="delete"> <input type="hidden" name="id" value="<?php echo $customer['id']; ?>"> <button type="submit" class="btn btn-danger btn-sm">Delete</button> </form> </td> </tr> <?php endforeach; ?> </tbody> </table> <?php else: ?> <div class="empty-message"> <p>No customers found. Add your first customer above.</p> </div> <?php endif; ?> </div> </div> </div> <script src="../asset/js/common.js"></script>
<script src="../asset/js/ajax.js"></script>
<script src="../asset/js/customers.js"></script>
</body> </html>