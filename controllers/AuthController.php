<?php
// Get the base path
$basePath = dirname(__DIR__);

require_once $basePath . '/config/database.php';
require_once $basePath . '/config/session.php';
require_once $basePath . '/config/validation.php';
require_once $basePath . '/config/security.php';

class AuthController {
    private $conn;
    private $validator;
    
    public function __construct() {
        $this->conn = getConnection();
        $this->validator = new Validator();
    }
    
    // Login user
    public function login($username, $password) {
        // Validate inputs
        $this->validator->required('username', $username, 'Username');
        $this->validator->required('password', $password, 'Password');
        
        if ($this->validator->fails()) {
            return array(
                'success' => false,
                'error' => $this->validator->getFirstError()
            );
        }
        
        // Rate limiting - max 5 attempts per minute
        if (!checkRateLimit('login', 5, 60)) {
            return array(
                'success' => false,
                'error' => 'Too many login attempts. Please wait a minute.'
            );
        }
        
        // Sanitize input
        $username = escape($this->conn, $username);
        
        // Find user
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return array(
                'success' => false,
                'error' => 'Invalid username or password'
            );
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password
        if (!verifyPassword($password, $user['password'])) {
            return array(
                'success' => false,
                'error' => 'Invalid username or password'
            );
        }
        
        // Set session
        setUserSession($user);
        
        return array(
            'success' => true,
            'message' => 'Login successful',
            'user' => array(
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            )
        );
    }
    
    // Register new user
    public function register($data) {
        // Validate inputs
        $this->validator->required('username', $data['username'], 'Username');
        $this->validator->minLength('username', $data['username'], 3, 'Username');
        $this->validator->maxLength('username', $data['username'], 50, 'Username');
        $this->validator->username('username', $data['username']);
        
        $this->validator->required('email', $data['email'], 'Email');
        $this->validator->email('email', $data['email'], 'Email');
        
        $this->validator->required('full_name', $data['full_name'], 'Full Name');
        $this->validator->minLength('full_name', $data['full_name'], 2, 'Full Name');
        
        $this->validator->required('password', $data['password'], 'Password');
        $this->validator->minLength('password', $data['password'], 6, 'Password');
        
        $this->validator->required('confirm_password', $data['confirm_password'], 'Confirm Password');
        $this->validator->passwordMatch($data['password'], $data['confirm_password']);
        
        $this->validator->required('role', $data['role'], 'Role');
        
        if ($this->validator->fails()) {
            return array(
                'success' => false,
                'error' => $this->validator->getFirstError(),
                'errors' => $this->validator->getErrors()
            );
        }
        
        // Sanitize inputs
        $username = escape($this->conn, $data['username']);
        $email = escape($this->conn, $data['email']);
        $full_name = escape($this->conn, $data['full_name']);
        $role = escape($this->conn, $data['role']);
        $phone = isset($data['phone']) ? escape($this->conn, $data['phone']) : '';
        
        // Check if username exists
        $sql = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return array(
                'success' => false,
                'error' => 'Username already exists'
            );
        }
        
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return array(
                'success' => false,
                'error' => 'Email already exists'
            );
        }
        
        // Hash password
        $hashedPassword = hashPassword($data['password']);
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password, full_name, role, phone, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssss", $username, $email, $hashedPassword, $full_name, $role, $phone);
        
        if ($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Registration successful! Please login.'
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Registration failed. Please try again.'
            );
        }
    }
    
    // Logout user
    public function logout() {
        logout();
        return array(
            'success' => true,
            'message' => 'Logged out successfully'
        );
    }
    
    // Close connection
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>