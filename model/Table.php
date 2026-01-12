<?php
$basePath = dirname(__DIR__);
require_once $basePath . '/model/config/database.php';

class Table {
    private $conn;
    private $tableName = 'tables';
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Get all tables
    public function getAll() {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY table_number ASC";
        $result = $this->conn->query($sql);
        
        $tables = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tables[] = $row;
            }
        }
        return $tables;
    }
    
    // Get available tables only
    public function getAvailable() {
        $sql = "SELECT * FROM {$this->tableName} WHERE status = 'available' ORDER BY table_number ASC";
        $result = $this->conn->query($sql);
        
        $tables = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tables[] = $row;
            }
        }
        return $tables;
    }
    
    // Get single table by ID
    public function getById($id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    // Get table by table number
    public function getByNumber($tableNumber) {
        $sql = "SELECT * FROM {$this->tableName} WHERE table_number = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tableNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    // Create new table
    public function create($data) {
        $table_number = intval($data['table_number']);
        $capacity = intval($data['capacity']);
        $location = escape($this->conn, $data['location']);
        $status = escape($this->conn, $data['status']);
        
        // Validate
        if ($table_number <= 0) {
            return array(
                'success' => false,
                'error' => 'Table number must be greater than 0'
            );
        }
        
        if ($capacity <= 0) {
            return array(
                'success' => false,
                'error' => 'Capacity must be greater than 0'
            );
        }
        
        // Check if table number already exists
        $checkSql = "SELECT id FROM {$this->tableName} WHERE table_number = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $table_number);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            return array(
                'success' => false,
                'error' => 'Table number already exists'
            );
        }
        
        $sql = "INSERT INTO {$this->tableName} (table_number, capacity, location, status) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiss", $table_number, $capacity, $location, $status);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Table created successfully',
                'id' => $this->conn->insert_id
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to create table'
            );
        }
    }
    
    // Update table
    public function update($id, $data) {
        $table_number = intval($data['table_number']);
        $capacity = intval($data['capacity']);
        $location = escape($this->conn, $data['location']);
        $status = escape($this->conn, $data['status']);
        
        // Validate
        if ($table_number <= 0) {
            return array(
                'success' => false,
                'error' => 'Table number must be greater than 0'
            );
        }
        
        if ($capacity <= 0) {
            return array(
                'success' => false,
                'error' => 'Capacity must be greater than 0'
            );
        }
        
        // Check if table number already exists for another table
        $checkSql = "SELECT id FROM {$this->tableName} WHERE table_number = ? AND id != ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $table_number, $id);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            return array(
                'success' => false,
                'error' => 'Table number already exists'
            );
        }
        
        $sql = "UPDATE {$this->tableName} SET table_number = ?, capacity = ?, location = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iissi", $table_number, $capacity, $location, $status, $id);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Table updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to update table'
            );
        }
    }
    
    // Update table status only
    public function updateStatus($id, $status) {
        $status = escape($this->conn, $status);
        
        $sql = "UPDATE {$this->tableName} SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Table status updated'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to update status'
            );
        }
    }
    
    // Delete table
    public function delete($id) {
        // Check if table has active orders
        $checkSql = "SELECT COUNT(*) as count FROM orders WHERE table_id = ? AND status IN ('active', 'in-kitchen', 'ready')";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return array(
                'success' => false,
                'error' => 'Cannot delete table. It has active orders.'
            );
        }
        
        // Check if table has reservations
        $checkSql = "SELECT COUNT(*) as count FROM reservations WHERE table_id = ? AND status IN ('pending', 'confirmed')";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return array(
                'success' => false,
                'error' => 'Cannot delete table. It has pending reservations.'
            );
        }
        
        $sql = "DELETE FROM {$this->tableName} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Table deleted successfully'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to delete table'
            );
        }
    }
    
    // Count tables by status
    public function countByStatus() {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->tableName} GROUP BY status";
        $result = $this->conn->query($sql);
        
        $counts = array(
            'available' => 0,
            'occupied' => 0,
            'reserved' => 0,
            'total' => 0
        );
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['status']] = $row['count'];
                $counts['total'] += $row['count'];
            }
        }
        
        return $counts;
    }
    
    // Get total capacity
    public function getTotalCapacity() {
        $sql = "SELECT SUM(capacity) as total FROM {$this->tableName}";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'] ? $row['total'] : 0;
    }
    
    // Close connection
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>