<?php
require 'config/db.php';

try {
    $stmt = $pdo->query("SELECT id, title, barcode_id, total_copies, available_copies FROM books");
    $books = $stmt->fetchAll();
    
    echo "Total Books: " . count($books) . "\n";
    foreach ($books as $b) {
        $status = ($b['available_copies'] > 0) ? "In Stock" : "OUT OF STOCK";
        echo "ID: {$b['id']} | {$b['title']} ({$b['barcode_id']}) | Available: {$b['available_copies']}/{$b['total_copies']} | Status: $status\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
