<?php
require 'config/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, barcode_id, prn_number, department, admission_year FROM members LIMIT 5");
    $members = $stmt->fetchAll();
    
    echo "Count: " . count($members) . "\n";
    foreach ($members as $m) {
        echo "ID: {$m['id']}, Name: {$m['name']}, Barcode: {$m['barcode_id']}, PRN: [{$m['prn_number']}], Dept: [{$m['department']}], Year: [{$m['admission_year']}]\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
