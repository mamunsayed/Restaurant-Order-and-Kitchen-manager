<?php
$basePath = dirname(__DIR__);
require_once $basePath . '/model/config/database.php';

class Reservation {
    private $conn;
    private $table = 'reservations';
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function getAll() {
        $sql = "SELECT r.*, t.table_number, c.name as customer_name_db 
                FROM {$this->table} r 
                LEFT JOIN tables t ON r.table_id = t.id 
                LEFT JOIN customers c ON r.customer_id = c.id 
                ORDER BY r.reservation_date DESC, r.reservation_time DESC";
        $result = $this->conn->query($sql);
        $reservations = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reservations[] = $row;
            }
        }
        return $reservations;
    }
    
    public function getUpcoming() {
        $sql = "SELECT r.*, t.table_number 
                FROM {$this->table} r 
                LEFT JOIN tables t ON r.table_id = t.id 
                WHERE r.reservation_date >= CURDATE() AND r.status IN ('pending', 'confirmed')
                ORDER BY r.reservation_date ASC, r.reservation_time ASC";
        $result = $this->conn->query($sql);
        $reservations = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reservations[] = $row;
            }
        }
        return $reservations;
    }
    
    public function getToday() {
        $sql = "SELECT r.*, t.table_number 
                FROM {$this->table} r 
                LEFT JOIN tables t ON r.table_id = t.id 
                WHERE r.reservation_date = CURDATE() AND r.status IN ('pending', 'confirmed')
                ORDER BY r.reservation_time ASC";
        $result = $this->conn->query($sql);
        $reservations = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reservations[] = $row;
            }
        }
        return $reservations;
    }
    
    public function getById($id) {
        $sql = "SELECT r.*, t.table_number 
                FROM {$this->table} r 
                LEFT JOIN tables t ON r.table_id = t.id 
                WHERE r.id = ? LIMIT 1";
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

        // Required fields
        $table_id = !empty($data['table_id']) ? intval($data['table_id']) : 0;
        $customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : 0;

        $customer_name = isset($data['customer_name']) ? trim($data['customer_name']) : '';
        $customer_phone = isset($data['customer_phone']) ? trim($data['customer_phone']) : '';

        $guest_count = isset($data['guest_count']) ? intval($data['guest_count']) : (isset($data['guests']) ? intval($data['guests']) : 1);
        $reservation_date = isset($data['reservation_date']) ? trim($data['reservation_date']) : '';
        $reservation_time = isset($data['reservation_time']) ? trim($data['reservation_time']) : '';
        $notes = isset($data['notes']) ? trim($data['notes']) : '';
        $status = 'pending';

        if ($table_id <= 0) {
            return array('success' => false, 'error' => 'Please select a table');
        }

        // If customer_id not provided, create/find customer by name/phone
        if ($customer_id <= 0) {
            if ($customer_name === '') {
                return array('success' => false, 'error' => 'Customer name is required');
            }

            $safeName = escape($this->conn, $customer_name);
            $safePhone = escape($this->conn, $customer_phone);

            // Try to find existing customer (by phone if available, otherwise by name)
            if ($safePhone !== '') {
                $findSql = "SELECT id FROM customers WHERE phone = ? LIMIT 1";
                $findStmt = $this->conn->prepare($findSql);
                $findStmt->bind_param("s", $safePhone);
            } else {
                $findSql = "SELECT id FROM customers WHERE name = ? LIMIT 1";
                $findStmt = $this->conn->prepare($findSql);
                $findStmt->bind_param("s", $safeName);
            }

            $findStmt->execute();
            $found = $findStmt->get_result()->fetch_assoc();

            if ($found && !empty($found['id'])) {
                $customer_id = intval($found['id']);
            } else {
                $insSql = "INSERT INTO customers (name, phone) VALUES (?, ?)";
                $insStmt = $this->conn->prepare($insSql);
                $insStmt->bind_param("ss", $safeName, $safePhone);
                if (!$insStmt->execute()) {
                    return array('success' => false, 'error' => 'Failed to create customer');
                }
                $customer_id = intval($this->conn->insert_id);
            }
        }

        if ($guest_count <= 0) {
            $guest_count = 1;
        }

        $safeDate = escape($this->conn, $reservation_date);
        $safeTime = escape($this->conn, $reservation_time);
        $safeNotes = escape($this->conn, $notes);

        $sql = "INSERT INTO {$this->table} (customer_id, table_id, reservation_date, reservation_time, guests, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iississ", $customer_id, $table_id, $safeDate, $safeTime, $guest_count, $status, $safeNotes);

        if ($stmt->execute()) {
            // Update table status
            $this->updateTableStatus($table_id, 'reserved');
            return array('success' => true, 'message' => 'Reservation created successfully');
        }
        return array('success' => false, 'error' => 'Failed to create reservation');
    }
    
    public function update($id, $data) {

        $table_id = !empty($data['table_id']) ? intval($data['table_id']) : 0;
        $customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : 0;

        $customer_name = isset($data['customer_name']) ? trim($data['customer_name']) : '';
        $customer_phone = isset($data['customer_phone']) ? trim($data['customer_phone']) : '';

        $guest_count = isset($data['guest_count']) ? intval($data['guest_count']) : (isset($data['guests']) ? intval($data['guests']) : 1);
        $reservation_date = isset($data['reservation_date']) ? trim($data['reservation_date']) : '';
        $reservation_time = isset($data['reservation_time']) ? trim($data['reservation_time']) : '';
        $notes = isset($data['notes']) ? trim($data['notes']) : '';
        $status = isset($data['status']) ? trim($data['status']) : 'pending';

        if ($table_id <= 0) {
            return array('success' => false, 'error' => 'Please select a table');
        }

        // Resolve customer_id if missing
        if ($customer_id <= 0) {
            if ($customer_name === '') {
                return array('success' => false, 'error' => 'Customer name is required');
            }

            $safeName = escape($this->conn, $customer_name);
            $safePhone = escape($this->conn, $customer_phone);

            if ($safePhone !== '') {
                $findSql = "SELECT id FROM customers WHERE phone = ? LIMIT 1";
                $findStmt = $this->conn->prepare($findSql);
                $findStmt->bind_param("s", $safePhone);
            } else {
                $findSql = "SELECT id FROM customers WHERE name = ? LIMIT 1";
                $findStmt = $this->conn->prepare($findSql);
                $findStmt->bind_param("s", $safeName);
            }

            $findStmt->execute();
            $found = $findStmt->get_result()->fetch_assoc();

            if ($found && !empty($found['id'])) {
                $customer_id = intval($found['id']);
            } else {
                $insSql = "INSERT INTO customers (name, phone) VALUES (?, ?)";
                $insStmt = $this->conn->prepare($insSql);
                $insStmt->bind_param("ss", $safeName, $safePhone);
                if (!$insStmt->execute()) {
                    return array('success' => false, 'error' => 'Failed to create customer');
                }
                $customer_id = intval($this->conn->insert_id);
            }
        }

        if ($guest_count <= 0) {
            $guest_count = 1;
        }

        $safeDate = escape($this->conn, $reservation_date);
        $safeTime = escape($this->conn, $reservation_time);
        $safeNotes = escape($this->conn, $notes);
        $safeStatus = escape($this->conn, $status);

        $sql = "UPDATE {$this->table}
                SET customer_id = ?, table_id = ?, reservation_date = ?, reservation_time = ?, guests = ?, status = ?, notes = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iississi", $customer_id, $table_id, $safeDate, $safeTime, $guest_count, $safeStatus, $safeNotes, $id);

        if ($stmt->execute()) {
            return array('success' => true, 'message' => 'Reservation updated successfully');
        }
        return array('success' => false, 'error' => 'Failed to update reservation');
    }
    
    public function updateStatus($id, $status) {
        $status = escape($this->conn, $status);
        $sql = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            // If cancelled or completed, free the table
            if ($status == 'cancelled' || $status == 'completed') {
                $reservation = $this->getById($id);
                if ($reservation && $reservation['table_id']) {
                    $this->updateTableStatus($reservation['table_id'], 'available');
                }
            }
            return array('success' => true, 'message' => 'Status updated');
        }
        return array('success' => false, 'error' => 'Failed to update status');
    }
    
    public function delete($id) {
        $reservation = $this->getById($id);
        
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($reservation && $reservation['table_id']) {
                $this->updateTableStatus($reservation['table_id'], 'available');
            }
            return array('success' => true, 'message' => 'Reservation deleted');
        }
        return array('success' => false, 'error' => 'Failed to delete');
    }
    
    private function updateTableStatus($tableId, $status) {
        $sql = "UPDATE tables SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $tableId);
        $stmt->execute();
    }
    
    public function countByStatus() {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} WHERE reservation_date >= CURDATE() GROUP BY status";
        $result = $this->conn->query($sql);
        $counts = array('pending' => 0, 'confirmed' => 0, 'cancelled' => 0, 'completed' => 0);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['status']] = $row['count'];
            }
        }
        return $counts;
    }
    
    public function __destruct() {
        if ($this->conn) $this->conn->close();
    }
}
?>