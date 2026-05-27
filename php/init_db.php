<?php
/**
 * GAGS Foundation - Database Initialization
 * 
 * Creates all tables and seeds the default admin user.
 * Safe to run multiple times (uses CREATE TABLE IF NOT EXISTS).
 * 
 * Usage:
 *   - CLI:  php php/init_db.php
 *   - Auto: Called by api/index.php on first request
 */

require_once __DIR__ . '/config.php';

function initializeDatabase() {
    $pdo = getDB();
    
    try {
        // ============ PROGRAMS TABLE ============
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS programs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                tag VARCHAR(100),
                description TEXT,
                image VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // ============ PROJECTS TABLE ============
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                goal_amount DECIMAL(12,2) DEFAULT 0,
                raised_amount DECIMAL(12,2) DEFAULT 0,
                status ENUM('active','completed','upcoming') DEFAULT 'active',
                image VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // ============ DONATIONS TABLE ============
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS donations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                donor_name VARCHAR(255),
                email VARCHAR(255),
                amount DECIMAL(12,2) NOT NULL,
                project_id INT,
                message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // ============ GALLERY TABLE ============
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS gallery (
                id INT AUTO_INCREMENT PRIMARY KEY,
                image VARCHAR(500) NOT NULL,
                caption VARCHAR(255),
                category VARCHAR(100) DEFAULT 'general',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // ============ PARTNERS TABLE ============
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS partners (
                id INT AUTO_INCREMENT PRIMARY KEY,
                org_name VARCHAR(255) NOT NULL,
                contact_name VARCHAR(255),
                email VARCHAR(255),
                phone VARCHAR(50),
                partnership_type VARCHAR(100),
                message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // ============ ADMINS TABLE ============
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // ============ SEED DEFAULT ADMIN ============
        $adminUsername = defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'admin';
        $adminPassword = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'admin123';
        
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE username = ?');
        $stmt->execute([$adminUsername]);
        $userExists = $stmt->fetchColumn();
        
        if ($userExists == 0) {
            $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 10]);
            $stmt = $pdo->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
            $stmt->execute([$adminUsername, $hashedPassword]);
            
            if (php_sapi_name() === 'cli') {
                echo "✓ Default admin user created\n";
            }
        }
        
        if (php_sapi_name() === 'cli') {
            echo "✓ Database initialized successfully\n";
        }
        
        return true;
        
    } catch (PDOException $e) {
        if (php_sapi_name() === 'cli') {
            echo "✗ Database initialization error: " . $e->getMessage() . "\n";
            exit(1);
        }
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

// Run directly if called from CLI
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    initializeDatabase();
}
