<?php
$pageTitle = 'Billing';
$basePath = dirname(__DIR__);

require_once $basePath . '/model/config/session.php';
require_once $basePath . '/model/config/security.php';
require_once $basePath . '/model/Order.php';

requireLogin();
requireRole(['admin', 'manager', 'cashier']);

$orderModel = new Order();

$error = '';
$success = '';
$order = null;
$billGenerated = false;

// Get order ID from URL
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Handle payment
if (isPost()) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'process_payment') {
            $orderId = intval($_POST['order_id']);
            $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
            $paidAmount = floatval($_POST['paid_amount']);
            $discount = floatval($_POST['discount']);
            
            // Get order
            $order = $orderModel->getById($orderId);
            
            if ($order) {
                // Calculate final total
                $subtotal = $order['subtotal'];
                $discountAmount = ($subtotal * $discount) / 100;
                $afterDiscount = $subtotal - $discountAmount;
                $tax = $afterDiscount * 0.05;
                $finalTotal = $afterDiscount + $tax;
                $changeAmount = $paidAmount - $finalTotal;
                
                if ($paidAmount < $finalTotal) {
                    $error = 'Paid amount is less than total';
                } else {
                    // Update order payment status
                    $conn = getConnection();
                    $sql = "UPDATE orders SET 
                            payment_method = ?, 
                            payment_status = 'paid',
                            discount = ?,
                            status = 'completed',
                            completed_at = NOW()
                            WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sdi", $paymentMethod, $discountAmount, $orderId);
                    
                    if ($stmt->execute()) {
                        // Update table status
                        if ($order['table_id']) {
                            $tableSql = "UPDATE tables SET status = 'available' WHERE id = ?";
                            $tableStmt = $conn->prepare($tableSql);
                            $tableStmt->bind_param("i", $order['table_id']);
                            $tableStmt->execute();
                        }
                        
                        $success = 'Payment successful! Change: $' . number_format($changeAmount, 2);
                        $billGenerated = true;
                    } else {
                        $error = 'Payment failed';
                    }
                    $conn->close();
                }
            }
        }
    }
}

// Get order details
if ($orderId > 0) {
    $order = $orderModel->getById($orderId);
}

// Get completed orders for billing
$completedOrders = $orderModel->getByStatus('ready');
$paidOrders = $orderModel->getByStatus('completed');

