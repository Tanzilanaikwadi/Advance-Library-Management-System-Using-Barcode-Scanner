<?php
require 'config/db.php';

try {
    // 1. Add `password` column to members if not exists
    $columns = $pdo->query("DESCRIBE members")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('password', $columns)) {
        $pdo->exec("ALTER TABLE members ADD COLUMN password VARCHAR(255) AFTER email");
        echo "Added 'password' column to members table.\n";
    } else {
        echo "'password' column already exists.\n";
    }

    // 2. Set default password for existing members (prn_number or barcode_id)
    $stmt = $pdo->query("SELECT id, prn_number, barcode_id FROM members WHERE password IS NULL OR password = ''");
    $members = $stmt->fetchAll();

    $count = 0;
    foreach ($members as $m) {
        $default_pass = !empty($m['prn_number']) ? $m['prn_number'] : $m['barcode_id'];
        
        // Hashing the password
        $hashed_pass = password_hash($default_pass, PASSWORD_DEFAULT);
        
        $update = $pdo->prepare("UPDATE members SET password = ? WHERE id = ?");
        $update->execute([$hashed_pass, $m['id']]);
        $count++;
    }
    
    echo "Updated passwords for $count members (Default: PRN or Barcode).\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
