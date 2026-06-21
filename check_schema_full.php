<?php
require 'config/db.php';

try {
    echo "MEMBERS Table:\n";
    $stmt = $pdo->query("DESCRIBE members");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

    echo "\nUSERS Table:\n";
    $stmt = $pdo->query("DESCRIBE users");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

    echo "\nISSUES Table:\n";
    $stmt = $pdo->query("DESCRIBE issues");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
