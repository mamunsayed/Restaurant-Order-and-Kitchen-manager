<?php
// PHP Validation Functions

class Validator {
    private $errors = array();
    
    // Validate required field
    public function required($field, $value, $fieldName) {
        if (empty(trim($value))) {
            $this->errors[$field] = $fieldName . " is required";
            return false;
        }
        return true;
    }
    
    // Validate email
    public function email($field, $value, $fieldName) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Please enter a valid email";
            return false;
        }
        return true;
    }
    
    // Validate minimum length
    public function minLength($field, $value, $min, $fieldName) {
        if (strlen(trim($value)) < $min) {
            $this->errors[$field] = $fieldName . " must be at least " . $min . " characters";
            return false;
        }
        return true;
    }
    
    // Validate maximum length
    public function maxLength($field, $value, $max, $fieldName) {
        if (strlen(trim($value)) > $max) {
            $this->errors[$field] = $fieldName . " must not exceed " . $max . " characters";
            return false;
        }
        return true;
    }
    
    // Validate password match
    public function passwordMatch($password, $confirmPassword) {
        if ($password !== $confirmPassword) {
            $this->errors['confirm_password'] = "Passwords do not match";
            return false;
        }
        return true;
    }
    
    // Validate phone number
    public function phone($field, $value, $fieldName) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $value);
        
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            $this->errors[$field] = "Please enter a valid phone number";
            return false;
        }
        return true;
    }
    
    // Validate numeric
    public function numeric($field, $value, $fieldName) {
        if (!is_numeric($value)) {
            $this->errors[$field] = $fieldName . " must be a number";
            return false;
        }
        return true;
    }
    
    // Validate positive number
    public function positive($field, $value, $fieldName) {
        if (!is_numeric($value) || $value < 0) {
            $this->errors[$field] = $fieldName . " must be a positive number";
            return false;
        }
        return true;
    }
    
    // Validate username (alphanumeric)
    public function username($field, $value) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            $this->errors[$field] = "Username can only contain letters, numbers and underscore";
            return false;
        }
        return true;
    }
    
    // Sanitize input
    public function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Check if validation passed
    public function passes() {
        return empty($this->errors);
    }
    
    // Check if validation failed
    public function fails() {
        return !empty($this->errors);
    }
    
    // Get all errors
    public function getErrors() {
        return $this->errors;
    }
    
    // Get first error
    public function getFirstError() {
        if (!empty($this->errors)) {
            return reset($this->errors);
        }
        return null;
    }
    
    // Add custom error
    public function addError($field, $message) {
        $this->errors[$field] = $message;
    }
    
    // Clear errors
    public function clearErrors() {
        $this->errors = array();
    }
}
?>