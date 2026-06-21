<?php
require 'config/db.php';

try {
    echo "--- Syncing Book Copies ---\n";
    
    // Get all books
    $stmt = $pdo->query("SELECT id, title, total_copies FROM books");
    $books = $stmt->fetchAll();
    
    foreach ($books as $b) {
        $book_id = $b['id'];
        $total = $b['total_copies'];
        
        // Count active issues for this book
        $stmt_issues = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE book_id = ? AND status = 'issued'");
        $stmt_issues->execute([$book_id]);
        $issued_count = $stmt_issues->fetchColumn();
        
        // Calculate correct available copies
        $correct_available = $total - $issued_count;
        
        // Update if incorrect
        $stmt_check = $pdo->prepare("SELECT available_copies FROM books WHERE id = ?");
        $stmt_check->execute([$book_id]);
        $current_available = $stmt_check->fetchColumn();
        
        if ($current_available != $correct_available) {
            echo "Fixing Book ID {$book_id} ({$b['title']}): Was $current_available, Now $correct_available (Total: $total, Issued: $issued_count)\n";
            
            $stmt_update = $pdo->prepare("UPDATE books SET available_copies = ? WHERE id = ?");
            $stmt_update->execute([$correct_available, $book_id]);
        } else {
            echo "Book ID {$book_id} is OK.\n";
        }
    }
    
    echo "--- Sync Complete ---\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
