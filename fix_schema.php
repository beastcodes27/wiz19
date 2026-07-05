<?php
require_once __DIR__ . '/includes/db.php';

echo "Fixing database schema...\n";

// Add missing columns to transactions table
$columns = [
    "ALTER TABLE transactions ADD COLUMN video_id INT NULL AFTER user_id",
    "ALTER TABLE transactions ADD COLUMN fee_amount DECIMAL(10,2) DEFAULT 0.00 AFTER amount",
    "ALTER TABLE transactions ADD COLUMN completed_at TIMESTAMP NULL AFTER created_at",
];

foreach ($columns as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 60) . "...\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "SKIP: Column already exists.\n";
        } else {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

// Add missing columns to videos table
$videoColumns = [
    "ALTER TABLE videos ADD COLUMN price DECIMAL(10,2) DEFAULT 1000.00 AFTER thumbnail_url",
    "ALTER TABLE videos ADD COLUMN duration VARCHAR(20) DEFAULT '00:00' AFTER price",
    "ALTER TABLE videos ADD COLUMN fake_views INT DEFAULT 0 AFTER views",
];

foreach ($videoColumns as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 60) . "...\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "SKIP: Column already exists.\n";
        } else {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

// Add streaming_access table if missing
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS streaming_access (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            creator_id INT NOT NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_ip_creator (ip_address, creator_id),
            FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "OK: Created streaming_access table.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "Schema fix complete.\n";
