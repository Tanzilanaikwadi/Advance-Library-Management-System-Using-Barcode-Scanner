<?php
require 'config/db.php';

$book_id = 3; // The ID of the book that is out of stock

try {
    echo "Checking issues for Book ID: $book_id\n";
    $stmt = $pdo->prepare("SELECT * FROM issues WHERE book_id = ? AND status = 'issued'");
    $stmt->execute([$book_id]);
    $issues = $stmt->fetchAll();
    
    echo "Active Issues Found: " . count($issues) . "\n";
    foreach ($issues as $i) {
        echo "Issue ID: {$i['id']} | Member ID: {$i['member_id']} | Date: {$i['issue_date']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
