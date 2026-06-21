<?php
require_once 'config/db.php';

try {
    echo "🔧 Setting up test data for barcode scanner...\n\n";

    // 1. Add sample members
    echo "📌 Adding sample members...\n";
    
    $members = [
        [
            'barcode_id' => 'M001001',
            'prn_number' => 'PRN001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '9876543210',
            'department' => 'Computer Science',
            'admission_year' => '2023',
            'status' => 'active',
            'max_issue_limit' => 3
        ],
        [
            'barcode_id' => 'M001002',
            'prn_number' => 'PRN002',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '9876543211',
            'department' => 'Electronics',
            'admission_year' => '2023',
            'status' => 'active',
            'max_issue_limit' => 3
        ],
        [
            'barcode_id' => 'M001003',
            'prn_number' => 'PRN003',
            'name' => 'Alex Johnson',
            'email' => 'alex@example.com',
            'phone' => '9876543212',
            'department' => 'Mechanical',
            'admission_year' => '2022',
            'status' => 'active',
            'max_issue_limit' => 3
        ]
    ];

    foreach ($members as $member) {
        // Check if member already exists
        $stmt = $pdo->prepare("SELECT id FROM members WHERE barcode_id = ?");
        $stmt->execute([$member['barcode_id']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO members (barcode_id, prn_number, name, email, phone, department, admission_year, status, max_issue_limit) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $member['barcode_id'],
                $member['prn_number'],
                $member['name'],
                $member['email'],
                $member['phone'],
                $member['department'],
                $member['admission_year'],
                $member['status'],
                $member['max_issue_limit']
            ]);
            echo "  ✅ Added member: {$member['name']} (Barcode: {$member['barcode_id']})\n";
        } else {
            echo "  ⏭️  Member {$member['name']} already exists\n";
        }
    }

    echo "\n";

    // 2. Add sample books
    echo "📌 Adding sample books...\n";
    
    $books = [
        [
            'barcode_id' => 'BK230001',
            'title' => 'Introduction to PHP',
            'author' => 'David Sklar',
            'category' => 'Programming',
            'shelf_location' => 'A-101',
            'total_copies' => 5,
            'available_copies' => 5
        ],
        [
            'barcode_id' => 'BK230002',
            'title' => 'Python for Everyone',
            'author' => 'Charles Severance',
            'category' => 'Programming',
            'shelf_location' => 'A-102',
            'total_copies' => 3,
            'available_copies' => 2
        ],
        [
            'barcode_id' => 'BK230003',
            'title' => 'Data Structures and Algorithms',
            'author' => 'Robert Lafore',
            'category' => 'Computer Science',
            'shelf_location' => 'B-101',
            'total_copies' => 4,
            'available_copies' => 1
        ],
        [
            'barcode_id' => 'BK230004',
            'title' => 'Web Development with HTML/CSS',
            'author' => 'Jon Duckett',
            'category' => 'Web Development',
            'shelf_location' => 'A-105',
            'total_copies' => 6,
            'available_copies' => 4
        ],
        [
            'barcode_id' => 'BK230005',
            'title' => 'JavaScript: The Definitive Guide',
            'author' => 'David Flanagan',
            'category' => 'Programming',
            'shelf_location' => 'A-103',
            'total_copies' => 2,
            'available_copies' => 1
        ]
    ];

    foreach ($books as $book) {
        // Check if book already exists
        $stmt = $pdo->prepare("SELECT id FROM books WHERE barcode_id = ?");
        $stmt->execute([$book['barcode_id']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO books (barcode_id, title, author, category, shelf_location, total_copies, available_copies) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $book['barcode_id'],
                $book['title'],
                $book['author'],
                $book['category'],
                $book['shelf_location'],
                $book['total_copies'],
                $book['available_copies']
            ]);
            echo "  ✅ Added book: {$book['title']} (Barcode: {$book['barcode_id']})\n";
        } else {
            echo "  ⏭️  Book {$book['title']} already exists\n";
        }
    }

    echo "\n";

    // 3. Add some test issues (books issued to members)
    echo "📌 Adding sample book issues...\n";
    
    // Get first member
    $stmt = $pdo->prepare("SELECT id FROM members WHERE barcode_id = 'M001001' LIMIT 1");
    $stmt->execute();
    $member1 = $stmt->fetch();
    
    // Get books
    $stmt = $pdo->prepare("SELECT id FROM books WHERE barcode_id IN ('BK230001', 'BK230002') LIMIT 2");
    $stmt->execute();
    $books_list = $stmt->fetchAll();

    if ($member1 && count($books_list) >= 2) {
        // Check if issues already exist
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE member_id = ?");
        $stmt->execute([$member1['id']]);
        $issue_count = $stmt->fetchColumn();

        if ($issue_count == 0) {
            // Add issue for first book
            $due_date = date('Y-m-d', strtotime('+14 days'));
            $stmt = $pdo->prepare("
                INSERT INTO issues (member_id, book_id, issue_date, due_date, status) 
                VALUES (?, ?, NOW(), ?, 'issued')
            ");
            $stmt->execute([$member1['id'], $books_list[0]['id'], $due_date]);
            echo "  ✅ Issued book to John Doe (Due: $due_date)\n";

            // Add another issue for second book
            $stmt->execute([$member1['id'], $books_list[1]['id'], $due_date]);
            echo "  ✅ Issued another book to John Doe (Due: $due_date)\n";

            // Update available copies
            $update_stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
            $update_stmt->execute([$books_list[0]['id']]);
            $update_stmt->execute([$books_list[1]['id']]);
        } else {
            echo "  ⏭️  Issues already exist for members\n";
        }
    }

    echo "\n✅ Test data setup completed!\n\n";
    echo "🧪 Test Barcodes to Scan:\n";
    echo "   Member Barcodes:\n";
    echo "     • M001001 (John Doe with 2 books issued)\n";
    echo "     • M001002 (Jane Smith)\n";
    echo "     • M001003 (Alex Johnson)\n";
    echo "   Book Barcodes:\n";
    echo "     • BK230001 (PHP book - 5 copies available)\n";
    echo "     • BK230002 (Python book - 2 copies available)\n";
    echo "     • BK230003 (Data Structures - 1 copy available)\n";
    echo "     • BK230004 (Web Dev - 4 copies available)\n";
    echo "     • BK230005 (JavaScript - 1 copy available)\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
