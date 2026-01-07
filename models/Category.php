<?php
$basePath = dirname(__DIR__);
require_once $basePath . '/config/database.php';

class Category {
    private $conn;
    private $table = 'categories';
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Get all categories
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $result = $this->conn->query($sql);
        
        $categories = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }
    
    // Get active categories only
    public function getActive() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY name ASC";
        $result = $this->conn->query($sql);
        
        $categories = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }
    
    // Get single category by ID
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    // Create new category
    public function create($data) {
        $name = escape($this->conn, $data['name']);
        $description = escape($this->conn, $data['description']);
        $image = escape($this->conn, $data['image']);
        $status = escape($this->conn, $data['status']);
        
        // Check if name already exists
        $checkSql = "SELECT id FROM {$this->table} WHERE name = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            return array(
                'success' => false,
                'error' => 'Category name already exists'
            );
        }
        
        $sql = "INSERT INTO {$this->table} (name, description, image, status) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $description, $image, $status);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Category created successfully',
                'id' => $this->conn->insert_id
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to create category'
            );
        }
    }
    
    // Update category
    public function update($id, $data) {
        $name = escape($this->conn, $data['name']);
        $description = escape($this->conn, $data['description']);
        $image = escape($this->conn, $data['image']);
        $status = escape($this->conn, $data['status']);
        
        // Check if name already exists for another category
        $checkSql = "SELECT id FROM {$this->table} WHERE name = ? AND id != ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("si", $name, $id);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            return array(
                'success' => false,
                'error' => 'Category name already exists'
            );
        }
        
        $sql = "UPDATE {$this->table} SET name = ?, description = ?, image = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $description, $image, $status, $id);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Category updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to update category'
            );
        }
    }
    
    // Delete category
    public function delete($id) {
        // Check if category has menu items
        $checkSql = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return array(
                'success' => false,
                'error' => 'Cannot delete category. It has ' . $result['count'] . ' menu items.'
            );
        }
        
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Category deleted successfully'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to delete category'
            );
        }
    }
    
    // Count total categories
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