-- =============================================
-- Restaurant Management System Database
-- Database Name: restaurant_db
-- =============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS restaurant_db;
USE restaurant_db;

-- =============================================
-- 1. USERS TABLE (Login/Signup)
-- =============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'server', 'kitchen') NOT NULL DEFAULT 'server',
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    remember_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 2. CATEGORIES TABLE
-- =============================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 3. MENU ITEMS TABLE
-- =============================================
CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- =============================================
-- 4. TABLES TABLE (Restaurant Tables)
-- =============================================
CREATE TABLE tables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_number INT NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 4,
    status ENUM('available', 'occupied', 'reserved') DEFAULT 'available',
    location VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 5. CUSTOMERS TABLE
-- =============================================
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    total_orders INT DEFAULT 0,
    total_spent DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 6. ORDERS TABLE
-- =============================================
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_id INT,
    customer_id INT,
    user_id INT NOT NULL,
    order_type ENUM('dine-in', 'takeaway', 'delivery') NOT NULL,
    status ENUM('active', 'in-kitchen', 'ready', 'completed', 'cancelled') DEFAULT 'active',
    subtotal DECIMAL(10, 2) DEFAULT 0.00,
    tax DECIMAL(10, 2) DEFAULT 0.00,
    discount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) DEFAULT 0.00,
    payment_method ENUM('cash', 'card', 'online') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    notes TEXT,
    delivery_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- 7. ORDER ITEMS TABLE
-- =============================================
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'cooking', 'ready', 'served') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- =============================================
-- 8. STAFF TABLE
-- =============================================
CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    position VARCHAR(50),
    salary DECIMAL(10, 2),
    hire_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- 9. RESERVATIONS TABLE
-- =============================================
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    table_id INT,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    guest_count INT NOT NULL DEFAULT 2,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL
);

-- =============================================
-- 10. BILLS TABLE
-- =============================================
CREATE TABLE bills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    bill_number VARCHAR(50) NOT NULL UNIQUE,
    subtotal DECIMAL(10, 2) NOT NULL,
    tax_rate DECIMAL(5, 2) DEFAULT 5.00,
    tax_amount DECIMAL(10, 2) NOT NULL,
    discount_percent DECIMAL(5, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card', 'online') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    paid_amount DECIMAL(10, 2) DEFAULT 0.00,
    change_amount DECIMAL(10, 2) DEFAULT 0.00,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- INSERT DEFAULT DATA
-- =============================================

-- Default Admin User (Password: admin123)
INSERT INTO users (username, email, password, full_name, role, status) VALUES
('admin', 'admin@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin', 'active'),
('manager', 'manager@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Restaurant Manager', 'manager', 'active'),
('server1', 'server@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Server', 'server', 'active'),
('kitchen1', 'kitchen@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chef Mike', 'kitchen', 'active');

-- Default Categories
INSERT INTO categories (name, description, status) VALUES
('Appetizers', 'Starters and small bites', 'active'),
('Main Course', 'Main dishes and entrees', 'active'),
('Beverages', 'Drinks and refreshments', 'active'),
('Desserts', 'Sweet treats and desserts', 'active'),
('Fast Food', 'Burgers, pizzas and quick bites', 'active');

-- Default Menu Items
INSERT INTO menu_items (category_id, name, description, price, status) VALUES
(1, 'Spring Rolls', 'Crispy vegetable spring rolls', 5.99, 'available'),
(1, 'Chicken Wings', 'Spicy buffalo chicken wings', 8.99, 'available'),
(1, 'Soup of the Day', 'Fresh homemade soup', 4.99, 'available'),
(2, 'Grilled Chicken', 'Herb marinated grilled chicken', 15.99, 'available'),
(2, 'Beef Steak', 'Premium beef steak with sides', 22.99, 'available'),
(2, 'Fish & Chips', 'Crispy fish with french fries', 13.99, 'available'),
(2, 'Pasta Alfredo', 'Creamy chicken alfredo pasta', 12.99, 'available'),
(3, 'Coca Cola', 'Chilled soft drink', 2.49, 'available'),
(3, 'Fresh Orange Juice', 'Freshly squeezed orange juice', 3.99, 'available'),
(3, 'Coffee', 'Hot brewed coffee', 2.99, 'available'),
(4, 'Chocolate Cake', 'Rich chocolate layer cake', 6.99, 'available'),
(4, 'Ice Cream', 'Vanilla ice cream with toppings', 4.99, 'available'),
(5, 'Classic Burger', 'Beef burger with cheese', 9.99, 'available'),
(5, 'Pepperoni Pizza', '12 inch pepperoni pizza', 14.99, 'available'),
(5, 'French Fries', 'Crispy golden fries', 3.99, 'available');

-- Default Tables
INSERT INTO tables (table_number, capacity, status, location) VALUES
(1, 2, 'available', 'Window Side'),
(2, 2, 'available', 'Window Side'),
(3, 4, 'available', 'Center'),
(4, 4, 'available', 'Center'),
(5, 4, 'available', 'Center'),
(6, 6, 'available', 'Corner'),
(7, 6, 'available', 'Corner'),
(8, 8, 'available', 'Private'),
(9, 8, 'available', 'Private'),
(10, 10, 'available', 'Party Area');

-- =============================================
-- END OF SQL FILE
-- =============================================