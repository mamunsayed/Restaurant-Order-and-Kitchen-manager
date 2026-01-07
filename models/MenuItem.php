<?php
$basePath = dirname(__DIR__);
require_once $basePath . '/config/database.php';

class MenuItem {
    private $conn;
    private $table = 'menu_items';
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Get all menu items with category name
    public function getAll() {
        $sql = "SELECT m.*, c.name as category_name 
                FROM {$this->table} m 
                LEFT JOIN categories c ON m.category_id = c.id 
                ORDER BY c.name ASC, m.name ASC";
        $result = $this->conn->query($sql);
        
        $items = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }
    
    // Get available menu items only
    public function getAvailable() {
        $sql = "SELECT m.*, c.name as category_name 
                FROM {$this->table} m 
                LEFT JOIN categories c ON m.category_id = c.id 
                WHERE m.status = 'available' AND c.status = 'active'
                ORDER BY c.name ASC, m.name ASC";
        $result = $this->conn->query($sql);
        
        $items = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }
    
    // Get menu items by category
    public function getByCategory($categoryId) {
        $sql = "SELECT m.*, c.name as category_name 
                FROM {$this->table} m 
                LEFT JOIN categories c ON m.category_id = c.id 
                WHERE m.category_id = ? AND m.status = 'available'
                ORDER BY m.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }
    
    // Get single menu item by ID
    public function getById($id) {
        $sql = "SELECT m.*, c.name as category_name 
                FROM {$this->table} m 
                LEFT JOIN categories c ON m.category_id = c.id 
                WHERE m.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    // Create new menu item
    public function create($data) {
        $category_id = intval($data['category_id']);
        $name = escape($this->conn, $data['name']);
        $description = escape($this->conn, $data['description']);
        $price = floatval($data['price']);
        $image = escape($this->conn, $data['image']);
        $status = escape($this->conn, $data['status']);
        
        // Validate price
        if ($price <= 0) {
            return array(
                'success' => false,
                'error' => 'Price must be greater than 0'
            );
        }
        
        // Check if name already exists in same category
        $checkSql = "SELECT id FROM {$this->table} WHERE name = ? AND category_id = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("si", $name, $category_id);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            return array(
                'success' => false,
                'error' => 'Item with this name already exists in this category'
            );
        }
        
        $sql = "INSERT INTO {$this->table} (category_id, name, description, price, image, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issdss", $category_id, $name, $description, $price, $image, $status);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Menu item created successfully',
                'id' => $this->conn->insert_id
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to create menu item'
            );
        }
    }
    
    // Update menu item
    public function update($id, $data) {
        $category_id = intval($data['category_id']);
        $name = escape($this->conn, $data['name']);
        $description = escape($this->conn, $data['description']);
        $price = floatval($data['price']);
        $image = escape($this->conn, $data['image']);
        $status = escape($this->conn, $data['status']);
        
        // Validate price
        if ($price <= 0) {
            return array(
                'success' => false,
                'error' => 'Price must be greater than 0'
            );
        }
        
        // Check if name already exists in same category for another item
        $checkSql = "SELECT id FROM {$this->table} WHERE name = ? AND category_id = ? AND id != ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("sii", $name, $category_id, $id);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            return array(
                'success' => false,
                'error' => 'Item with this name already exists in this category'
            );
        }
        
        $sql = "UPDATE {$this->table} 
                SET category_id = ?, name = ?, description = ?, price = ?, image = ?, status = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issdssi", $category_id, $name, $description, $price, $image, $status, $id);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Menu item updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to update menu item'
            );
        }
    }
    
    // Delete menu item
    public function delete($id) {
        // Check if item is in any active order
        $checkSql = "SELECT COUNT(*) as count FROM order_items oi 
                     JOIN orders o ON oi.order_id = o.id 
                     WHERE oi.menu_item_id = ? AND o.status IN ('active', 'in-kitchen')";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return array(
                'success' => false,
                'error' => 'Cannot delete item. It is in active orders.'
            );
        }
        
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Menu item deleted successfully'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to delete menu item'
            );
        }
    }
    
    // Search menu items
    public function search($keyword) {
        $keyword = '%' . escape($this->conn, $keyword) . '%';
        
        $sql = "SELECT m.*, c.name as category_name 
                FROM {$this->table} m 
                LEFT JOIN categories c ON m.category_id = c.id 
                WHERE m.name LIKE ? OR m.description LIKE ? OR c.name LIKE ?
                ORDER BY m.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = array();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }
    
    // Count total menu items
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    // Close connection
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>