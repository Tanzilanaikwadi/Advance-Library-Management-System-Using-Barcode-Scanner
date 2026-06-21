<?php
require_once 'config/db.php';

// Ensure only logged in users (Admin) can export
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Access Denied");
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($type === 'books') {
    $filename = "books_export_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Barcode ID', 'Title', 'Author', 'Category', 'Shelf Location', 'Total Copies', 'Available Copies']);
    
    $stmt = $pdo->query("SELECT id, barcode_id, title, author, category, shelf_location, total_copies, available_copies FROM books ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
} 

elseif ($type === 'members') {
    $filename = "members_export_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Barcode ID', 'Name', 'Email', 'Phone', 'Address', 'Department', 'Year', 'PRN', 'Status']);
    
    $stmt = $pdo->query("SELECT id, barcode_id, name, email, phone, address, department, admission_year, prn_number, status FROM members ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

elseif ($type === 'transactions') {
    $filename = "transactions_export_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Trans ID', 'Book Title', 'Book Barcode', 'Member Name', 'Member Barcode', 'Department', 'Issue Date', 'Due Date', 'Return Date', 'Status', 'Fine']);
    
    // We can reuse the filter logic if passed via GET, but here we export ALL recent transactions to be safe and comprehensive
    // Or we can try to replicate the filter logic. For simplicity and robustness, specific export usually means "Current View" or "All".
    // Let's export ALL sorted by date.
    
    $sql = "SELECT i.id, b.title, b.barcode_id as book_barcode, m.name, m.barcode_id as member_barcode, m.department, 
                   i.issue_date, i.due_date, i.return_date, i.status, i.fine_amount
            FROM issues i 
            JOIN books b ON i.book_id = b.id 
            JOIN members m ON i.member_id = m.id 
            ORDER BY i.issue_date DESC";
            
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

else {
    die("Invalid Export Type");
}
?>
