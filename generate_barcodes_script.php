<?php
require_once 'config/db.php';
include 'header.php';
require_once 'lib_barcode.php';

// Fetch all books
$stmt = $pdo->query("SELECT id, title, barcode_id FROM books");
$books = $stmt->fetchAll();

$count = 0;
$generated = 0;
$skipped = 0;
$errors = 0;
?>

<div class="page-header">
    <h1 class="page-title">Barcode Generation Report</h1>
    <a href="book_barcodes.php" class="btn" style="background:#64748b;">Back to List</a>
</div>

<div class="card">
    <p>Starting process for <?php echo count($books); ?> books...</p>
    <ul style="max-height: 400px; overflow-y: auto; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">

    <?php
    // Ensure directory exists
    if (!is_dir('uploads/barcodes')) {
        mkdir('uploads/barcodes', 0777, true);
    }

    foreach ($books as $book) {
        $barcode = $book['barcode_id'];
        
        if (empty($barcode)) {
            echo "<li style='color:red'>Book ID {$book['id']} ({$book['title']}) has no barcode string in DB!</li>";
            // Optional: Generate a barcode string for it? 
            // For now, adhere to requirement of generating IMAGES for existing barcodes.
            continue;
        }

        $fileName = 'uploads/barcodes/' . preg_replace('/[^A-Za-z0-9\-]/', '', $barcode) . '.png';
        
        if (file_exists($fileName)) {
            $skipped++;
        } else {
            try {
                $res = SimpleBarcode::generateAndSave($barcode, $fileName);
                if ($res) {
                    echo "<li style='color:green'>✅ Generated: $barcode for '{$book['title']}'</li>";
                    $generated++;
                } else {
                    echo "<li style='color:red'>❌ Failed to generate: $barcode</li>";
                    $errors++;
                }
            } catch (Exception $e) {
                echo "<li style='color:red'>❌ Error: $barcode - " . $e->getMessage() . "</li>";
                $errors++;
            }
        }
        $count++;
    }
    ?>
    </ul>

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
        <h2>Summary</h2>
        <p><strong>Total Books:</strong> <?php echo count($books); ?></p>
        <p><strong>Newly Generated:</strong> <?php echo $generated; ?></p>
        <p><strong>Skipped (Already Existed):</strong> <?php echo $skipped; ?></p>
        <p><strong>Errors:</strong> <?php echo $errors; ?></p>
    </div>
</div>

<?php include 'footer.php'; ?>
