<?php
require_once 'config/db.php';
include 'header.php';

$message = '';
$error = '';
$issued_by_user = 'Admin'; // Default, ideally from session

// Handle Issue Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_barcode = trim($_POST['member_barcode']);
    $book_barcode = trim($_POST['book_barcode']);
    $due_days = (int)($_POST['due_days'] ?? 14);
    $issued_by_name = trim($_POST['issued_by_name']); 
    
    // Override with manual input if provided and not empty, otherwise session default
    if (!empty($issued_by_name)) {
        $issued_by = $issued_by_name;
    } else {
        $issued_by = $issued_by_user;
    }

    if (empty($member_barcode) || empty($book_barcode)) {
        $error = "Both Member and Book barcodes are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Get Member
            $stmt = $pdo->prepare("SELECT * FROM members WHERE barcode_id = ? OR prn_number = ? FOR UPDATE");
            $stmt->execute([$member_barcode, $member_barcode]);
            $member = $stmt->fetch();

            if (!$member) {
                throw new Exception("Member not found.");
            }
            if ($member['status'] !== 'active') {
                throw new Exception("Member is blocked.");
            }

            // 2. Count Active Issues for Limit Check
            $stmt = $pdo->prepare("SELECT count(*) FROM issues WHERE member_id = ? AND status = 'issued'");
            $stmt->execute([$member['id']]);
            $active_count = $stmt->fetchColumn();

            if ($active_count >= $member['max_issue_limit']) {
                throw new Exception("Member has reached maximum issue limit ({$member['max_issue_limit']}).");
            }

            // 3. Get Book
            $stmt = $pdo->prepare("SELECT * FROM books WHERE barcode_id = ? FOR UPDATE");
            $stmt->execute([$book_barcode]);
            $book = $stmt->fetch();

            if (!$book) {
                throw new Exception("Book not found.");
            }
            if ($book['available_copies'] < 1) {
                throw new Exception("Book is out of stock. Please enroll available copies in stock first; the book cannot be issued until stock is available.");
            }

            // 4. Check if already issued to this member
            $stmt = $pdo->prepare("SELECT id FROM issues WHERE member_id = ? AND book_id = ? AND status = 'issued'");
            $stmt->execute([$member['id'], $book['id']]);
            if ($stmt->fetch()) {
                throw new Exception("Member already has this book issued.");
            }

            // 5. Create Issue
            $due_date = date('Y-m-d H:i:s', strtotime("+$due_days days"));
            $stmt = $pdo->prepare("INSERT INTO issues (member_id, book_id, due_date, issued_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$member['id'], $book['id'], $due_date, $issued_by]);

            // 6. Update Stock
            $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
            $stmt->execute([$book['id']]);

            $pdo->commit();
            $message = "Book successfully issued to {$member['name']} confirmed by {$issued_by}!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="mb-10">
    <h1 class="text-3xl font-bold text-slate-900">Issue Book</h1>
    <p class="text-slate-500 mt-2">Scan member and book barcodes to process a new issue transaction.</p>
</div>

<?php if ($message): ?>
    <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl mb-6 flex items-center gap-3">
        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <div class="flex-1">
            <span class="font-medium"><?php echo $message; ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-xl mb-6 flex items-center gap-3">
        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="font-medium"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Left Column: Scanning Form -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <form method="POST" id="issueForm">
            <h3 class="text-lg font-bold text-slate-800 mb-6 pb-4 border-b border-slate-100 flex items-center gap-2">
                <span class="bg-slate-100 p-2 rounded-lg text-slate-600">📝</span> Transaction Details
            </h3>

            <!-- Member Scan Section -->
            <div class="mb-6 bg-slate-50 p-5 rounded-xl border border-slate-200">
                <label class="block text-sm font-bold text-slate-700 mb-2">1. Member Identification</label>
                
                <!-- Scan Input -->
                <div class="flex gap-3 mb-4">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-slate-400">👤</span>
                        </div>
                        <input type="text" id="member_barcode" name="member_barcode" placeholder="Scan Barcode or Enter PRN..." autofocus
                               class="w-full pl-10 pr-4 py-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all outline-none font-mono text-slate-800 font-bold shadow-sm">
                    </div>
                </div>

                <!-- Member Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Student Name</label>
                        <input type="text" id="form_member_name" readonly placeholder="Name will appear here..."
                               class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded text-slate-700 text-sm font-medium focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">PRN Number</label>
                        <input type="text" id="form_member_prn" readonly placeholder="PRN..."
                               class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded text-slate-700 text-sm font-mono focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Department</label>
                        <input type="text" id="form_member_dept" readonly placeholder="Department..."
                               class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded text-slate-700 text-sm focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Admission Year</label>
                        <input type="text" id="form_member_year" readonly placeholder="Year..."
                               class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded text-slate-700 text-sm focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Book Scan -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">2. Book Barcode</label>
                 <div class="flex gap-3">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-slate-400">📖</span>
                        </div>
                        <input type="text" id="book_barcode" name="book_barcode" placeholder="Scan Book Barcode..."
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all outline-none font-mono text-slate-800">
                    </div>
                </div>
            </div>

            <!-- Auto-Filled Book Details -->
            <div id="bookAutoFill" class="mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200 hidden">
                 <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Book Details (Auto-Filled)
                </div>
                <div>
                    <input type="text" id="form_book_title" readonly 
                           class="w-full px-3 py-2 bg-white border border-slate-200 rounded text-slate-800 font-medium mb-1">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Duration -->
                 <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Issue Duration (Days)</label>
                    <input type="number" name="due_days" value="14" 
                           class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none font-bold text-slate-700">
                </div>
                
                <!-- Issued By -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Issued By (Faculty/Staff Name)</label>
                    <input type="text" name="issued_by_name" value="<?php echo htmlspecialchars($issued_by_user); ?>" 
                           class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-slate-700">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                    Confirm Issue Book
                </button>
            </div>
        </form>
    </div>

    <!-- Right Column: Info Display Cards -->
    <div class="flex flex-col gap-6">
        
        <!-- Member Info Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 hidden overflow-hidden transition-all duration-300" id="memberInfo">
             <div class="bg-blue-600 p-4 flex justify-between items-center text-white">
                 <h3 class="font-bold flex items-center gap-2">
                    <span>👤</span> Member Profile
                 </h3>
                 <span id="memberStatusBadge" class="px-2 py-0.5 bg-white/20 rounded text-xs uppercase font-bold">Active</span>
             </div>
             
             <div class="p-6">
                 <div class="flex items-start gap-4">
                     <img id="memberPhoto" src="" class="w-24 h-24 object-cover rounded-lg border border-slate-200 hidden bg-slate-100">
                     <div class="flex-1 overflow-hidden">
                         <div id="memberName" class="font-bold text-lg text-slate-900 leading-tight truncate"></div>
                         <div id="memberEmail" class="text-sm text-slate-500 mb-2 truncate"></div>
                     </div>
                 </div>

                 <div class="grid grid-cols-2 gap-y-3 gap-x-4 mt-4 text-sm text-slate-600 border-t border-slate-100 pt-3">
                     <div>
                         <span class="block text-xs text-slate-400 uppercase tracking-wider">Department</span>
                         <span id="memberDept" class="font-medium text-slate-800 truncate block"></span>
                     </div>
                     <div>
                         <span class="block text-xs text-slate-400 uppercase tracking-wider">Year</span>
                         <span id="memberYear" class="font-medium text-slate-800 block"></span>
                     </div>
                     <div>
                         <span class="block text-xs text-slate-400 uppercase tracking-wider">PRN</span>
                         <span id="memberPRN" class="font-mono font-medium text-slate-800 block"></span>
                     </div>
                     <div>
                         <span class="block text-xs text-slate-400 uppercase tracking-wider">Phone</span>
                         <span id="memberPhone" class="font-medium text-slate-800 block"></span>
                     </div>
                 </div>

                <div class="mt-4 pt-3 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Current Active Loans</h4>
                    <ul id="activeLoans" class="text-sm space-y-2 text-slate-700 max-h-32 overflow-y-auto"></ul>
                </div>

             </div>
        </div>

        <!-- Book Info Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 hidden overflow-hidden transition-all duration-300" id="bookInfo">
             <div class="bg-emerald-600 p-4 text-white">
                 <h3 class="font-bold flex items-center gap-2">
                    <span>📘</span> Book Details
                 </h3>
             </div>
             <div class="p-6">
                 <div id="bookTitle" class="font-bold text-lg text-slate-900 leading-tight mb-1"></div>
                 <div id="bookAuthor" class="text-sm text-slate-500 mb-4"></div>
                 
                 <div class="flex gap-4">
                     <div class="flex-1 bg-slate-50 p-2 rounded text-center border border-slate-200">
                         <span class="block text-xs text-slate-400 uppercase">Shelf</span>
                         <span id="bookShelf" class="font-bold text-slate-800"></span>
                     </div>
                     <div class="flex-1 bg-emerald-50 p-2 rounded text-center border border-emerald-200">
                         <span class="block text-xs text-emerald-600 uppercase">Stock</span>
                         <span id="bookStock" class="font-bold text-emerald-700 text-lg"></span>
                     </div>
                 </div>
             </div>
        </div>

    </div>

</div>

<!-- Recent Transactions Table -->
<div class="mt-10 bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <span class="bg-slate-100 p-2 rounded-lg text-slate-600">🕒</span> Today's Issued Books
        </h3>
        <button type="button" onclick="loadDailyLog()" class="text-sm text-blue-600 hover:text-blue-800 font-medium px-3 py-1 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
            Refresh List
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-xs text-slate-500 uppercase border-b border-slate-100 tracking-wider">
                    <th class="py-3 px-2 font-semibold w-24">Time</th>
                    <th class="py-3 px-2 font-semibold">Member Details</th>
                    <th class="py-3 px-2 font-semibold">Book Title</th>
                    <th class="py-3 px-2 font-semibold">Issued By</th>
                    <th class="py-3 px-2 font-semibold">Due Date</th>
                </tr>
            </thead>
            <tbody id="dailyLogBody" class="text-sm text-slate-700">
                <tr><td colspan="5" class="py-8 text-center text-slate-400 italic">No transactions today.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const memberInput = document.getElementById('member_barcode');
    const bookInput = document.getElementById('book_barcode');

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    const lookupMember = debounce(async () => {
        const barcode = memberInput.value.trim();
        if(!barcode) return;

        try {
            const r = await fetch(`ajax_handler.php?action=get_member&barcode=${encodeURIComponent(barcode)}`);
            const data = await r.json();
            
            const infoDiv = document.getElementById('memberInfo');
            
            if(data.success) {
                const m = data.data.member;
                
                infoDiv.classList.remove('hidden');
                document.getElementById('memberName').innerText = m.name;
                document.getElementById('memberEmail').innerText = m.email || '';
                document.getElementById('memberDept').innerText = m.department || '-';
                document.getElementById('memberYear').innerText = m.admission_year || '-';
                document.getElementById('memberPRN').innerText = m.prn_number || '-';
                document.getElementById('memberPhone').innerText = m.phone || '-';

                // Auto-fill Form Inputs
                // Container is now always visible
                document.getElementById('form_member_name').value = m.name;
                document.getElementById('form_member_prn').value = m.prn_number || '';
                document.getElementById('form_member_dept').value = m.department || '';
                document.getElementById('form_member_year').value = m.admission_year || '';
                
                const photo = document.getElementById('memberPhoto');
                if (m.photo_path) {
                    photo.src = m.photo_path;
                    photo.classList.remove('hidden');
                } else {
                    photo.classList.add('hidden');
                }
                
                // Status Badge
                const badge = document.getElementById('memberStatusBadge');
                badge.innerText = m.status;
                if(m.status === 'active') {
                    badge.className = 'px-2 py-0.5 bg-green-500/20 text-green-100 rounded text-xs uppercase font-bold';
                    infoDiv.querySelector('.bg-blue-600').className = 'bg-blue-600 p-4 flex justify-between items-center text-white';
                } else {
                    badge.className = 'px-2 py-0.5 bg-red-500/20 text-red-100 rounded text-xs uppercase font-bold';
                    infoDiv.querySelector('.bg-blue-600').className = 'bg-red-600 p-4 flex justify-between items-center text-white';
                    alert("WARNING: Member is BLOCKED!");
                }

                if(!data.data.can_issue) {
                    alert("WARNING: Member has reached issue limit!");
                }

                // Active Loans
                const loansList = document.getElementById('activeLoans');
                loansList.innerHTML = '';
                if(data.data.active_issues && data.data.active_issues.length > 0) {
                     data.data.active_issues.forEach(issue => {
                         loansList.innerHTML += `<li class="flex justify-between items-center bg-slate-50 p-2 rounded border border-slate-100">
                             <div class="truncate pr-2 font-medium flex-1">${issue.title}</div>
                             <div class="text-xs font-bold text-red-600 whitespace-nowrap bg-red-50 px-2 py-1 rounded">Due: ${issue.due_date ? issue.due_date.split(' ')[0] : '-'}</div>
                         </li>`;
                     });
                } else {
                     loansList.innerHTML = '<li class="text-slate-400 italic text-center py-2 text-xs">No active loans</li>';
                }

            } else {
                // Not found logic if needed
                console.log(data.message);
            }
        } catch(e) { console.error(e); }
    }, 500);

    const lookupBook = debounce(async () => {
        const barcode = bookInput.value.trim();
        if(!barcode) return;

        try {
            const r = await fetch(`ajax_handler.php?action=get_book&barcode=${encodeURIComponent(barcode)}`);
            const data = await r.json();
            
            const infoDiv = document.getElementById('bookInfo');
            
            if(data.success) {
                const b = data.data.book;
                infoDiv.classList.remove('hidden');
                
                document.getElementById('bookAutoFill').classList.remove('hidden');
                document.getElementById('form_book_title').value = b.title;

                document.getElementById('bookTitle').innerText = b.title;
                document.getElementById('bookAuthor').innerText = b.author;
                document.getElementById('bookShelf').innerText = b.shelf_location || '-';
                document.getElementById('bookStock').innerText = b.available_copies + ' / ' + b.total_copies;
                
            } else {
                infoDiv.classList.add('hidden');
            }
        } catch(e) { console.error(e); }
    }, 500);

    async function loadDailyLog() {
        const tbody = document.getElementById('dailyLogBody');
        try {
            const r = await fetch('ajax_handler.php?action=get_daily_log');
            const data = await r.json();
            
            if(data.success && data.data.logs.length > 0) {
                tbody.innerHTML = '';
                data.data.logs.forEach(log => {
                    // split time part from issue_date "YYYY-MM-DD HH:MM:SS"
                    const timePart = log.issue_date.split(' ')[1].substring(0, 5); // HH:MM
                    
                    tbody.innerHTML += `
                        <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-2 text-slate-500 font-mono text-xs align-top pt-4">${timePart}</td>
                            <td class="py-3 px-2 align-top">
                                <div class="font-bold text-slate-800 text-sm">${log.member_name}</div>
                                <div class="flex flex-wrap gap-x-2 gap-y-1 mt-1">
                                    <span class="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">PRN: ${log.prn_number || '-'}</span>
                                    <span class="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">Dept: ${log.department || '-'}</span>
                                    <span class="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">Yr: ${log.admission_year || '-'}</span>
                                </div>
                            </td>
                            <td class="py-3 px-2 font-medium text-slate-700 align-top pt-4 text-sm">${log.book_title}</td>
                            <td class="py-3 px-2 text-slate-600 text-sm align-top pt-4">${log.issued_by || 'System'}</td>
                            <td class="py-3 px-2 text-slate-600 text-xs align-top pt-4 font-mono">${log.due_date ? log.due_date.split(' ')[0] : '-'}</td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-slate-400 italic">No books issued today yet.</td></tr>';
            }
        } catch(e) {
            console.error(e);
        }
    }

    memberInput.addEventListener('input', lookupMember);
    bookInput.addEventListener('input', lookupBook);

    // Handle barcode scanner Enter key press
    memberInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent premature form submission
            bookInput.focus();  // Move to the next field automatically
        }
    });

    bookInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            // If both are filled, submit the form!
            if (memberInput.value && bookInput.value) {
                document.getElementById('issueForm').submit();
            } else {
                document.querySelector('button[type="submit"]').focus();
            }
        }
    });
    
    // Load daily log on start
    document.addEventListener('DOMContentLoaded', loadDailyLog);
</script>

<?php include 'footer.php'; ?>
