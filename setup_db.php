<?php
require 'config/db.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    echo "Dropping tables...\n";
    $pdo->exec("DROP TABLE IF EXISTS issues");
    $pdo->exec("DROP TABLE IF EXISTS books");
    $pdo->exec("DROP TABLE IF EXISTS members");
    $pdo->exec("DROP TABLE IF EXISTS users");
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Creating Users table...\n";
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'librarian') NOT NULL DEFAULT 'librarian',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    echo "Creating Members table...\n";
    $pdo->exec("CREATE TABLE members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barcode_id VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
        max_issue_limit INT DEFAULT 3,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    echo "Creating Books table...\n";
    $pdo->exec("CREATE TABLE books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barcode_id VARCHAR(50) NOT NULL UNIQUE,
        title VARCHAR(150) NOT NULL,
        author VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        shelf_location VARCHAR(50),
        total_copies INT NOT NULL DEFAULT 1,
        available_copies INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    echo "Creating Issues table...\n";
    $pdo->exec("CREATE TABLE issues (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        book_id INT NOT NULL,
        issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        due_date DATETIME NOT NULL,
        return_date DATETIME NULL,
        fine_amount DECIMAL(10, 2) DEFAULT 0.00,
        status ENUM('issued', 'returned') NOT NULL DEFAULT 'issued',
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    echo "Inserting default users...\n";
    $pass_admin = password_hash('admin123', PASSWORD_BCRYPT);
    $pass_lib = password_hash('lib123', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $pass_admin, 'admin']);
    $stmt->execute(['librarian', $pass_lib, 'librarian']);

    echo "Database setup completed successfully!\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>
