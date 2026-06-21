<?php
require_once 'config/db.php';
include 'header.php';

// Filter inputs
$member_filter = isset($_GET['member']) ? trim($_GET['member']) : '';
$book_filter = isset($_GET['book']) ? trim($_GET['book']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$dept_filter = isset($_GET['dept']) ? trim($_GET['dept']) : '';
$year_filter = isset($_GET['year']) ? trim($_GET['year']) : '';
$prn_filter = isset($_GET['prn']) ? trim($_GET['prn']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build Query
$sql = "SELECT i.*, 
               m.name as member_name, 
               m.barcode_id as member_barcode, 
               m.department,
               m.admission_year, 
               m.prn_number,
               m.photo_path,
               b.title as book_title, 
               b.barcode_id as book_barcode 
        FROM issues i 
        JOIN members m ON i.member_id = m.id 
        JOIN books b ON i.book_id = b.id 
        WHERE 1=1";

$params = [];

if ($member_filter) {
    $sql .= " AND (m.name LIKE ? OR m.barcode_id LIKE ?)";
    $params[] = "%$member_filter%";
    $params[] = "%$member_filter%";
}

if ($book_filter) {
    $sql .= " AND (b.title LIKE ? OR b.barcode_id LIKE ?)";
    $params[] = "%$book_filter%";
    $params[] = "%$book_filter%";
}

if ($status_filter) {
    if ($status_filter === 'overdue') {
        $sql .= " AND i.status = 'issued' AND i.due_date < NOW()";
    } else {
        $sql .= " AND i.status = ?";
        $params[] = $status_filter;
    }
}

if ($dept_filter) {
    $sql .= " AND m.department LIKE ?";
    $params[] = "%$dept_filter%";
}

if ($year_filter) {
    $sql .= " AND m.admission_year LIKE ?";
    $params[] = "%$year_filter%";
}

if ($prn_filter) {
    $sql .= " AND m.prn_number LIKE ?";
    $params[] = "%$prn_filter%";
}

if ($date_from) {
    $sql .= " AND DATE(i.issue_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $sql .= " AND DATE(i.issue_date) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY i.issue_date DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<div class="page-header flex flex-col sm:flex-row justify-between sm:items-center items-start gap-4 mb-6">
    <div>
        <h1 class="page-title text-2xl font-bold text-slate-800">Transaction History</h1>
        <p class="text-slate-500 mt-1">Filter and view issue/return logs.</p>
    </div>
    <a href="export.php?type=transactions" class="btn bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-lg font-medium transition-colors flex items-center gap-2">
        <i class="fas fa-file-excel"></i> Export History
    </a>
</div>

<!-- Filter Section -->
<div class="card bg-white shadow-sm rounded-lg p-4 sm:p-6 mb-6">
    <form method="GET">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">

            <!-- Member Filter -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Member (Name/ID)</label>
                <input type="text" name="member" value="<?php echo htmlspecialchars($member_filter); ?>"
                    placeholder="Search name or ID..."
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Department Filter -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Department</label>
                <input type="text" name="dept" value="<?php echo htmlspecialchars($dept_filter); ?>"
                    placeholder="Search department..."
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Admission Year Filter -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Admission Year</label>
                <input type="text" name="year" value="<?php echo htmlspecialchars($year_filter); ?>"
                    placeholder="YYYY"
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- PRN Filter -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">PRN Number</label>
                <input type="text" name="prn" value="<?php echo htmlspecialchars($prn_filter); ?>"
                    placeholder="PRN..."
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Book Filter -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Book (Title/ID)</label>
                <input type="text" name="book" value="<?php echo htmlspecialchars($book_filter); ?>"
                    placeholder="Search title or barcode..."
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" class="w-full p-2 border border-slate-300 rounded-lg bg-white outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="issued" <?php if ($status_filter == 'issued') echo 'selected'; ?>>Issued</option>
                    <option value="returned" <?php if ($status_filter == 'returned') echo 'selected'; ?>>Returned</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Actions -->
            <div class="col-span-1 sm:col-span-2 md:col-span-4 flex flex-col sm:flex-row gap-3 mt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors text-center w-full sm:w-auto">
                    Filter Results
                </button>
                <a href="transactions.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-lg font-medium transition-colors text-center border border-slate-300 w-full sm:w-auto">
                    Reset Filters
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
                    <th class="p-4">Transaction Info</th>
                    <th class="p-4">Member</th>
                    <th class="p-4">Book Details</th>
                    <th class="p-4">Important Dates</th>
                    <th class="p-4">Status & Fine</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (count($transactions) > 0): ?>
                    <?php foreach ($transactions as $t): ?>
                        <?php
                        // Calculate overdue status
                        $is_overdue = false;
                        $days_overdue = 0;
                        if ($t['status'] == 'issued') {
                            $due_timestamp = strtotime($t['due_date']);
                            // Check if due date is in the past
                            if (time() > $due_timestamp) {
                                $is_overdue = true;
                                $days_overdue = floor((time() - $due_timestamp) / (60 * 60 * 24));
                            }
                        }
                        ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; font-size: 14px;">
                            <td style="padding: 12px; color: #64748b; vertical-align: top;">
                                <div style="font-weight: 600; color: #1e293b;">#<?php echo $t['id']; ?></div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                    <?php echo date('H:i', strtotime($t['issue_date'])); ?>
                                </div>
                            </td>

                            <!-- Member Column with Photo -->
                            <td style="padding: 12px; vertical-align: top;">
                                <div style="display: flex; gap: 10px;">
                                    <?php if (!empty($t['photo_path']) && file_exists($t['photo_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($t['photo_path']); ?>"
                                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 18px;">
                                            <!-- SVG User Icon -->
                                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($t['member_name']); ?></div>
                                        <div style="font-size: 11px; font-family: monospace; color: #64748b; margin-top: 2px; background: #f1f5f9; padding: 1px 4px; border-radius: 4px; display: inline-block;">
                                            <?php echo htmlspecialchars($t['member_barcode']); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                            <?php if ($t['department']) echo htmlspecialchars($t['department']); ?>
                                            <?php if ($t['department'] && $t['admission_year']) echo ' • '; ?>
                                            <?php if ($t['admission_year']) echo htmlspecialchars($t['admission_year']); ?>
                                        </div>
                                        <?php if ($t['prn_number']): ?>
                                            <div style="font-size: 11px; color: #94a3b8;">PRN: <?php echo htmlspecialchars($t['prn_number']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Book Column -->
                            <td style="padding: 12px; vertical-align: top;">
                                <div style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($t['book_title']); ?></div>
                                <div style="font-size: 11px; font-family: monospace; color: #64748b; margin-top: 2px;">
                                    <?php echo htmlspecialchars($t['book_barcode']); ?>
                                </div>
                            </td>

                            <!-- Dates Column -->
                            <td style="padding: 12px; vertical-align: top; font-size: 13px;">
                                <div style="color: #475569; margin-bottom: 4px;">
                                    <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600;">Issued:</span>
                                    <?php echo date('M d, Y', strtotime($t['issue_date'])); ?>
                                </div>

                                <?php if ($t['status'] == 'returned'): ?>
                                    <div style="color: #059669; margin-bottom: 4px;">
                                        <span style="font-size: 11px; color: #059669; text-transform: uppercase; font-weight: 600;">Returned:</span>
                                        <?php echo date('M d, Y', strtotime($t['return_date'])); ?>
                                    </div>
                                <?php else: ?>
                                    <?php if ($is_overdue): ?>
                                        <div style="color: #ef4444; font-weight: 500;">
                                            <span style="font-size: 11px; color: #ef4444; text-transform: uppercase; font-weight: 600;">Due:</span>
                                            <?php echo date('M d, Y', strtotime($t['due_date'])); ?>
                                            <div style="font-size: 11px; background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">
                                                <?php echo $days_overdue; ?> days overdue
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #475569;">
                                            <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600;">Due:</span>
                                            <?php echo date('M d, Y', strtotime($t['due_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <!-- Status Column -->
                            <td style="padding: 12px; vertical-align: top;">
                                <div style="margin-bottom: 5px;">
                                    <?php if ($t['status'] === 'issued'): ?>
                                        <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-block;">Issued</span>
                                    <?php elseif ($t['status'] === 'returned'): ?>
                                        <span style="background: #d1fae5; color: #059669; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-block;">Returned</span>
                                    <?php else: ?>
                                        <span style="background: #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-block;"><?php echo htmlspecialchars($t['status']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($t['fine_amount'] > 0): ?>
                                    <div style="color: #dc2626; font-size: 13px; font-weight: 500;">
                                        Fine: $<?php echo number_format($t['fine_amount'], 2); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: #64748b;">
                            <div style="font-size: 16px; font-weight: 500;">No transactions found</div>
                            <p style="margin-top: 5px; font-size: 14px;">Try adjusting your search filters.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include 'footer.php'; ?>