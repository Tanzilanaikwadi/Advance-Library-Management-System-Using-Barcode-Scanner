<?php
require 'config/db.php';

try {
    $book_id = 3;
    echo "--- Detailed Issue Check for Book ID $book_id ---\n";
    
    // Get ALL issues for this book regardless of status
    $stmt = $pdo->prepare("SELECT * FROM issues WHERE book_id = ?");
    $stmt->execute([$book_id]);
    $all_issues = $stmt->fetchAll();
    
    echo "Total Issue Records: " . count($all_issues) . "\n";
    
    $active_count = 0;
    foreach ($all_issues as $i) {
        $status = $i['status']; // e.g., 'issued' or 'returned'
        if ($status == 'issued') $active_count++;
        echo "ID: {$i['id']} | Member: {$i['member_id']} | Status: $status | Issued: {$i['issue_date']} | Returned: {$i['return_date']}\n";
    }
    
    echo "--------------------------\n";
    echo "Calculated Active Issued: $active_count\n";
    
    // Get book details
    $stmt = $pdo->prepare("SELECT total_copies, available_copies FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    echo "Book Table Says -> Total: {$book['total_copies']} | Available: {$book['available_copies']}\n";
    
    $expected_available = $book['total_copies'] - $active_count;
    echo "Expected Available: $expected_available\n";
    
    if ($book['available_copies'] != $expected_available) {
        echo "MISMATCH DETECTED! Fixing...\n";
        $upd = $pdo->prepare("UPDATE books SET available_copies = ? WHERE id = ?");
        $upd->execute([$expected_available, $book_id]);
        echo "Fixed to $expected_available.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
