<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Security Check: Ensure user still exists in DB
if (isset($pdo) && isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $exists = $stmt->fetch();
    } elseif ($role === 'student') {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $exists = $stmt->fetch();
    } else {
        $exists = false;
    }

    if (!$exists) {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    
    <!-- Google Fonts: Roboto for a more "Official" look -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Roboto', 'sans-serif'],
                    },
                    colors: {
                        official: {
                            50: '#f0f4f8',
                            100: '#d9e2ec',
                            200: '#bcccdc',
                            300: '#9fb3c8',
                            400: '#829ab1',
                            500: '#627d98',
                            600: '#486581',
                            700: '#334e68', // Official Slate Blue
                            800: '#243b53', // Deep Navy
                            900: '#102a43', // Darkest Navy
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f0f4f8; }
        ::-webkit-scrollbar-thumb { background: #829ab1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #627d98; }
    </style>
</head>
<body class="h-full font-sans text-gray-800 antialiased">

<!-- NAVIGATION BAR -->
<nav class="bg-official-900 border-b border-official-800 sticky top-0 z-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            <!-- Logo & Brand -->
            <div class="flex items-center">
                <a href="dashboard.php" class="flex-shrink-0 flex items-center gap-3 group">
                    <!-- Icon Box -->
                    <div class="p-1.5 bg-white rounded flex items-center justify-center">
                         <svg class="w-6 h-6 text-official-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <!-- Text -->
                    <div class="flex flex-col">
                        <span class="font-bold text-lg leading-tight text-white tracking-wide uppercase">Library</span>
                        <span class="text-[10px] leading-tight text-official-300 uppercase tracking-widest">Management System</span>
                    </div>
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <div class="flex items-center sm:hidden">
                 <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="bg-official-800 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-official-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                     <span class="sr-only">Open main menu</span>
                     <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                     </svg>
                 </button>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden sm:ml-6 sm:flex sm:items-center sm:space-x-1">
                <a href="dashboard.php" class="px-3 py-2 text-sm font-medium text-official-100 hover:text-white hover:bg-official-800 rounded transition-colors uppercase tracking-wider">Dashboard</a>
                
                <!-- Books Dropdown -->
                <div class="relative group">
                    <button class="px-3 py-2 text-sm font-medium text-official-100 hover:text-white hover:bg-official-800 rounded transition-colors uppercase tracking-wider inline-flex items-center">
                        Books
                        <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="absolute left-0 mt-0 w-48 bg-white rounded-md shadow-lg py-1 hidden group-hover:block ring-1 ring-black ring-opacity-5 z-50">
                        <a href="view_books.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">All Books</a>
                        <a href="add_book.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Add Book</a>
                        <a href="book_barcodes.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Barcodes</a>
                        <a href="book_scan.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Scan Book</a>
                        <a href="barcode_scanner.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Barcode Scanner</a>
                        <a href="transactions.php?status=issued" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Issued Books</a>
                    </div>
                </div>

                <!-- Members Dropdown -->
                <div class="relative group">
                    <button class="px-3 py-2 text-sm font-medium text-official-100 hover:text-white hover:bg-official-800 rounded transition-colors uppercase tracking-wider inline-flex items-center">
                        Members
                        <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="absolute left-0 mt-0 w-48 bg-white rounded-md shadow-lg py-1 hidden group-hover:block ring-1 ring-black ring-opacity-5 z-50">
                        <a href="view_members.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">All Members</a>
                        <a href="add_member.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Add Member</a>
                        <a href="member_scan.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Scan Profile</a>
                    </div>
                </div>

                <!-- Circulation Dropdown -->
                <div class="relative group">
                    <button class="px-3 py-2 text-sm font-medium text-official-100 hover:text-white hover:bg-official-800 rounded transition-colors uppercase tracking-wider inline-flex items-center">
                        Circulation
                        <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="absolute left-0 mt-0 w-48 bg-white rounded-md shadow-lg py-1 hidden group-hover:block ring-1 ring-black ring-opacity-5 z-50">
                        <a href="issue_book.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Issue Book</a>
                        <a href="return_book.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Return Book</a>
                    </div>
                </div>

                <!-- History Dropdown -->
                 <div class="relative group">
                    <button class="px-3 py-2 text-sm font-medium text-official-100 hover:text-white hover:bg-official-800 rounded transition-colors uppercase tracking-wider inline-flex items-center">
                        History
                        <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="absolute left-0 mt-0 w-48 bg-white rounded-md shadow-lg py-1 hidden group-hover:block ring-1 ring-black ring-opacity-5 z-50">
                        <a href="transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">All Transactions</a>
                    </div>
                </div>
                <div class="h-6 w-px bg-official-700 mx-2"></div>
                <a href="logout.php" class="px-4 py-2 text-sm font-bold text-white bg-red-700 hover:bg-red-800 rounded transition-colors uppercase tracking-wider shadow-sm">Logout</a>
            </div>

        </div>
    </div>

    <!-- Mobile Menu, show/hide based on menu state. -->
    <div class="hidden sm:hidden bg-official-800 border-t border-official-700" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-official-900">Dashboard</a>
            
            <div class="px-3 pt-2 text-xs font-bold text-official-400 uppercase tracking-wider">Books</div>
            <a href="view_books.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">All Books</a>
            <a href="add_book.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Add Book</a>
            <a href="book_barcodes.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Barcodes</a>
            <a href="book_scan.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Scan Book</a>
            <a href="barcode_scanner.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Barcode Scanner</a>
            <a href="transactions.php?status=issued" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Issued Books</a>

            <div class="px-3 pt-2 text-xs font-bold text-official-400 uppercase tracking-wider">Members</div>
            <a href="view_members.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">All Members</a>
            <a href="add_member.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Add Member</a>
            <a href="member_scan.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Scan Profile</a>

            <div class="px-3 pt-2 text-xs font-bold text-official-400 uppercase tracking-wider">Circulation</div>
            <a href="issue_book.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Issue Book</a>
            <a href="return_book.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Return Book</a>

            <div class="px-3 pt-2 text-xs font-bold text-official-400 uppercase tracking-wider">History</div>
            <a href="transactions.php" class="block px-3 py-2 pl-6 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-official-700">Transactions</a>

            <div class="border-t border-official-700 mt-2 pt-2">
                <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-red-400 hover:text-white hover:bg-official-700">Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- Main Layout Container -->
<main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
