<?php
$basePath = dirname(__DIR__);
require_once $basePath . '/config/database.php';

class Staff {
    private $conn;
    private $table = 'staff';
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Get all staff
    public function getAll() {
        $sql = "SELECT s.*, u.username, u.role as user_role 
                FROM {$this->table} s 
                LEFT JOIN users u ON s.user_id = u.id 
                ORDER BY s.name ASC";
        $result = $this->conn->query($sql);
        
        $staff = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $staff[] = $row;
            }
        }
        return $staff;
    }
    
    // Get active staff only
    public function getActive() {
        $sql = "SELECT s.*, u.username 
                FROM {$this->table} s 
                LEFT JOIN users u ON s.user_id = u.id 
                WHERE s.status = 'active' 
                ORDER BY s.name ASC";
        $result = $this->conn->query($sql);
        
        $staff = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $staff[] = $row;
            }
        }
        return $staff;
    }
    
    // Get single staff by ID
    public function getById($id) {
        $sql = "SELECT s.*, u.username 
                FROM {$this->table} s 
                LEFT JOIN users u ON s.user_id = u.id 
                WHERE s.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    // Create new staff
    public function create($data) {
        $name = escape($this->conn, $data['name']);
        $email = escape($this->conn, $data['email']);
        $phone = escape($this->conn, $data['phone']);
        $position = escape($this->conn, $data['position']);
        $salary = floatval($data['salary']);
        $hire_date = escape($this->conn, $data['hire_date']);
        $address = escape($this->conn, $data['address']);
        $status = escape($this->conn, $data['status']);
        $user_id = !empty($data['user_id']) ? intval($data['user_id']) : null;
        
        // Check if email already exists
        if (!empty($email)) {
            $checkSql = "SELECT id FROM {$this->table} WHERE email = ? LIMIT 1";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                return array(
                    'success' => false,
                    'error' => 'Email already exists'
                );
            }
        }
        
        $sql = "INSERT INTO {$this->table} (user_id, name, email, phone, position, salary, hire_date, address, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssdsss", $user_id, $name, $email, $phone, $position, $salary, $hire_date, $address, $status);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Staff member added successfully',
                'id' => $this->conn->insert_id
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to add staff member'
            );
        }
    }
    
    // Update staff
    public function update($id, $data) {
        $name = escape($this->conn, $data['name']);
        $email = escape($this->conn, $data['email']);
        $phone = escape($this->conn, $data['phone']);
        $position = escape($this->conn, $data['position']);
        $salary = floatval($data['salary']);
        $hire_date = escape($this->conn, $data['hire_date']);
        $address = escape($this->conn, $data['address']);
        $status = escape($this->conn, $data['status']);
        $user_id = !empty($data['user_id']) ? intval($data['user_id']) : null;
        
        // Check if email already exists for another staff
        if (!empty($email)) {
            $checkSql = "SELECT id FROM {$this->table} WHERE email = ? AND id != ? LIMIT 1";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->bind_param("si", $email, $id);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                return array(
                    'success' => false,
                    'error' => 'Email already exists'
                );
            }
        }
        
        $sql = "UPDATE {$this->table} 
                SET user_id = ?, name = ?, email = ?, phone = ?, position = ?, 
                    salary = ?, hire_date = ?, address = ?, status = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssdsssi", $user_id, $name, $email, $phone, $position, $salary, $hire_date, $address, $status, $id);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Staff member updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to update staff member'
            );
        }
    }
    
    // Delete staff
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Staff member deleted successfully'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to delete staff member'
            );
        }
    }
    
    // Count staff by status
    public function countByStatus() {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $result = $this->conn->query($sql);
        
        $counts = array(
            'active' => 0,
            'inactive' => 0,
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
    
    // Count by position
    public function countByPosition() {
        $sql = "SELECT position, COUNT(*) as count FROM {$this->table} WHERE status = 'active' GROUP BY position";
        $result = $this->conn->query($sql);
        
        $counts = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['position']] = $row['count'];
            }
        }
        
        return $counts;
    }
    
    // Get total salary
    public function getTotalSalary() {
        $sql = "SELECT SUM(salary) as total FROM {$this->table} WHERE status = 'active'";
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