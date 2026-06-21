<?php
require_once 'config/db.php';

try {
    echo "🔧 Starting Database Migration...\n\n";

    // 1. Check and add prn_number column to members table
    $check = $pdo->query("SHOW COLUMNS FROM members LIKE 'prn_number'");
    if ($check->rowCount() === 0) {
        echo "📌 Adding 'prn_number' column to members table...\n";
        $pdo->exec("ALTER TABLE members ADD COLUMN prn_number VARCHAR(50) UNIQUE AFTER barcode_id");
        echo "✅ 'prn_number' column added successfully!\n\n";
    } else {
        echo "✅ 'prn_number' column already exists.\n\n";
    }

    // 2. Check and add password column to members table
    $check = $pdo->query("SHOW COLUMNS FROM members LIKE 'password'");
    if ($check->rowCount() === 0) {
        echo "📌 Adding 'password' column to members table...\n";
        $pdo->exec("ALTER TABLE members ADD COLUMN password VARCHAR(255) AFTER status");
        echo "✅ 'password' column added successfully!\n\n";
    } else {
        echo "✅ 'password' column already exists.\n\n";
    }

    // 3. Check and add department column to members table
    $check = $pdo->query("SHOW COLUMNS FROM members LIKE 'department'");
    if ($check->rowCount() === 0) {
        echo "📌 Adding 'department' column to members table...\n";
        $pdo->exec("ALTER TABLE members ADD COLUMN department VARCHAR(100) AFTER address");
        echo "✅ 'department' column added successfully!\n\n";
    } else {
        echo "✅ 'department' column already exists.\n\n";
    }

    // 4. Check and add admission_year column to members table
    $check = $pdo->query("SHOW COLUMNS FROM members LIKE 'admission_year'");
    if ($check->rowCount() === 0) {
        echo "📌 Adding 'admission_year' column to members table...\n";
        $pdo->exec("ALTER TABLE members ADD COLUMN admission_year VARCHAR(10) AFTER department");
        echo "✅ 'admission_year' column added successfully!\n\n";
    } else {
        echo "✅ 'admission_year' column already exists.\n\n";
    }

    // 5. Check and add photo_path column to members table
    $check = $pdo->query("SHOW COLUMNS FROM members LIKE 'photo_path'");
    if ($check->rowCount() === 0) {
        echo "📌 Adding 'photo_path' column to members table...\n";
        $pdo->exec("ALTER TABLE members ADD COLUMN photo_path TEXT AFTER password");
        echo "✅ 'photo_path' column added successfully!\n\n";
    } else {
        echo "✅ 'photo_path' column already exists.\n\n";
    }

    echo "🎉 Database migration completed successfully!\n";
    echo "\n📋 Members table structure:\n";
    
    $columns = $pdo->query("SHOW COLUMNS FROM members");
    $cols = $columns->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cols as $col) {
        echo "  • {$col['Field']} ({$col['Type']})\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
