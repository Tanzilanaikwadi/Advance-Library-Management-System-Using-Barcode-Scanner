<?php
require_once 'config/db.php';
include 'header.php';

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_member_id'])) {
    $delete_id = $_POST['delete_member_id'];
    
    // Check for active issues
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE member_id = ? AND status = 'issued'");
    $stmt->execute([$delete_id]);
    if ($stmt->fetchColumn() > 0) {
        $error_msg = "Cannot delete member: They currently have active books issued.";
    } else {
        // Delete issues history first (to avoid constraint errors if any)
        $stmt = $pdo->prepare("DELETE FROM issues WHERE member_id = ?");
        $stmt->execute([$delete_id]);
        
        // Delete member
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            $success_msg = "Member deleted successfully.";
        } else {
            $error_msg = "Failed to delete member.";
        }
    }
}

// Filter inputs
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$dept_filter = isset($_GET['dept']) ? trim($_GET['dept']) : '';

// Build Query
$sql = "SELECT * FROM members WHERE 1=1";
$params = [];

if ($search_query) {
    $sql .= " AND (name LIKE ? OR barcode_id LIKE ? OR prn_number LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if ($status_filter) {
    if ($status_filter === 'active' || $status_filter === 'inactive') {
      $sql .= " AND status = ?";
      $params[] = $status_filter;
    }
}

if ($dept_filter) {
    $sql .= " AND department LIKE ?";
    $params[] = "%$dept_filter%";
}

$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();
?>

<div class="page-header flex flex-col sm:flex-row justify-between sm:items-center items-start gap-4 mb-6">
    <div>
        <h1 class="page-title text-2xl font-bold text-slate-800">All Members</h1>
        <p class="text-slate-500 mt-1">View and manage registered library members.</p>
    </div>
    <a href="add_member.php" class="btn bg-official-600 hover:bg-official-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
        Add Member
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
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Search (Name, ID, PRN)</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>"
                    placeholder="Search query..."
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Department Filter -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Department</label>
                <input type="text" name="dept" value="<?php echo htmlspecialchars($dept_filter); ?>"
                    placeholder="E.g. Computer Science"
                    class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" class="w-full p-2 border border-slate-300 rounded-lg bg-white outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="active" <?php if ($status_filter == 'active') echo 'selected'; ?>>Active</option>
                    <option value="inactive" <?php if ($status_filter == 'inactive') echo 'selected'; ?>>Inactive</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-span-1 sm:col-span-2 md:col-span-4 flex flex-col sm:flex-row gap-3 mt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors text-center w-full sm:w-auto">
                    Filter Results
                </button>
                <a href="view_members.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-lg font-medium transition-colors text-center border border-slate-300 w-full sm:w-auto">
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
                    <th class="p-4">Member Info</th>
                    <th class="p-4">Academic Details</th>
                    <th class="p-4">Contact</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (count($members) > 0): ?>
                    <?php foreach ($members as $m): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; font-size: 14px;">
                            <!-- Member Info -->
                            <td style="padding: 12px; vertical-align: top;">
                                <div style="display: flex; gap: 10px;">
                                    <?php if (!empty($m['photo_path']) && file_exists($m['photo_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($m['photo_path']); ?>"
                                            style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0;">
                                    <?php else: ?>
                                        <div style="width: 48px; height: 48px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 20px;">
                                            <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($m['name']); ?></div>
                                        <div style="font-size: 11px; font-family: monospace; color: #64748b; margin-top: 2px; background: #f1f5f9; padding: 1px 4px; border-radius: 4px; display: inline-block;">
                                            Library ID: <?php echo htmlspecialchars($m['barcode_id']); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                                           Joined: <?php echo date('M d, Y', strtotime($m['created_at'])); ?> 
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Academic Info -->
                            <td style="padding: 12px; vertical-align: top; color: #475569;">
                                <?php if ($m['department']): ?>
                                    <div style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($m['department']); ?></div>
                                <?php endif; ?>
                                <?php if ($m['admission_year']): ?>
                                    <div style="font-size: 12px; margin-top: 2px;">Batch of <?php echo htmlspecialchars($m['admission_year']); ?></div>
                                <?php endif; ?>
                                <?php if ($m['prn_number']): ?>
                                    <div style="font-size: 12px; font-family: monospace; margin-top: 2px;">PRN: <?php echo htmlspecialchars($m['prn_number']); ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Contact Info -->
                            <td style="padding: 12px; vertical-align: top; font-size: 13px; color: #475569;">
                                <?php if ($m['phone']): ?>
                                    <div style="margin-bottom: 2px;">
                                        <span style="color: #64748b;">Phone:</span> <?php echo htmlspecialchars($m['phone']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($m['email']): ?>
                                    <div>
                                        <span style="color: #64748b;">Email:</span> <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>" style="color: #2563eb; text-decoration: none;"><?php echo htmlspecialchars($m['email']); ?></a>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$m['phone'] && !$m['email']): ?>
                                    <span style="color:#94a3b8; font-style: italic;">No contact provided</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status Column -->
                            <td style="padding: 12px; vertical-align: top;">
                                <?php if ($m['status'] === 'active'): ?>
                                    <span style="background: #d1fae5; color: #059669; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-block;">Active</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-block;"><?php echo htmlspecialchars(ucfirst($m['status'])); ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td style="padding: 12px; vertical-align: top; text-align: right;">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this member? This action cannot be undone.');">
                                    <input type="hidden" name="delete_member_id" value="<?php echo $m['id']; ?>">
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
                        <td colspan="5" style="padding: 40px; text-align: center; color: #64748b;">
                            <div style="font-size: 16px; font-weight: 500;">No members found</div>
                            <p style="margin-top: 5px; font-size: 14px;">Try adjusting your search filters.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include 'footer.php'; ?>
