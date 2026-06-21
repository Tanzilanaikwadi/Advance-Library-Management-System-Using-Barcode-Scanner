<?php
require_once 'config/db.php';
include 'header.php';
?>

<div class="mb-8 text-center max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Book Inventory Scanner</h1>
    <p class="text-gray-500 mt-2">Scan a book barcode to check stock levels, borrower history, and more.</p>
</div>

<!-- Search Bar -->
<div class="max-w-xl mx-auto mb-12">
    <div class="relative group">
        <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white rounded-lg p-2 flex items-center shadow-xl ring-1 ring-gray-900/5">
            <div class="p-3 bg-gray-50 rounded-md text-gray-500">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 14.5v.01M12 6.6L3 12m0 0l9 5.3 9-5.3M3 12v7.55c0 .3.25.55.55.55h.9c.3 0 .55-.25.55-.55V12M19 12v7.55c0 .3.25.55.55.55h.9c.3 0 .55-.25.55-.55V12" />
                </svg>
            </div>
            <input type="text" id="scanInput" placeholder="Scan Book Barcode..." autofocus
                   class="w-full p-4 bg-transparent outline-none text-gray-700 placeholder-gray-400 font-medium">
            <button onclick="scanBook()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-md font-bold transition-all shadow-md">
                Check
            </button>
        </div>
    </div>
</div>

<!-- Results Container -->
<div id="bookContainer" class="hidden max-w-5xl mx-auto">
    
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <!-- Top Section: Book Info -->
        <div class="md:flex">
            <!-- Cover & Status -->
            <div class="md:w-1/3 bg-gray-50 p-8 flex flex-col items-center justify-center border-r border-gray-100">
                <!-- Cover Image Placeholder -->
                <div class="w-48 h-64 bg-white shadow-lg rounded-lg flex items-center justify-center mb-6 border border-gray-200 overflow-hidden relative">
                    <svg class="w-16 h-16 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M19 2H5C3.89 2 3 2.9 3 4V20C3 21.1 3.89 22 5 22H19C20.1 22 21 21.1 21 20V4C21 2.9 20.1 2 19 2ZM19 20H5V4H19V20ZM12 5.5V17.5L18 11.5L12 5.5Z" /></svg>
                    <!-- "Stock Status" Badge -->
                    <div id="stockBadge" class="absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded shadow-sm text-white bg-green-500">
                        IN STOCK
                    </div>
                </div>
                
                <!-- Availability Ring -->
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-800" id="b_available_display">0 / 0</div>
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Free Copies</div>
                </div>
            </div>

            <!-- Details -->
            <div class="md:w-2/3 p-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 id="b_title" class="text-3xl font-bold text-gray-900 leading-tight mb-2">Book Title Here</h2>
                        <div class="text-lg text-indigo-600 font-medium">By <span id="b_author">Author Name</span></div>
                    </div>
                    <div class="bg-gray-100 px-3 py-1 rounded font-mono text-sm font-bold text-gray-600" id="b_barcode">
                        BARCODE-123
                    </div>
                </div>

                <!-- metadata grid -->
                <div class="grid grid-cols-2 gap-y-4 gap-x-8 mb-8 text-sm">
                    <div>
                        <span class="block text-gray-400 text-xs uppercase font-bold mb-1">Category</span>
                        <span id="b_category" class="font-medium text-gray-700">Technology</span>
                    </div>
                    <div>
                        <span class="block text-gray-400 text-xs uppercase font-bold mb-1">Shelf Location</span>
                        <span id="b_shelf" class="font-medium text-gray-700">A-12</span>
                    </div>
                    <div>
                         <span class="block text-gray-400 text-xs uppercase font-bold mb-1">Total Lifetime Issues</span>
                         <span id="b_total_borrowed" class="font-medium text-gray-700">124</span>
                    </div>
                     <div>
                         <span class="block text-gray-400 text-xs uppercase font-bold mb-1">Last Returned</span>
                         <span id="b_last_returned" class="font-medium text-gray-700">Yesterday</span>
                    </div>
                </div>

                <hr class="border-gray-100 mb-6">

                <!-- Current Borrowers -->
                <div>
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Currently Issued To
                    </h3>
                    
                    <div id="borrowers_list" class="space-y-3">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Not Found State -->
<div id="notFoundMsg" class="hidden max-w-lg mx-auto mt-12 text-center">
    <div class="inline-flex items-center justify-center p-4 bg-red-50 rounded-full mb-4">
        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    </div>
    <h3 class="text-xl font-bold text-gray-900">Book Not Found</h3>
    <p class="text-gray-500 mt-2">We couldn't find a book with that barcode. Please double check and try again.</p>
</div>

<script>
    const input = document.getElementById('scanInput');
    
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') scanBook();
    });

    function scanBook() {
        const barcode = input.value.trim();
        if (!barcode) return;

        // Reset UI
        document.getElementById('bookContainer').classList.add('hidden');
        document.getElementById('notFoundMsg').classList.add('hidden');

        fetch(`ajax_handler.php?action=get_book_details&barcode=${encodeURIComponent(barcode)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showBook(data.data);
                } else {
                    document.getElementById('notFoundMsg').classList.remove('hidden');
                }
            })
            .catch(e => alert("System Error: " + e));
    }

    function showBook(data) {
        document.getElementById('bookContainer').classList.remove('hidden');
        
        const b = data.book;
        const stats = data.stats;
        
        // Basic Info
        document.getElementById('b_title').innerText = b.title;
        document.getElementById('b_author').innerText = b.author;
        document.getElementById('b_barcode').innerText = b.barcode_id;
        document.getElementById('b_category').innerText = b.category || '-';
        document.getElementById('b_shelf').innerText = b.shelf_location || "Not Assigned";
        
        // Counts
        const total = parseInt(b.total_copies);
        const avail = parseInt(b.available_copies);
        document.getElementById('b_available_display').innerText = `${avail} / ${total}`;

        // Badge Logic
        const badge = document.getElementById('stockBadge');
        if (avail > 0) {
            badge.innerText = "IN STOCK";
            badge.className = "absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded shadow-sm text-white bg-green-500";
        } else {
            badge.innerText = "OUT OF STOCK";
            badge.className = "absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded shadow-sm text-white bg-red-500";
        }

        // Stats
        document.getElementById('b_total_borrowed').innerText = stats.total_borrowed + " times";
        document.getElementById('b_last_returned').innerText = stats.last_returned;

        // Borrowers List
        const listDiv = document.getElementById('borrowers_list');
        listDiv.innerHTML = ''; // clear

        if (data.borrowers && data.borrowers.length > 0) {
            data.borrowers.forEach(bor => {
                const isOverdue = new Date(bor.due_date) < new Date();
                const dueClass = isOverdue ? "text-red-600 font-bold bg-red-50" : "text-gray-500 bg-gray-50";
                
                const row = `
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-official-100 flex items-center justify-center text-official-600 font-bold text-xs">
                                ${bor.name.charAt(0)}
                            </div>
                            <div>
                                <div class="font-bold text-gray-800 text-sm">${bor.name}</div>
                                <div class="text-xs text-gray-400 font-mono">${bor.member_barcode}</div>
                            </div>
                        </div>
                        <div class="px-2 py-1 rounded text-xs ${dueClass}">
                            Due: ${bor.due_date.split(' ')[0]}
                        </div>
                    </div>
                `;
                listDiv.innerHTML += row;
            });
        } else {
            listDiv.innerHTML = `
                <div class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                    <p class="text-sm text-gray-400 italic">No copies are currently issued.</p>
                </div>
            `;
        }
    }
</script>

<?php include 'footer.php'; ?>
