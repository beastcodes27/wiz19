<?php
require_once __DIR__ . '/includes/db.php';

echo "Running Flowtune schema updates...\n";

// Add monetization_mode to users
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN monetization_mode ENUM('single', 'channel') DEFAULT 'single'");
    echo "Added monetization_mode to users.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column monetization_mode already exists.\n";
    } else {
        echo "Error adding monetization_mode: " . $e->getMessage() . "\n";
    }
}

// Create video_access table
try {
    $pdo->exec("
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
        )
    ");
    echo "Created video_access table.\n";
} catch (PDOException $e) {
    echo "Error creating video_access table: " . $e->getMessage() . "\n";
}

echo "Schema update complete.\n";
