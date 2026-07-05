-- Flowtune Database Schema

CREATE DATABASE IF NOT EXISTS flowtune DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flowtune;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    balance DECIMAL(10,2) DEFAULT 0.00,
    gateway_api_key VARCHAR(255) NULL,
    webhook_url VARCHAR(255) NULL,
    global_redirect_url VARCHAR(255) NULL,
    active_landing VARCHAR(50) DEFAULT 'landing1',
    monetization_mode ENUM('single', 'channel') DEFAULT 'single',
    avatar VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Videos Table
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    video_url TEXT NOT NULL,
    thumbnail_url TEXT,
    views INT DEFAULT 0,
    clicks INT DEFAULT 0,
    earnings DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'pending', 'rejected', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal', 'earning') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    network VARCHAR(50) NULL,
    currency VARCHAR(10) DEFAULT 'TZS',
    reference_id VARCHAR(100) UNIQUE,
    status ENUM('completed', 'pending', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Withdrawals Table
CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Video Access Table (For 24h channel/single unlock)
CREATE TABLE IF NOT EXISTS video_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NULL,
    vendor_id INT NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    reference VARCHAR(100) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Support Tickets Table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Settings Table (Key-Value pairs)
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL
);

-- Analytics Table
CREATE TABLE IF NOT EXISTS analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    view_date DATE NOT NULL,
    views INT DEFAULT 0,
    clicks INT DEFAULT 0,
    earnings DECIMAL(10,2) DEFAULT 0.00,
    UNIQUE KEY unique_video_date (video_id, view_date),
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
);

-- Insert default admin user (password is 'password123')
INSERT INTO users (full_name, email, phone, password, role) 
VALUES ('Super Admin', 'admin@flowtune.com', '255700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('platform_name', 'Flowtune'),
('support_email', 'support@flowtune.com'),
('default_currency', 'TZS'),
('maintenance_mode', '0'),
('mpesa_test_mode', '1')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
