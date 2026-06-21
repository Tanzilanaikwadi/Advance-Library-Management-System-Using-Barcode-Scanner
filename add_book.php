<?php
require_once 'config/db.php';
include 'header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category = trim($_POST['category']);
    $shelf = trim($_POST['shelf']);
    $copies = (int) $_POST['copies'];
    $manual_barcode = trim($_POST['barcode']);

    if (empty($title) || empty($author) || $copies < 1) {
        $error = "Title, Author, and valid Copies are required.";
    } else {
        try {
            // 1. SMART CHECK: Does this Title + Author already exist?
            // Case-insensitive check usually good, using LOWER() if needed, but simple match for now
            $stmt = $pdo->prepare("SELECT * FROM books WHERE title = ? AND author = ?");
            $stmt->execute([$title, $author]);
            $existing_book = $stmt->fetch();

            if ($existing_book) {
                // MERGE STOCK
                $new_total = $existing_book['total_copies'] + $copies;
                $new_available = $existing_book['available_copies'] + $copies;

                $update = $pdo->prepare("UPDATE books SET total_copies = ?, available_copies = ? WHERE id = ?");
                $update->execute([$new_total, $new_available, $existing_book['id']]);

                $message = "<strong>Stock Updated!</strong> Found existing book '{$title}' (Barcode: <strong>{$existing_book['barcode_id']}</strong>). Added $copies copies. Total: $new_total.";

                // Show barcode for printing
                $new_book_barcode = $existing_book['barcode_id'];
            } else {
                // CREATE NEW BOOK

                // Determine Barcode
                if (!empty($manual_barcode)) {
                    $barcode_to_use = $manual_barcode;
                    // Check strict uniqueness of manual barcode just in case
                    $chk = $pdo->prepare("SELECT id FROM books WHERE barcode_id = ?");
                    $chk->execute([$barcode_to_use]);
                    if ($chk->fetch()) throw new Exception("Barcode '$barcode_to_use' is already used by another different book.");
                } else {
                    // Auto-Generate Unique Barcode: BK-TIMESTAMP-RAND
                    $barcode_to_use = 'BK-' . date('ymd') . '-' . rand(100, 999);
                }

                $stmt = $pdo->prepare("INSERT INTO books (barcode_id, title, author, category, shelf_location, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$barcode_to_use, $title, $author, $category, $shelf, $copies, $copies]);

                // Server-Side Barcode Generation
                require_once 'lib_barcode.php';
                $barcode_file = 'uploads/barcodes/' . $barcode_to_use . '.png';
                // Be careful with paths, need absolute or relative to script execution.
                // add_book is in root, so uploads/barcodes is correct relative path.

                // Ensure directory exists
                if (!is_dir('uploads/barcodes')) mkdir('uploads/barcodes', 0777, true);

                $gen_status = SimpleBarcode::generateAndSave($barcode_to_use, $barcode_file);

                if ($gen_status) {
                    $message = "<strong>New Book Registered!</strong><br>Barcode Generated: <strong>$barcode_to_use</strong>";
                } else {
                    $message = "<strong>New Book Registered!</strong><br>Warning: Barcode image generation failed for <strong>$barcode_to_use</strong>";
                }

                $new_book_barcode = $barcode_to_use;
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header mb-6">
    <h1 class="page-title text-2xl font-bold text-slate-800">Add / Update Book Inventory</h1>
    <p class="text-slate-500 mt-1">Add new books. If the book (Title+Author) already exists, stock will be updated automatically.</p>
</div>

<?php if ($message): ?>
    <div class="bg-emerald-50 text-emerald-800 p-5 rounded-xl mb-6 border border-emerald-500 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
        <div class="text-base">
            <?php echo $message; ?>
        </div>
        <div class="bg-white p-3 rounded-lg overflow-hidden flex-shrink-0 w-full sm:w-auto flex justify-center">
            <?php
            $b_file = 'uploads/barcodes/' . ($new_book_barcode ?? '') . '.png';
            if (file_exists($b_file)) {
                echo "<img src='$b_file' alt='Barcode' class='max-w-full sm:max-w-[250px]'>";
            } else {
                echo "<span>No Barcode Image</span>";
            }
            ?>
        </div>
    </div>
<?php endif; ?>


<?php if ($error): ?>
    <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card max-w-4xl mx-auto p-4 sm:p-6 bg-white shadow-sm rounded-lg">
    <div class="text-right mb-5">
        <a href="book_barcodes.php" class="text-slate-500 hover:text-slate-800 text-sm font-medium transition-colors">View All Barcodes &rarr;</a>
    </div>
    <form method="POST" id="addBookForm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div class="mb-4">
                <label class="block font-medium mb-2">Barcode (Ready to Scan) <span class="text-green-600 text-xs font-bold">[SCAN HERE FIRST]</span></label>
                <input type="text" id="barcodeInput" name="barcode" placeholder="Scan Barcode or leave empty" autofocus class="w-full p-2.5 border-2 border-emerald-500 rounded-lg bg-emerald-50 font-mono text-base outline-none focus:ring-2 focus:ring-emerald-600">
                <small class="text-slate-500 block mt-1">System will generate one if left blank.</small>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Title <span class="text-red-500">*</span></label>
                <input type="text" id="titleInput" name="title" required placeholder="e.g. Introduction to Algorithms" class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Author <span class="text-red-500">*</span></label>
                <input type="text" name="author" required placeholder="e.g. Thomas Cormen" class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Category</label>
                <select name="category" required class="w-full p-2.5 border border-slate-300 rounded-lg bg-white outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="Fiction">Fiction</option>
                    <option value="Science">Science</option>
                    <option value="Technology">Technology</option>
                    <option value="History">History</option>
                    <option value="Biography">Biography</option>
                    <option value="Reference">Reference</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Shelf Location</label>
                <input type="text" name="shelf" placeholder="e.g. A-12" class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Total Copies <span class="text-red-500">*</span></label>
                <input type="number" name="copies" value="1" min="1" required class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-medium transition-colors">Add Book</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const barcodeInput = document.getElementById('barcodeInput');
        const titleInput = document.getElementById('titleInput');

        if (barcodeInput && titleInput) {
            barcodeInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    // Prevent form submission when scanner sends Enter
                    e.preventDefault();
                    // Move focus to the Title field so user can keep typing
                    titleInput.focus();
                }
            });
        }
    });
</script>

<?php include 'footer.php'; ?>