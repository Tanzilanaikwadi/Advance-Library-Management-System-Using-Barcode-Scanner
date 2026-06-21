<?php
require_once 'config/db.php';
include 'header.php';

$stmt = $pdo->query("SELECT * FROM books ORDER BY id DESC");
$books = $stmt->fetchAll();
?>

<div class="page-header mb-8 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
    <h1 class="page-title text-2xl font-bold text-slate-800">Book Barcodes</h1>
    <div class="flex flex-col sm:flex-row gap-3">
        <button onclick="window.print()" class="bg-slate-600 hover:bg-slate-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors w-full sm:w-auto shadow-sm">Print List</button>
        <a href="export.php?type=books" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-lg font-medium transition-colors flex justify-center items-center gap-2 w-full sm:w-auto shadow-sm no-underline">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="generate_barcodes_script.php" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors text-center w-full sm:w-auto shadow-sm no-underline">Regenerate Missing Images</a>
    </div>
</div>

<div class="card bg-white p-0 sm:p-6 shadow-sm rounded-lg overflow-hidden border border-slate-200">
    <div class="overflow-x-auto w-full">
        <table class="w-full text-left min-w-[600px] border-collapse">
            <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                    <th class="p-4">Book Details</th>
                    <th class="p-4 text-center">Barcode ID</th>
                    <th class="p-4 text-center">Barcode Image</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($books as $book): ?>
                    <?php
                    $file = 'uploads/barcodes/' . $book['barcode_id'] . '.png';
                    $src = file_exists($file) ? $file : '';
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="p-4">
                            <strong class="text-slate-800 text-base"><?php echo htmlspecialchars($book['title']); ?></strong><br>
                            <span class="text-slate-500 text-sm"><?php echo htmlspecialchars($book['author']); ?></span>
                            <div class="text-xs text-slate-400 mt-1 font-medium">
                                <?php echo htmlspecialchars($book['category']); ?> | <?php echo htmlspecialchars($book['shelf_location']); ?>
                            </div>
                        </td>
                        <td class="p-4 text-center font-mono text-slate-700 font-medium whitespace-nowrap">
                            <?php echo htmlspecialchars($book['barcode_id']); ?>
                        </td>
                        <td class="p-4 text-center">
                            <?php if ($src): ?>
                                <img src="<?php echo $src; ?>" alt="Barcode" class="h-12 sm:h-16 mx-auto object-contain bg-white p-1 rounded border border-slate-100 shadow-sm">
                            <?php else: ?>
                                <span class="text-red-500 text-xs font-bold bg-red-50 px-2.5 py-1 rounded inline-block whitespace-nowrap border border-red-100">Missing Image</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        .card,
        .card * {
            visibility: visible;
        }

        .card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none;
            border: none;
        }

        .page-header,
        .btn {
            display: none !important;
        }
    }
</style>

<?php include 'footer.php'; ?>