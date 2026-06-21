<?php
require_once 'config/db.php';

$action = $_GET['action'] ?? '';

// jsonResponse is defined in config/db.php

if ($action === 'get_member') {
    $search = $_GET['barcode'] ?? '';
    
    if (!$search) {
        jsonResponse(false, 'Search term required');
    }

    // Try Barcode or PRN
    $stmt = $pdo->prepare("SELECT * FROM members WHERE barcode_id = ? OR prn_number = ?");
    $stmt->execute([$search, $search]);
    $member = $stmt->fetch();

    if ($member) {
        // Get active issues
        $stmt = $pdo->prepare("
            SELECT i.id, i.due_date, i.issue_date, i.status, b.title, b.barcode_id as book_barcode 
            FROM issues i 
            JOIN books b ON i.book_id = b.id 
            WHERE i.member_id = ? AND i.status = 'issued'
        ");
        $stmt->execute([$member['id']]);
        $active_issues = $stmt->fetchAll();

        // Get Issue History (Last 10 Returned)
        $stmt = $pdo->prepare("
            SELECT i.*, b.title, b.barcode_id as book_barcode 
            FROM issues i 
            JOIN books b ON i.book_id = b.id 
            WHERE i.member_id = ? AND i.status = 'returned'
            ORDER BY i.return_date DESC
            LIMIT 10
        ");
        $stmt->execute([$member['id']]);
        $history = $stmt->fetchAll();

        // Check if blocked
        if ($member['status'] == 'blocked') {
             jsonResponse(false, 'Member is BLOCKED', ['member' => $member, 'history' => []]);
        }
        
        jsonResponse(true, 'Member found', [
            'member' => $member,
            'active_issues' => $active_issues,
            'history' => $history,
            'can_issue' => count($active_issues) < $member['max_issue_limit']
        ]);
    } else {
        jsonResponse(false, 'Member not found');
    }

} elseif ($action === 'get_daily_log') {
    // Fetch today's issues
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT i.*, 
               m.name as member_name, 
               m.barcode_id as member_barcode,
               m.department,
               m.admission_year, 
               m.prn_number,
               b.title as book_title,
               b.barcode_id as book_barcode
        FROM issues i 
        JOIN members m ON i.member_id = m.id 
        JOIN books b ON i.book_id = b.id 
        WHERE DATE(i.issue_date) = ?
        ORDER BY i.issue_date DESC
    ");
    $stmt->execute([$today]);
    $logs = $stmt->fetchAll();
    
    jsonResponse(true, 'Log fetched', ['logs' => $logs]);

} elseif ($action === 'get_book') {
    $barcode = $_GET['barcode'] ?? '';
    
    if (!$barcode) {
        jsonResponse(false, 'Barcode required');
    }

    $stmt = $pdo->prepare("SELECT * FROM books WHERE barcode_id = ?");
    $stmt->execute([$barcode]);
    $book = $stmt->fetch();

    if ($book) {
        if ($book['available_copies'] > 0) {
            jsonResponse(true, 'Book found', ['book' => $book]);
        } else {
            jsonResponse(false, 'Book is out of stock. Please enroll available copies in stock first; the book cannot be issued until stock is available.', ['book' => $book]);
        }
    } else {
        jsonResponse(false, 'Book not found');
    }

} elseif ($action === 'get_book_details') {
    $barcode = $_GET['barcode'] ?? '';
    if (!$barcode) jsonResponse(false, 'Barcode required');

    $stmt = $pdo->prepare("SELECT * FROM books WHERE barcode_id = ?");
    $stmt->execute([$barcode]);
    $book = $stmt->fetch();

    if ($book) {
        $stmt = $pdo->prepare("
            SELECT i.issue_date, i.due_date, m.name, m.barcode_id as member_barcode
            FROM issues i
            JOIN members m ON i.member_id = m.id
            WHERE i.book_id = ? AND i.status = 'issued'
        ");
        $stmt->execute([$book['id']]);
        $borrowers = $stmt->fetchAll();

        // 1. Total Lifetime Borrows
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE book_id = ?");
        $stmt->execute([$book['id']]);
        $total_borrowed = $stmt->fetchColumn();

        // 2. Last Returned Date
        $stmt = $pdo->prepare("SELECT MAX(return_date) FROM issues WHERE book_id = ? AND status = 'returned'");
        $stmt->execute([$book['id']]);
        $last_returned = $stmt->fetchColumn();

        jsonResponse(true, 'Book Found', [
            'book' => $book,
            'borrowers' => $borrowers,
            'stats' => [
                'total_borrowed' => $total_borrowed,
                'last_returned' => $last_returned ? date('d M Y', strtotime($last_returned)) : 'Never'
            ]
        ]);
    } else {
        jsonResponse(false, 'Book not found');
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_barcode') {
    $data = json_decode(file_get_contents('php://input'), true);
    $barcode = $data['barcode'] ?? '';
    $imageData = $data['image'] ?? '';

    if (!$barcode || !$imageData) {
        jsonResponse(false, 'Missing data');
    }

    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $imageContent = base64_decode($imageData);

    $fileName = 'uploads/barcodes/' . preg_replace('/[^A-Za-z0-9\-]/', '', $barcode) . '.png';
    
    if (file_put_contents($fileName, $imageContent)) {
         jsonResponse(true, 'Barcode Saved Successfully', ['file' => $fileName]);
    } else {
         jsonResponse(false, 'Failed to write file');
    }

} elseif ($action === 'scan_barcode') {
    $barcode = $_GET['barcode'] ?? '';
    
    if (!$barcode) {
        jsonResponse(false, 'Barcode required');
    }

    // 1. Check if it's a member barcode
    $stmt = $pdo->prepare("SELECT * FROM members WHERE barcode_id = ?");
    $stmt->execute([$barcode]);
    $member = $stmt->fetch();

    if ($member) {
        // Get active issues
        $stmt = $pdo->prepare("
            SELECT i.id, i.due_date, i.issue_date, i.status, b.title, b.barcode_id as book_barcode, b.id as book_id
            FROM issues i 
            JOIN books b ON i.book_id = b.id 
            WHERE i.member_id = ? AND i.status = 'issued'
        ");
        $stmt->execute([$member['id']]);
        $active_issues = $stmt->fetchAll();

        // Get book details for all active issues
        $book_details = [];
        foreach ($active_issues as $issue) {
            $stmt = $pdo->prepare("SELECT available_copies, total_copies FROM books WHERE id = ?");
            $stmt->execute([$issue['book_id']]);
            $book_details[] = $stmt->fetch();
        }

        jsonResponse(true, 'Member found', [
            'type' => 'member',
            'member' => $member,
            'active_issues' => $active_issues,
            'book_details' => $book_details
        ]);
    }

    // 2. Check if it's a book barcode
    $stmt = $pdo->prepare("SELECT * FROM books WHERE barcode_id = ?");
    $stmt->execute([$barcode]);
    $book = $stmt->fetch();

    if ($book) {
        jsonResponse(true, 'Book found', [
            'type' => 'book',
            'book' => $book
        ]);
    }

    // 3. Not found
    jsonResponse(false, 'Barcode not found in system');
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'issue_book') {
    $data = json_decode(file_get_contents('php://input'), true);
    $member_id = $data['member_id'] ?? null;
    $book_id = $data['book_id'] ?? null;

    if (!$member_id || !$book_id) {
        jsonResponse(false, 'Member ID and Book ID required');
    }

    try {
        // 1. Check member exists and is active
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();

        if (!$member) {
            jsonResponse(false, 'Member not found');
        }

        if ($member['status'] !== 'active') {
            jsonResponse(false, 'Member account is not active');
        }

        // 2. Check book exists and has copies
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();

        if (!$book) {
            jsonResponse(false, 'Book not found');
        }

        if ($book['available_copies'] <= 0) {
            jsonResponse(false, 'No copies available for this book');
        }

        // 3. Check if member already has this book
        $stmt = $pdo->prepare("SELECT id FROM issues WHERE member_id = ? AND book_id = ? AND status = 'issued'");
        $stmt->execute([$member_id, $book_id]);
        if ($stmt->fetch()) {
            jsonResponse(false, 'Member already has this book issued');
        }

        // 4. Check issue limit
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM issues WHERE member_id = ? AND status = 'issued'");
        $stmt->execute([$member_id]);
        $current_issues = $stmt->fetch()['count'];

        if ($current_issues >= $member['max_issue_limit']) {
            jsonResponse(false, 'Member has reached maximum issue limit');
        }

        // 5. Create issue record (14 days due date)
        $due_date = date('Y-m-d H:i:s', strtotime('+14 days'));
        $stmt = $pdo->prepare("
            INSERT INTO issues (member_id, book_id, issue_date, due_date, status, fine_amount)
            VALUES (?, ?, NOW(), ?, 'issued', 0)
        ");
        $stmt->execute([$member_id, $book_id, $due_date]);
        $issue_id = $pdo->lastInsertId();

        // 6. Update book's available copies
        $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
        $stmt->execute([$book_id]);

        jsonResponse(true, 'Book issued successfully', [
            'issue_id' => $issue_id,
            'due_date' => $due_date,
            'book_title' => $book['title'],
            'member_name' => $member['name']
        ]);

    } catch (Exception $e) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    }
    
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_scanned_book') {
        $data = json_decode(file_get_contents('php://input'), true);
        $barcode = trim($data['barcode'] ?? '');
        $title = trim($data['title'] ?? '');
        $author = trim($data['author'] ?? '');
        $category = trim($data['category'] ?? '');
        $shelf = trim($data['shelf_location'] ?? '');
        $total_copies = isset($data['total_copies']) ? (int)$data['total_copies'] : 1;
        $available_copies = isset($data['available_copies']) ? (int)$data['available_copies'] : $total_copies;

        if (!$barcode || !$title || !$author) {
            jsonResponse(false, 'Barcode, title and author are required');
        }

        if ($total_copies < 0) $total_copies = 1;
        if ($available_copies < 0) $available_copies = 0;
        if ($available_copies > $total_copies) $available_copies = $total_copies;

        try {
            // Ensure barcode uniqueness
            $stmt = $pdo->prepare("SELECT id FROM books WHERE barcode_id = ?");
            $stmt->execute([$barcode]);
            if ($stmt->fetch()) {
                jsonResponse(false, 'Book with this barcode already exists');
            }

            $stmt = $pdo->prepare("INSERT INTO books (barcode_id, title, author, category, shelf_location, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$barcode, $title, $author, $category, $shelf, $total_copies, $available_copies]);
            $book_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $newBook = $stmt->fetch();

            jsonResponse(true, 'Book saved', ['book' => $newBook]);
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage());
        }
    
    } else {
        jsonResponse(false, 'Invalid action');
    }
?>
