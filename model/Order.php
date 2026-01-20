<?php
$basePath = dirname(__DIR__);
require_once $basePath . '/model/config/database.php';

class Order {
    private $conn;
    private $table = 'orders';
    private $itemsTable = 'order_items';
    
    public function __construct() {
        $this->conn = getConnection();
        // Auto-heal schema mismatches on older databases (XAMPP local installs)
        $this->ensureSchema();
    }

    /**
     * Ensure required columns exist (prevents "Unknown column" fatal errors
     * when user imported an older SQL schema).
     */
    private function ensureSchema() {
        // Orders table columns used by the app
        $this->ensureColumn($this->table, 'notes', "TEXT NULL");
        $this->ensureColumn($this->table, 'delivery_address', "VARCHAR(255) NULL");

        $this->ensureColumn($this->table, 'subtotal', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        $this->ensureColumn($this->table, 'tax', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        $this->ensureColumn($this->table, 'discount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        $this->ensureColumn($this->table, 'payment_method', "VARCHAR(50) NULL");
        $this->ensureColumn($this->table, 'payment_status', "VARCHAR(20) NOT NULL DEFAULT 'unpaid'");
        $this->ensureColumn($this->table, 'completed_at', "DATETIME NULL");

        // Order items columns used by kitchen + order details flows
        $this->ensureColumn($this->itemsTable, 'item_name', "VARCHAR(120) NULL");
        $this->ensureColumn($this->itemsTable, 'notes', "TEXT NULL");
        $this->ensureColumn($this->itemsTable, 'status', "VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }

    private function ensureColumn($table, $column, $definitionSql) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        $checkSql = "SELECT COUNT(*) AS c
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = ?
                      AND COLUMN_NAME = ?";
        $stmt = $this->conn->prepare($checkSql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $exists = $row && intval($row['c']) > 0;
        $stmt->close();

        if (!$exists) {
            // Best-effort: if ALTER fails (permissions), app continues and other flows may still work.
            $alter = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definitionSql}";
            try {
                $this->conn->query($alter);
            } catch (Exception $e) {
                // swallow
            }
        }
    }
    
    // Get all orders
    public function getAll($status = null) {
        $sql = "SELECT o.*, t.table_number, u.full_name as server_name, c.name as customer_name_rel
                FROM {$this->table} o
                LEFT JOIN tables t ON o.table_id = t.id
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN customers c ON o.customer_id = c.id";
        
        if ($status) {
            $sql .= " WHERE o.status = '" . escape($this->conn, $status) . "'";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        $result = $this->conn->query($sql);
        
        $orders = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        return $orders;
    }
    
    // Get active orders (for kitchen)
    public function getActiveOrders() {
        $sql = "SELECT o.*, t.table_number, u.full_name as server_name
                FROM {$this->table} o
                LEFT JOIN tables t ON o.table_id = t.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.status IN ('active', 'in-kitchen', 'ready')
                ORDER BY o.created_at ASC";
        $result = $this->conn->query($sql);
        
        $orders = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Get order items
                $row['items'] = $this->getOrderItems($row['id']);
                $orders[] = $row;
            }
        }
        return $orders;
    }
    
    // Get orders by status
    public function getByStatus($status) {
        return $this->getAll($status);
    }
    
    // Get single order by ID
    public function getById($id) {
        $sql = "SELECT o.*, t.table_number, u.full_name as server_name
                FROM {$this->table} o
                LEFT JOIN tables t ON o.table_id = t.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            $order['items'] = $this->getOrderItems($id);
            return $order;
        }
        return null;
    }
    
    // Get order items
    public function getOrderItems($orderId) {
        $sql = "SELECT oi.*, m.name as item_name, m.image as item_image
                FROM {$this->itemsTable} oi
                LEFT JOIN menu_items m ON oi.menu_item_id = m.id
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }
    
    // Create new order
    public function create($data) {
        $table_id = !empty($data['table_id']) ? intval($data['table_id']) : null;
        $customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : null;
        $user_id = intval($data['user_id']);
        $order_type = escape($this->conn, $data['order_type']);
        $notes = escape($this->conn, isset($data['notes']) ? $data['notes'] : '');
        $delivery_address = escape($this->conn, isset($data['delivery_address']) ? $data['delivery_address'] : '');
        
        $sql = "INSERT INTO {$this->table} (table_id, customer_id, user_id, order_type, notes, delivery_address, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiisss", $table_id, $customer_id, $user_id, $order_type, $notes, $delivery_address);
        
        if ($stmt->execute()) {
            $orderId = $this->conn->insert_id;
            
            // Update table status if dine-in
            if ($table_id && $order_type == 'dine-in') {
                $this->updateTableStatus($table_id, 'occupied');
            }
            
            return array(
                'success' => true,
                'message' => 'Order created successfully',
                'id' => $orderId
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to create order'
            );
        }
    }
    
    // Add item to order
    public function addItem($orderId, $menuItemId, $quantity, $notes = '') {
        // Get menu item details
        $sql = "SELECT name, price FROM menu_items WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $menuItemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            return array('success' => false, 'error' => 'Menu item not found');
        }
        
        $menuItem = $result->fetch_assoc();
        $price = $menuItem['price'];
        $itemName = $menuItem['name'];
        $subtotal = $price * $quantity;
        $notes = escape($this->conn, $notes);
        
        // Check if item already exists in order
        $checkSql = "SELECT id, quantity FROM {$this->itemsTable} WHERE order_id = ? AND menu_item_id = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $orderId, $menuItemId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Update existing item
            $existingItem = $checkResult->fetch_assoc();
            $newQty = $existingItem['quantity'] + $quantity;
            $newSubtotal = $price * $newQty;
            
            $updateSql = "UPDATE {$this->itemsTable} SET quantity = ?, subtotal = ? WHERE id = ?";
            $updateStmt = $this->conn->prepare($updateSql);
            $updateStmt->bind_param("idi", $newQty, $newSubtotal, $existingItem['id']);
            $updateStmt->execute();
        } else {
            // Insert new item
            $insertSql = "INSERT INTO {$this->itemsTable} (order_id, menu_item_id, item_name, price, quantity, subtotal, notes, status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $insertStmt = $this->conn->prepare($insertSql);
            $insertStmt->bind_param("iisdids", $orderId, $menuItemId, $itemName, $price, $quantity, $subtotal, $notes);
            $insertStmt->execute();
        }
        
