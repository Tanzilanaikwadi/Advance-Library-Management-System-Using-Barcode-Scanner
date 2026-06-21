<?php
require_once 'config/db.php';
include 'header.php';

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book_id'])) {
    $delete_id = $_POST['delete_book_id'];
    
    // Check for active issues
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE book_id = ? AND status = 'issued'");
    $stmt->execute([$delete_id]);
    if ($stmt->fetchColumn() > 0) {
        $error_msg = "Cannot delete book: Copies are currently issued to members.";
    } else {
        // Delete issues history first
        $stmt = $pdo->prepare("DELETE FROM issues WHERE book_id = ?");
        $stmt->execute([$delete_id]);
        
        // Delete book
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            $success_msg = "Book deleted successfully.";
        } else {
            $error_msg = "Failed to delete book.";
        }
    }
}

// Filter inputs
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build Query
$sql = "SELECT * FROM books WHERE 1=1";
$params = [];

if ($search_query) {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR barcode_id LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if ($category_filter) {
    $sql .= " AND category LIKE ?";
    $params[] = "%$category_filter%";
}

$sql .= " ORDER BY id DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();
?>

<div class="page-header flex flex-col sm:flex-row justify-between sm:items-center items-start gap-4 mb-6">
    <div>
        <h1 class="page-title text-2xl font-bold text-slate-800">All Books</h1>
        <p class="text-slate-500 mt-1">View and manage library book inventory.</p>
    </div>
    <a href="add_book.php" class="btn bg-official-600 hover:bg-official-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        Add Book
    </a>
</div>

<?php if ($success_msg): ?>
<div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
    <?php echo htmlspecialchars($success_msg); ?>
</div>
<?php endif; ?>

<?php if ($error_msg): ?>
<div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <?php echo htmlspecialchars($error_msg); ?>
</div>
<?php endif; ?>

<!-- Filter Section -->
<div class="card bg-white shadow-sm rounded-lg p-4 sm:p-6 mb-6">
    <form method="GET">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 items-end">
            <!-- Search -->
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-1">Search Books</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>"
                    placeholder="Title, Author, Barcode..."
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                <select name="category" class="w-full p-2 border border-slate-300 rounded-lg bg-white outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <option value="Fiction" <?php if ($category_filter === 'Fiction') echo 'selected'; ?>>Fiction</option>
                    <option value="Science" <?php if ($category_filter === 'Science') echo 'selected'; ?>>Science</option>
                    <option value="Technology" <?php if ($category_filter === 'Technology') echo 'selected'; ?>>Technology</option>
                    <option value="History" <?php if ($category_filter === 'History') echo 'selected'; ?>>History</option>
                    <option value="Biography" <?php if ($category_filter === 'Biography') echo 'selected'; ?>>Biography</option>
                    <option value="Reference" <?php if ($category_filter === 'Reference') echo 'selected'; ?>>Reference</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 mt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors text-center w-full sm:w-auto">
                    Filter
                </button>
                <a href="view_books.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-lg font-medium transition-colors text-center border border-slate-300 w-full sm:w-auto">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<div class="card bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="overflow-x-auto w-full">
        <table class="w-full text-left min-w-[800px] border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
                    <th class="p-4">Book Details</th>
                    <th class="p-4">Category & Location</th>
                    <th class="p-4">Inventory</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (count($books) > 0): ?>
                    <?php foreach ($books as $b): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; font-size: 14px;">
                            <!-- Book Details -->
                            <td style="padding: 12px; vertical-align: top;">
                                <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    Author: <?php echo htmlspecialchars($b['author']); ?>
                                </div>
                                <div style="font-size: 11px; font-family: monospace; color: #64748b; margin-top: 4px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; display: inline-block;">
                                    <?php echo htmlspecialchars($b['barcode_id']); ?>
                                </div>
                            </td>

                            <!-- Category & Location -->
                            <td style="padding: 12px; vertical-align: top; color: #475569;">
                                <?php if ($b['category']): ?>
                                    <div style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($b['category']); ?></div>
                                <?php endif; ?>
                                <?php if ($b['shelf_location']): ?>
                                    <div style="font-size: 12px; margin-top: 2px;">Shelf: <?php echo htmlspecialchars($b['shelf_location']); ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Inventory Info -->
                            <td style="padding: 12px; vertical-align: top; font-size: 13px; color: #475569;">
                                <div><span style="color: #64748b;">Total Copies:</span> <span style="font-weight:600;"><?php echo htmlspecialchars($b['total_copies']); ?></span></div>
                                <?php if ($b['available_copies'] > 0): ?>
                                    <div style="margin-top:2px;"><span style="color: #64748b;">Available:</span> <span style="font-weight:600; color:#059669;"><?php echo htmlspecialchars($b['available_copies']); ?></span></div>
                                <?php else: ?>
                                    <div style="margin-top:2px; font-weight:600; color:#ef4444;">Out of Stock</div>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td style="padding: 12px; vertical-align: top; text-align: right;">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this book? This action cannot be undone.');">
                                    <input type="hidden" name="delete_book_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded text-sm font-medium transition-colors inline-flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="padding: 40px; text-align: center; color: #64748b;">
                            <div style="font-size: 16px; font-weight: 500;">No books found</div>
                            <p style="margin-top: 5px; font-size: 14px;">Try adjusting your search filters.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