$currentUser = getCurrentUser();
?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo $pageTitle; ?> - Restaurant Management</title>  <link rel="stylesheet" href="../asset/css/billing.css">
</head> <body> <div class="sidebar no-print"> <div class="sidebar-logo"> <div class="sidebar-logo-icon">R</div> <h2>Restaurant</h2> </div> <div class="sidebar-menu"> <a href="dashboard.php"><span class="icon"></span> Dashboard</a> <a href="staff.php"><span class="icon"></span> Staff</a> <a href="tables.php"><span class="icon"></span> Tables</a> <a href="categories.php"><span class="icon"></span> Categories</a> <a href="menu_items.php"><span class="icon"></span> Menu Items</a> <a href="new_order.php"><span class="icon"></span> New Order</a> <a href="orders.php"><span class="icon"></span> Orders</a> <a href="kitchen.php"><span class="icon"></span> Kitchen</a> <a href="billing.php" class="active"><span class="icon"></span> Billing</a> <a href="customers.php"><span class="icon"></span> Customers</a> <a href="reservations.php"><span class="icon"></span> Reservations</a> <a href="reports.php"><span class="icon"></span> Reports</a> <a href="logout.php"><span class="icon"></span> Logout</a> </div> </div> <div class="main-content"> <div class="top-header no-print"> <div class="welcome-text"> Billing & Payments</div> <div class="top-header-right"> <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div> </div> </div> <div class="content-area"> <?php if ($success != ''): ?> <div class="alert alert-success no-print"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?> <?php if ($error != ''): ?> <div class="alert alert-error no-print"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?> <?php if ($order): ?> <!-- Billing View --> <div class="billing-grid"> <!-- Bill --> <div class="bill-container"> <div class="bill-header"> <h1> Restaurant</h1> <p>123 Food Street, City</p> <p>Tel: (123) 456-7890</p> </div> <div class="bill-info"> <div class="bill-info-row"> <span>Bill No:</span> <span>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span> </div> <div class="bill-info-row"> <span>Date:</span> <span><?php echo date('M d, Y h:i A'); ?></span> </div> <div class="bill-info-row"> <span>Table:</span> <span><?php echo $order['table_number'] ? 'Table ' . $order['table_number'] : 'N/A'; ?></span> </div> <div class="bill-info-row"> <span>Server:</span> <span><?php echo htmlspecialchars($order['server_name']); ?></span> </div> <div class="bill-info-row"> <span>Order Type:</span> <span><?php echo ucfirst($order['order_type']); ?></span> </div> </div> <div class="bill-items"> <div class="bill-item" style="font-weight: bold; border-bottom: 2px solid #ddd;"> <span class="bill-item-name">Item</span> <span class="bill-item-qty">Qty</span> <span class="bill-item-price">Amount</span> </div> <?php foreach ($order['items'] as $item): ?> <div class="bill-item"> <span class="bill-item-name"><?php echo htmlspecialchars($item['item_name']); ?></span> <span class="bill-item-qty"><?php echo $item['quantity']; ?></span> <span class="bill-item-price">$<?php echo number_format($item['subtotal'], 2); ?></span> </div> <?php endforeach; ?> </div> <div class="bill-totals"> <div class="bill-total-row"> <span>Subtotal:</span> <span>$<?php echo number_format($order['subtotal'], 2); ?></span> </div> <div class="bill-total-row discount" id="discountRow" style="display: none;"> <span>Discount (<span id="discountPercent">0</span>%):</span> <span>-$<span id="discountAmount">0.00</span></span> </div> <div class="bill-total-row"> <span>Tax (5%):</span> <span>$<span id="taxAmount"><?php echo number_format($order['tax'], 2); ?></span></span> </div> <div class="bill-total-row grand-total"> <span>Total:</span> <span>$<span id="grandTotal"><?php echo number_format($order['total'], 2); ?></span></span> </div> </div> <div class="bill-footer"> <p>Thank you for dining with us!</p> <p>Please visit again </p> </div> </div> <!-- Payment Form --> <div class="no-print"> <?php if ($order['payment_status'] != 'paid' && !$billGenerated): ?> <div class="card"> <h2> Payment</h2> <form method="POST" action="" id="paymentForm"> <?php echo csrfField(); ?> <input type="hidden" name="action" value="process_payment"> <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>"> <div class="form-group"> <label>Payment Method</label> <div class="payment-methods"> <div class="payment-method selected" data-method="cash" onclick="selectPaymentMethod('cash')"> <span class="method-icon"></span> <span class="method-name">Cash</span> </div> <div class="payment-method" data-method="card" onclick="selectPaymentMethod('card')"> <span class="method-icon"></span> <span class="method-name">Card</span> </div> <div class="payment-method" data-method="online" onclick="selectPaymentMethod('online')"> <span class="method-icon"></span> <span class="method-name">Online</span> </div> </div> <input type="hidden" name="payment_method" id="paymentMethod" value="cash"> </div> <div class="form-group"> <label>Discount (%)</label> <input type="number" name="discount" id="discountInput" value="0" min="0" max="100" onchange="calculateTotal()"> </div> <div class="form-group"> <label>Amount to Pay</label> <input type="text" id="amountToPay" value="$<?php echo number_format($order['total'], 2); ?>" readonly style="background-color: #e8f5e9; font-weight: bold; font-size: 18px; color: #2e7d32;"> </div> <div class="form-group"> <label>Paid Amount ($)</label> <input type="number" name="paid_amount" id="paidAmount" step="0.01" min="0" value="<?php echo number_format($order['total'], 2); ?>" onchange="calculateChange()"> </div> <div class="form-group"> <label>Change</label> <input type="text" id="changeAmount" value="$0.00" readonly style="background-color: #fff8e1; font-weight: bold; font-size: 18px;"> </div> <button type="submit" class="btn btn-success" style="width: 100%; padding: 15px; font-size: 16px;"> Complete Payment
                            </button> </form> </div> <?php else: ?> <div class="card"> <h2> Payment Complete</h2> <div style="text-align: center; padding: 20px;"> <div style="font-size: 64px; margin-bottom: 15px;"></div> <p style="font-size: 18px; color: #2e7d32; font-weight: bold;">Payment Successful!</p> <p style="color: #666; margin: 15px 0;">Order has been completed.</p> <button onclick="window.print();" class="btn btn-primary" style="margin-top: 10px;"> Print Bill</button> <a href="new_order.php" class="btn btn-success" style="margin-top: 10px;"> New Order</a> </div> </div> <?php endif; ?> </div> </div> <?php else: ?> <!-- Orders List for Billing --> <div class="card"> <h2> Select Order for Billing</h2> <?php if (count($completedOrders) > 0): ?> <h3 style="margin-bottom: 15px; color: #666; font-size: 14px;">Ready Orders</h3> <div class="order-list"> <?php foreach ($completedOrders as $o): ?> <a href="billing.php?order_id=<?php echo $o['id']; ?>" style="text-decoration: none;"> <div class="order-list-item"> <div class="order-list-info"> <h4>Order #<?php echo $o['id']; ?> - Table <?php echo $o['table_number'] ? $o['table_number'] : 'N/A'; ?></h4> <p><?php echo ucfirst($o['order_type']); ?> | <?php echo date('h:i A', strtotime($o['created_at'])); ?></p> </div> <div class="order-list-total">$<?php echo number_format($o['total'], 2); ?></div> </div> </a> <?php endforeach; ?> </div> <?php else: ?> <div class="empty-message"> <div class="empty-icon"></div> <p>No orders ready for billing</p> </div> <?php endif; ?> </div> <!-- Recent Paid Bills --> <?php if (count($paidOrders) > 0): ?> <div class="card"> <h2> Recent Paid Bills</h2> <div class="order-list"> <?php 
                    $recentPaid = array_slice($paidOrders, 0, 5);
                    foreach ($recentPaid as $o): 
                    ?> <a href="billing.php?order_id=<?php echo $o['id']; ?>" style="text-decoration: none;"> <div class="order-list-item"> <div class="order-list-info"> <h4>Order #<?php echo $o['id']; ?> - Table <?php echo $o['table_number'] ? $o['table_number'] : 'N/A'; ?></h4> <p><?php echo ucfirst($o['order_type']); ?> | <?php echo date('M d, h:i A', strtotime($o['created_at'])); ?></p> </div> <div class="order-list-total" style="color: #666;">$<?php echo number_format($o['total'], 2); ?> </div> </div> </a> <?php endforeach; ?> </div> </div> <?php endif; ?> <?php endif; ?> </div> </div>  <script src=\"../asset/js/ajax.js\"></script>
<script src="../asset/js/billing.js"></script>

<script>window.ORIGINAL_SUBTOTAL = <?php echo $order ? $order['subtotal'] : 0; ?>;</script>
</body> </html>