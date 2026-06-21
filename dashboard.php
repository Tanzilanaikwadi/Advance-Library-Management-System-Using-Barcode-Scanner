<?php
require_once 'config/db.php';
include 'header.php';

// Fetch Statistics
// 1. Total Books (Copies)
$stmt = $pdo->query("SELECT SUM(total_copies) as total FROM books");
$total_books = $stmt->fetch()['total'] ?? 0;

// 2. Issued Books
$stmt = $pdo->query("SELECT COUNT(*) as total FROM issues WHERE status = 'issued'");
$issued_books = $stmt->fetch()['total'] ?? 0;

// 3. Available Stock
$stmt = $pdo->query("SELECT SUM(available_copies) as total FROM books");
$available_stock = $stmt->fetch()['total'] ?? 0;

// 4. Overdue Records
$stmt = $pdo->query("SELECT COUNT(*) as total FROM issues WHERE status = 'issued' AND due_date < NOW()");
$overdue_records = $stmt->fetch()['total'] ?? 0;

// 5. Total Members
$stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
$total_members = $stmt->fetch()['total'] ?? 0;
?>

<!-- Enhanced Header Section with Background -->
<div class="mb-8 rounded-lg overflow-hidden shadow-md relative bg-gray-900">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 z-0">
        <img src="assets/library_bg.png" alt="Library Background" class="w-full h-full object-cover opacity-40">
        <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/80 to-transparent"></div>
    </div>
    
    <!-- Content -->
    <div class="relative z-10 p-8 md:p-10">
        <div class="max-w-3xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white tracking-tight mb-2">Electronics and Communication Department Library</h1>
            <p class="text-gray-300 mb-6 text-sm md:text-base leading-relaxed">
                Empowering innovation through knowledge. Access our comprehensive collection of resources spanning circuits, signals, telecommunications, and advanced electronic systems to fuel your academic and research pursuits.
            </p>
            
            <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-700/50">
                <div class="w-10 h-10 rounded-full bg-official-600 flex items-center justify-center text-white font-bold shadow-lg">
                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <h2 class="text-white font-semibold flex items-center gap-2">
                        Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                    </h2>
                    <p class="text-gray-400 text-xs">System Overview • <?php echo date('F j, Y'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    
    <!-- Stat Card 1: Total Books -->
    <a href="transactions.php" class="block bg-white rounded-sm p-6 shadow-sm border-t-4 border-official-700 relative overflow-hidden transition-all hover:shadow-md hover:-translate-y-1">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Books</h3>
            <div class="p-2 bg-gray-100 rounded-full text-official-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800"><?php echo number_format($total_books); ?></div>
        <p class="text-green-600 text-xs font-medium mt-2 flex items-center gap-1">
             In Inventory
        </p>
    </a>

    <!-- Stat Card 2: Issued Books -->
    <a href="transactions.php?status=issued" class="block bg-white rounded-sm p-6 shadow-sm border-t-4 border-amber-500 relative overflow-hidden transition-all hover:shadow-md hover:-translate-y-1">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Issued Books</h3>
             <div class="p-2 bg-amber-50 rounded-full text-amber-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800"><?php echo number_format($issued_books); ?></div>
        <p class="text-amber-600 text-xs font-medium mt-2 flex items-center gap-1">
            Currently with members
        </p>
    </a>

    <!-- Stat Card 3: Available -->
    <div class="block bg-white rounded-sm p-6 shadow-sm border-t-4 border-emerald-600 relative overflow-hidden transition-all hover:shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Available Stock</h3>
            <div class="p-2 bg-emerald-50 rounded-full text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800"><?php echo number_format($available_stock); ?></div>
        <p class="text-emerald-600 text-xs font-medium mt-2 flex items-center gap-1">
            Ready to issue
        </p>
    </div>

    <!-- Stat Card 4: Overdue -->
    <a href="transactions.php?status=overdue" class="block bg-white rounded-sm p-6 shadow-sm border-t-4 border-red-600 relative overflow-hidden transition-all hover:shadow-md hover:-translate-y-1">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Overdue</h3>
            <div class="p-2 bg-red-50 rounded-full text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800"><?php echo number_format($overdue_records); ?></div>
        <p class="text-red-500 text-xs font-medium mt-2 flex items-center gap-1">
            Action Required
        </p>
    </a>

</div>

<!-- Lower Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Quick Actions -->
    <div class="lg:col-span-2 bg-white rounded-sm shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Quick Actions</h3>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-4">
                <a href="issue_book.php" class="flex items-center gap-2 bg-official-800 hover:bg-official-900 text-white px-5 py-2.5 rounded text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Issue New Book
                </a>
                <a href="return_book.php" class="flex items-center gap-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-5 py-2.5 rounded text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Return Book
                </a>
                <a href="add_member.php" class="flex items-center gap-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-5 py-2.5 rounded text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Add Member
                </a>
                <a href="view_members.php" class="flex items-center gap-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-5 py-2.5 rounded text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    View All Members
                </a>
                <a href="view_books.php" class="flex items-center gap-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-5 py-2.5 rounded text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    View All Books
                </a>
                <a href="transactions.php?status=issued" class="flex items-center gap-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-5 py-2.5 rounded text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Issued Books
                </a>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="bg-official-900 rounded-sm shadow-lg text-white border border-official-800">
        <div class="px-6 py-4 border-b border-official-800 bg-official-800/50">
             <h3 class="text-sm font-bold text-official-100 uppercase tracking-wider">System Status</h3>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex justify-between items-center border-b border-official-700 pb-3">
                <span class="text-official-200 text-sm">Total Members</span>
                <span class="text-lg font-mono font-bold"><?php echo $total_members; ?></span>
            </div>
            <div class="flex justify-between items-center border-b border-official-700 pb-3">
                <span class="text-official-200 text-sm">Total Transactions</span>
                <span class="text-lg font-mono font-bold">
                    <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM issues"); 
                        echo $stmt->fetchColumn(); 
                    ?>
                </span>
            </div>
            <div class="flex justify-between items-center pt-2">
                <span class="text-official-200 text-sm">Server Date</span>
                <span class="font-medium text-white"><?php echo date('d M Y'); ?></span>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
