<?php
$basePath = dirname(__DIR__);
require_once $basePath . '/model/config/database.php';

class Customer {
    private $conn;
    private $table = 'customers';
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $result = $this->conn->query($sql);
        $customers = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $customers[] = $row;
            }
        }
        return $customers;
    }
    
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
    
    public function create($data) {
        $name = escape($this->conn, $data['name']);
        $phone = escape($this->conn, $data['phone']);
        $email = escape($this->conn, $data['email']);
        $address = escape($this->conn, $data['address']);
        
        $sql = "INSERT INTO {$this->table} (name, phone, email, address) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $phone, $email, $address);
        
        if ($stmt->execute()) {
            return array('success' => true, 'message' => 'Customer added successfully', 'id' => $this->conn->insert_id);
        }
        return array('success' => false, 'error' => 'Failed to add customer');
    }
    
    public function update($id, $data) {
        $name = escape($this->conn, $data['name']);
        $phone = escape($this->conn, $data['phone']);
        $email = escape($this->conn, $data['email']);
        $address = escape($this->conn, $data['address']);
        
        $sql = "UPDATE {$this->table} SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $phone, $email, $address, $id);
        
        if ($stmt->execute()) {
            return array('success' => true, 'message' => 'Customer updated successfully');
        }
        return array('success' => false, 'error' => 'Failed to update customer');
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return array('success' => true, 'message' => 'Customer deleted successfully');
        }
        return array('success' => false, 'error' => 'Failed to delete customer');
    }
    
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }
    
    public function search($keyword) {
        $keyword = '%' . escape($this->conn, $keyword) . '%';
        $sql = "SELECT * FROM {$this->table} WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        $customers = array();
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        return $customers;
    }
    
    public function __destruct() {
        if ($this->conn) $this->conn->close();
    }
}
?>