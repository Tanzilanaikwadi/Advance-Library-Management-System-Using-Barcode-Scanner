<?php
require_once 'config/db.php';

// Check if student is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['member_id'];

// Fetch Member Details
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    die("Member not found.");
}

// Fetch Active Loans
$stmt = $pdo->prepare("
    SELECT i.*, b.title, b.author, b.barcode_id 
    FROM issues i 
    JOIN books b ON i.book_id = b.id 
    WHERE i.member_id = ? AND i.status = 'issued'
    ORDER BY i.due_date ASC
");
$stmt->execute([$member_id]);
$active_loans = $stmt->fetchAll();

// Calculate Total Fines (Simple logic: 10 per day overdue)
$total_fines = 0;
foreach ($active_loans as $loan) {
    if (strtotime($loan['due_date']) < time()) {
        $days_over = floor((time() - strtotime($loan['due_date'])) / (60 * 60 * 24));
        if ($days_over > 0) {
            $total_fines += $days_over * 10; // 10 Currency Unit per day
        }
    }
}

// Fetch History
$stmt = $pdo->prepare("
    SELECT i.*, b.title 
    FROM issues i 
    JOIN books b ON i.book_id = b.id 
    WHERE i.member_id = ? AND i.status = 'returned'
    ORDER BY i.return_date DESC
    LIMIT 5
");
$stmt->execute([$member_id]);
$history = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="text-gray-800">

<!-- Navigation -->
<nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-3">
                <div class="bg-indigo-600 text-white p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <span class="font-bold text-xl text-gray-900 tracking-tight">University Library</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500 hidden md:block">Welcome, <?php echo htmlspecialchars($member['name']); ?></span>
                <a href="logout.php" class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Hero Banner -->
    <div class="mb-8 rounded-2xl overflow-hidden shadow-lg relative bg-gray-900 border border-gray-800">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 z-0">
            <img src="assets/library_bg.png" alt="Library Background" class="w-full h-full object-cover opacity-50">
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-indigo-900/80 to-transparent"></div>
        </div>
        
        <!-- Content -->
        <div class="relative z-10 p-8 md:p-10">
            <div class="max-w-3xl">
                <span class="inline-block py-1 px-3 rounded-full bg-indigo-500/30 text-indigo-200 font-semibold text-xs mb-4 border border-indigo-500/30 backdrop-blur-sm tracking-wider uppercase">University Reference Center</span>
                <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight mb-4 drop-shadow-md leading-tight">Electronics and Communication<br/>Department Library</h1>
                <p class="text-indigo-100/90 mb-0 text-sm md:text-base leading-relaxed max-w-2xl font-normal drop-shadow-sm">
                    Empowering innovation through knowledge. Explore our extensive collection of materials covering circuits, digital signals, telecommunications, robotics, and advanced electronic systems.
                </p>
            </div>
        </div>
    </div>

    <!-- Profile & Stats Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-center text-center">
            <div class="w-24 h-24 rounded-full bg-gray-100 mb-4 overflow-hidden border-4 border-white shadow-md">
                <?php if ($member['photo_path']): ?>
                    <img src="<?php echo htmlspecialchars($member['photo_path']); ?>" alt="Profile" class="w-full h-full object-cover">
                <?php else: ?>
                    <svg class="w-full h-full text-gray-300 p-2" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                <?php endif; ?>
            </div>
            <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($member['name']); ?></h2>
            <p class="text-indigo-600 text-sm font-medium mb-4"><?php echo htmlspecialchars($member['department']); ?> • <?php echo htmlspecialchars($member['admission_year']); ?></p>
            
            <div class="w-full grid grid-cols-2 gap-2 text-left text-sm mt-4 border-t border-gray-100 pt-4">
                <div>
                    <span class="block text-gray-400 text-xs uppercase">PRN Number</span>
                    <span class="font-mono font-medium text-gray-700"><?php echo htmlspecialchars($member['prn_number']); ?></span>
                </div>
                <div>
                    <span class="block text-gray-400 text-xs uppercase">Library ID</span>
                    <span class="font-mono font-medium text-gray-700"><?php echo htmlspecialchars($member['barcode_id']); ?></span>
                </div>
                <div class="col-span-2 mt-2">
                    <span class="block text-gray-400 text-xs uppercase">Contact</span>
                    <span class="block font-medium text-gray-700 truncate"><?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></span>
                    <span class="block font-medium text-gray-700"><?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="col-span-2 mt-2">
                     <span class="block text-gray-400 text-xs uppercase">Account Status</span>
                     <?php if ($member['status'] == 'active'): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                            Active
                        </span>
                     <?php else: ?>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                            <?php echo ucfirst($member['status']); ?>
                        </span>
                     <?php endif; ?>
                </div>
            </div>

            <div class="mt-6 w-full flex flex-col gap-3">
                
                <?php if ($member['id_card_path']): 
                    $front_path = $member['id_card_path'];
                    $back_path = str_replace('ID_FRONT_', 'ID_BACK_', $front_path);
                ?>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Front Preview -->
                        <div class="group relative">
                            <span class="text-[10px] uppercase text-gray-400 font-bold block mb-1">Front Side</span>
                            <div class="relative overflow-hidden rounded-lg border border-gray-200 shadow-sm cursor-pointer hover:shadow-md transition-all">
                                <img src="<?php echo htmlspecialchars($front_path); ?>" class="w-full object-cover">
                                <a href="<?php echo htmlspecialchars($front_path); ?>" download class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs font-medium">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download
                                </a>
                            </div>
                        </div>

                        <!-- Back Preview -->
                        <?php if (file_exists($back_path)): ?>
                        <div class="group relative">
                            <span class="text-[10px] uppercase text-gray-400 font-bold block mb-1">Back Side</span>
                            <div class="relative overflow-hidden rounded-lg border border-gray-200 shadow-sm cursor-pointer hover:shadow-md transition-all">
                                <img src="<?php echo htmlspecialchars($back_path); ?>" class="w-full object-cover">
                                <a href="<?php echo htmlspecialchars($back_path); ?>" download class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs font-medium">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 rounded-lg p-4 text-center border border-dashed border-gray-300">
                        <span class="text-xs text-gray-400">ID Card not generated yet.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Current Status -->
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Active Loans Card -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-blue-100 font-medium text-sm uppercase tracking-wider mb-1">Books currently with you</h3>
                    <div class="text-4xl font-bold mb-4"><?php echo count($active_loans); ?> <span class="text-lg font-normal text-blue-200">/ <?php echo $member['max_issue_limit']; ?></span></div>
                    <?php if (count($active_loans) > 0): ?>
                        <p class="text-sm text-blue-100">Please return on time to avoid fines.</p>
                    <?php else: ?>
                        <p class="text-sm text-blue-100">You have no active loans.</p>
                    <?php endif; ?>
                </div>
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-4 translate-y-4">
                     <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M19 2H5C3.89 2 3 2.9 3 4V20C3 21.1 3.89 22 5 22H19C20.1 22 21 21.1 21 20V4C21 2.9 20.1 2 19 2ZM19 20H5V4H19V20ZM12 5.5V17.5L18 11.5L12 5.5Z" /></svg>
                </div>
            </div>

            <!-- Fines Card -->
            <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl shadow-lg p-6 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-rose-100 font-medium text-sm uppercase tracking-wider mb-1">Total Outstanding Fines</h3>
                    <div class="text-4xl font-bold mb-4">₹<?php echo $total_fines; ?></div>
                    <?php if ($total_fines > 0): ?>
                        <p class="text-sm text-rose-100 bg-white/20 inline-block px-2 py-1 rounded">Action Required: Pay at desk.</p>
                    <?php else: ?>
                        <p class="text-sm text-rose-100">Great! No pending dues.</p>
                    <?php endif; ?>
                </div>
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-4 translate-y-4">
                     <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" /></svg>
                </div>
            </div>

            <!-- Search Widget (Simple Mockup) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:col-span-2 flex flex-col justify-center">
                 <h3 class="font-bold text-gray-800 mb-2">Find Books</h3>
                 <div class="flex gap-2">
                     <input type="text" placeholder="Search by title, author, or subject..." class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-500">
                     <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">Search</button>
                 </div>
            </div>

        </div>
    </div>

    <!-- Active Loans Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
             <h3 class="font-bold text-gray-800">My Active Books</h3>
             <span class="text-xs text-gray-500 bg-white border border-gray-200 px-2 py-1 rounded"><?php echo count($active_loans); ?> Items</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Book Details</th>
                        <th class="px-6 py-3">Issue Date</th>
                        <th class="px-6 py-3">Due Date</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (count($active_loans) > 0): ?>
                        <?php foreach($active_loans as $loan): 
                            $is_overdue = strtotime($loan['due_date']) < time();
                            $days_left = ceil((strtotime($loan['due_date']) - time()) / (60 * 60 * 24));
                        ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($loan['title']); ?></div>
                                <div class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($loan['author']); ?></div>
                                <div class="inline-block bg-white border border-gray-100 p-1 rounded">
                                    <img src="barcode_preview.php?code=<?php echo urlencode($loan['barcode_id']); ?>&text=1" alt="Barcode" class="h-8">
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo date('d M Y', strtotime($loan['issue_date'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm <?php echo $is_overdue ? 'text-red-600 font-bold' : 'text-gray-700'; ?>">
                                    <?php echo date('d M Y', strtotime($loan['due_date'])); ?>
                                </span>
                                <?php if (!$is_overdue): ?>
                                    <div class="text-xs text-green-600 mt-1"><?php echo $days_left; ?> days left</div>
                                <?php else: ?>
                                    <div class="text-xs text-red-500 mt-1">Overdue by <?php echo abs($days_left); ?> days</div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($is_overdue): ?>
                                    <span class="px-2 py-1 text-xs font-bold text-red-700 bg-red-100 rounded-md uppercase">Overdue</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-bold text-green-700 bg-green-100 rounded-md uppercase">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">No books currently borrowed.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- History -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
             <h3 class="font-bold text-gray-800">Recent Return History</h3>
        </div>
        <div class="overflow-x-auto">
             <table class="w-full text-left text-sm">
                 <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                     <tr>
                         <th class="px-6 py-3">Book Title</th>
                         <th class="px-6 py-3">Returned On</th>
                         <th class="px-6 py-3">Fine Paid</th>
                     </tr>
                 </thead>
                 <tbody class="divide-y divide-gray-100">
                     <?php if (count($history) > 0): ?>
                         <?php foreach($history as $h): ?>
                         <tr class="hover:bg-gray-50/50">
                             <td class="px-6 py-3 font-medium text-gray-700"><?php echo htmlspecialchars($h['title']); ?></td>
                             <td class="px-6 py-3 text-gray-500"><?php echo date('d M Y', strtotime($h['return_date'])); ?></td>
                             <td class="px-6 py-3 text-gray-500">₹<?php echo $h['fine_amount'] > 0 ? $h['fine_amount'] : '0'; ?></td>
                         </tr>
                         <?php endforeach; ?>
                     <?php else: ?>
                         <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">No history available.</td></tr>
                     <?php endif; ?>
                 </tbody>
             </table>
        </div>
    </div>

</div>

</body>
</html>