        // Update order total
        $this->updateOrderTotal($orderId);
        
        return array('success' => true, 'message' => 'Item added to order');
    }
    
    // Update item quantity
    public function updateItemQuantity($itemId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($itemId);
        }
        
        // Get item price
        $sql = "SELECT order_id, price FROM {$this->itemsTable} WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            return array('success' => false, 'error' => 'Item not found');
        }
        
        $item = $result->fetch_assoc();
        $subtotal = $item['price'] * $quantity;
        $orderId = $item['order_id'];
        
        $updateSql = "UPDATE {$this->itemsTable} SET quantity = ?, subtotal = ? WHERE id = ?";
        $updateStmt = $this->conn->prepare($updateSql);
        $updateStmt->bind_param("idi", $quantity, $subtotal, $itemId);
        $updateStmt->execute();
        
        $this->updateOrderTotal($orderId);
        
        return array('success' => true, 'message' => 'Quantity updated');
    }
    
    // Remove item from order
    public function removeItem($itemId) {
        // Get order ID first
        $sql = "SELECT order_id FROM {$this->itemsTable} WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            return array('success' => false, 'error' => 'Item not found');
        }
        
        $orderId = $result->fetch_assoc()['order_id'];
        
        // Delete item
        $deleteSql = "DELETE FROM {$this->itemsTable} WHERE id = ?";
        $deleteStmt = $this->conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $itemId);
        $deleteStmt->execute();
        
        $this->updateOrderTotal($orderId);
        
        return array('success' => true, 'message' => 'Item removed');
    }
    
    // Update order total
    private function updateOrderTotal($orderId) {
        $sql = "SELECT SUM(subtotal) as total FROM {$this->itemsTable} WHERE order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $subtotal = $row['total'] ? $row['total'] : 0;
        $tax = $subtotal * 0.05; // 5% tax
        $total = $subtotal + $tax;
        
        $updateSql = "UPDATE {$this->table} SET subtotal = ?, tax = ?, total = ? WHERE id = ?";
        $updateStmt = $this->conn->prepare($updateSql);
        $updateStmt->bind_param("dddi", $subtotal, $tax, $total, $orderId);
        $updateStmt->execute();
    }
    
    // Update order status
    public function updateStatus($orderId, $status) {
        $status = escape($this->conn, $status);
        
        $sql = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $orderId);
        
        if ($stmt->execute()) {
            // If completed, update table status
            if ($status == 'completed') {
                $order = $this->getById($orderId);
                if ($order && $order['table_id']) {
                    $this->updateTableStatus($order['table_id'], 'available');
                }
            }
            
            return array('success' => true, 'message' => 'Order status updated');
        }
        
        return array('success' => false, 'error' => 'Failed to update status');
    }
    
    // Update item status (for kitchen)
    public function updateItemStatus($itemId, $status) {
        $status = escape($this->conn, $status);
        
        $sql = "UPDATE {$this->itemsTable} SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $itemId);
        
        if ($stmt->execute()) {
            return array('success' => true, 'message' => 'Item status updated');
        }
        
        return array('success' => false, 'error' => 'Failed to update item status');
    }
    
    // Send order to kitchen
    public function sendToKitchen($orderId) {
        // Update order status
        $this->updateStatus($orderId, 'in-kitchen');
        
        // Update all items to cooking
        $sql = "UPDATE {$this->itemsTable} SET status = 'cooking' WHERE order_id = ? AND status = 'pending'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        return array('success' => true, 'message' => 'Order sent to kitchen');
    }
    
    // Mark all items ready
    public function markAllReady($orderId) {
        $sql = "UPDATE {$this->itemsTable} SET status = 'ready' WHERE order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        $this->updateStatus($orderId, 'ready');
        
        return array('success' => true, 'message' => 'All items marked as ready');
    }
    
    // Complete order
    public function completeOrder($orderId) {
        $sql = "UPDATE {$this->table} SET status = 'completed', completed_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        
        if ($stmt->execute()) {
            // Update table status
            $order = $this->getById($orderId);
            if ($order && $order['table_id']) {
                $this->updateTableStatus($order['table_id'], 'available');
            }
            
            // Update all items to served
            $itemSql = "UPDATE {$this->itemsTable} SET status = 'served' WHERE order_id = ?";
            $itemStmt = $this->conn->prepare($itemSql);
            $itemStmt->bind_param("i", $orderId);
            $itemStmt->execute();
            
            return array('success' => true, 'message' => 'Order completed');
        }
        
        return array('success' => false, 'error' => 'Failed to complete order');
    }
    
    // Cancel order
    public function cancelOrder($orderId) {
        $order = $this->getById($orderId);
        
        $sql = "UPDATE {$this->table} SET status = 'cancelled' WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        
        if ($stmt->execute()) {
            // Update table status
            if ($order && $order['table_id']) {
                $this->updateTableStatus($order['table_id'], 'available');
            }
            
            return array('success' => true, 'message' => 'Order cancelled');
        }
        
        return array('success' => false, 'error' => 'Failed to cancel order');
    }
    
    // Update table status
    private function updateTableStatus($tableId, $status) {
        $sql = "UPDATE tables SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $tableId);
        $stmt->execute();
    }
    
    // Get order statistics
    public function getStats() {
        $stats = array(
            'today_orders' => 0,
            'today_revenue' => 0,
            'active_orders' => 0,
            'completed_today' => 0
        );
        
        // Today's orders
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(created_at) = CURDATE()";
        $result = $this->conn->query($sql);
        $stats['today_orders'] = $result->fetch_assoc()['count'];
        
        // Today's revenue
        $sql = "SELECT SUM(total) as total FROM {$this->table} WHERE DATE(created_at) = CURDATE() AND status = 'completed'";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        $stats['today_revenue'] = $row['total'] ? $row['total'] : 0;
        
        // Active orders
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status IN ('active', 'in-kitchen', 'ready')";
        $result = $this->conn->query($sql);
        $stats['active_orders'] = $result->fetch_assoc()['count'];
        
        // Completed today
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(created_at) = CURDATE() AND status = 'completed'";
        $result = $this->conn->query($sql);
        $stats['completed_today'] = $result->fetch_assoc()['count'];
        
        return $stats;
    }
    
    // Close connection
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>