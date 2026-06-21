<?php
require_once 'config/db.php';
include 'header.php';

// Check if member/admin is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8 border-b border-gray-200 pb-5">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Barcode Scanner</h1>
        <p class="text-gray-500 mt-2 text-sm">Scan student barcode first, then scan books to issue them</p>
    </div>

    <!-- Current Member Status -->
    <div id="memberStatus" class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-lg shadow-md p-4 sm:p-6 mb-8 hidden">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <p class="text-xs sm:text-sm font-semibold text-blue-600 uppercase tracking-wide mb-1">Currently Processing</p>
                <h3 class="text-xl sm:text-2xl font-bold text-blue-900" id="currentMemberName">--</h3>
                <p class="text-xs sm:text-sm text-blue-700 mt-1">
                    Barcode: <span class="font-mono font-bold" id="currentMemberBarcode">--</span> |
                    PRN: <span class="font-mono font-bold" id="currentMemberPRN">--</span>
                </p>
            </div>
            <div class="flex w-full sm:w-auto mt-2 sm:mt-0">
                <button onclick="clearMember()" class="w-full sm:w-auto px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                    Clear & Restart
                </button>
            </div>
        </div>
    </div>

    <!-- Scanner Input Section -->
    <div class="bg-white rounded-lg shadow-md p-8 mb-8 border-2 border-green-400">
        <div class="max-w-md mx-auto">
            <div class="flex items-center justify-center mb-3">
                <div class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                    <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                    SCANNER READY
                </div>
            </div>
            <label class="block text-sm font-medium text-gray-700 mb-3 text-center" id="scanLabel">
                👤 Scan Student Barcode First
            </label>
            <input
                type="text"
                id="barcodeInput"
                autofocus
                autocomplete="off"
                placeholder="Scanner will read barcodes automatically..."
                class="w-full px-4 py-4 border-2 border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 text-center text-xl font-mono font-bold tracking-widest bg-gray-50" />
            <p class="text-xs text-green-600 mt-3 text-center font-semibold">🔴 Hardware Scanner Mode Active - Ready to scan</p>
        </div>
    </div>

    <!-- Recent Scans -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Scan History</h2>
                <div id="scanHistory" class="space-y-3 max-h-96 overflow-y-auto">
                    <p class="text-gray-500 text-sm">No scans yet...</p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="space-y-4">
            <div class="bg-blue-50 rounded-lg shadow-md p-6">
                <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-2">Total Scans</p>
                <p class="text-3xl font-bold text-blue-900" id="scanCount">0</p>
            </div>
            <div class="bg-green-50 rounded-lg shadow-md p-6">
                <p class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-2">Members Found</p>
                <p class="text-3xl font-bold text-green-900" id="memberCount">0</p>
            </div>
            <div class="bg-purple-50 rounded-lg shadow-md p-6">
                <p class="text-xs font-semibold text-purple-700 uppercase tracking-wide mb-2">Books Found</p>
                <p class="text-3xl font-bold text-purple-900" id="bookCount">0</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Details Popup -->
<div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-bold text-white" id="modalTitle">Barcode Details</h2>
            <button onclick="closeModal()" class="text-white hover:bg-blue-800 p-1 rounded">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Content -->
        <div id="modalContent" class="p-6">
            <!-- Filled by JavaScript -->
        </div>
    </div>
</div>

<script>
    let scanCount = 0;
    let memberCount = 0;
    let bookCount = 0;
    let currentMember = null;
    let isScanning = false; // Debounce flag
    const MIN_SCAN_INTERVAL = 300; // Minimum ms between scans

    // Prevent input autocomplete
    document.getElementById('barcodeInput').addEventListener('beforeinput', (e) => {
        e.target.setAttribute('autocomplete', 'off');
    });

    // Handle barcode input
    document.getElementById('barcodeInput').addEventListener('keypress', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const barcode = e.target.value.trim();

            // Clear input immediately for next scan
            e.target.value = '';

            if (barcode && !isScanning) {
                isScanning = true;
                await scanBarcode(barcode);
                // Debounce: wait before allowing next scan
                setTimeout(() => {
                    isScanning = false;
                    e.target.focus(); // Ensure focus is back
                }, MIN_SCAN_INTERVAL);
            } else {
                e.target.focus();
            }
        }
    });

    // Keep input always focused, but ignore clicks inside the modal so the user
    // can type in form fields there.
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('detailsModal');
        if (modal && modal.contains(e.target)) {
            // click is inside modal - do not steal focus
            return;
        }
        if (e.target.id !== 'barcodeInput') {
            document.getElementById('barcodeInput').focus();
        }
    });

    window.addEventListener('blur', () => {
        setTimeout(() => {
            document.getElementById('barcodeInput').focus();
        }, 100);
    });

    async function scanBarcode(barcode) {
        try {
            const response = await fetch(`ajax_handler.php?action=scan_barcode&barcode=${encodeURIComponent(barcode)}`);
            const data = await response.json();

            if (data.success) {
                scanCount++;
                document.getElementById('scanCount').textContent = scanCount;
                playBeep('success'); // Audio feedback

                // If no member is currently selected
                if (!currentMember) {
                    if (data.data.type === 'member') {
                        memberCount++;
                        document.getElementById('memberCount').textContent = memberCount;
                        setCurrentMember(data.data);
                        addToHistory(barcode, 'member', data.data.member.name);
                    } else if (data.data.type === 'book') {
                        showError('Please scan a student barcode first!');
                        playBeep('error');
                        addToHistory(barcode, 'not_found', 'Error: No student selected');
                    }
                }
                // If member is already selected
                else {
                    if (data.data.type === 'book') {
                        await issueBookToMember(data.data.book, currentMember.member);
                        bookCount++;
                        document.getElementById('bookCount').textContent = bookCount;
                        addToHistory(barcode, 'book', data.data.book.title);
                    } else if (data.data.type === 'member') {
                        setCurrentMember(data.data);
                        addToHistory(barcode, 'member', data.data.member.name);
                        playBeep('info');
                    }
                }
            } else {
                showError(data.message);
                playBeep('error');
                addToHistory(barcode, 'not_found', 'Not Found');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error scanning barcode. Please try again.');
            playBeep('error');
        }
    }

    // Show form to save a scanned book that's not in system
    function showSaveBookForm(barcode = '') {
        document.getElementById('modalTitle').textContent = 'Save New Book';
        const barcodeSection = barcode ?
            `<p class="text-sm text-gray-600 mb-2">Scanned Barcode: <span class="font-mono font-bold">${htmlEscape(barcode)}</span></p>` :
            `<div class="mb-3">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Barcode</label>
                <input id="newBookBarcode" type="text" class="w-full px-3 py-2 border-2 border-gray-300 rounded bg-white text-black focus:border-blue-500 focus:outline-none" placeholder="Enter barcode" />
            </div>`;
        document.getElementById('modalContent').innerHTML = `
            <div class="p-4">
                ${barcodeSection}
                <div class="mb-3">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Title *</label>
                    <input id="newBookTitle" type="text" class="w-full px-3 py-2 border-2 border-gray-300 rounded bg-white text-black focus:border-blue-500 focus:outline-none" placeholder="Enter book title" />
                </div>
                <div class="mb-3">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Author *</label>
                    <input id="newBookAuthor" type="text" class="w-full px-3 py-2 border-2 border-gray-300 rounded bg-white text-black focus:border-blue-500 focus:outline-none" placeholder="Enter author name" />
                </div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Category</label>
                        <input id="newBookCategory" type="text" class="w-full px-3 py-2 border-2 border-gray-300 rounded bg-white text-black focus:border-blue-500 focus:outline-none" placeholder="Science, Math..." />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Shelf</label>
                        <input id="newBookShelf" type="text" class="w-full px-3 py-2 border-2 border-gray-300 rounded bg-white text-black focus:border-blue-500 focus:outline-none" placeholder="A1, B2..." />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Total Copies</label>
                        <input id="newBookTotal" type="number" min="1" value="1" class="w-full px-3 py-2 border-2 border-gray-300 rounded bg-white text-black focus:border-blue-500 focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Available Copies</label>
                        <input id="newBookAvailable" type="number" min="0" value="1" class="w-full px-3 py-2 border-2 border-gray-300 rounded bg-white text-black focus:border-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded font-semibold transition">Cancel</button>
                    <button id="saveScannedBookBtn" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-semibold transition">Save Book</button>
                </div>
            </div>
        `;
        document.getElementById('detailsModal').classList.remove('hidden');
        // Attach handler directly via onclick property
        const saveBtn = document.getElementById('saveScannedBookBtn');
        saveBtn.onclick = function() {
            saveScannedBook(barcode);
        };
        // Auto-focus first input after render
        setTimeout(() => {
            if (barcode) {
                const titleInput = document.getElementById('newBookTitle');
                if (titleInput) titleInput.focus();
            } else {
                const barcodeInput = document.getElementById('newBookBarcode');
                if (barcodeInput) barcodeInput.focus();
            }
        }, 0);
    }

    async function saveScannedBook(barcode) {
        // if barcode param empty, try reading from input field added by manual mode
        if (!barcode) {
            const field = document.getElementById('newBookBarcode');
            barcode = field ? field.value.trim() : '';
        }

        const titleField = document.getElementById('newBookTitle');
        const authorField = document.getElementById('newBookAuthor');
        const categoryField = document.getElementById('newBookCategory');
        const shelfField = document.getElementById('newBookShelf');
        const totalField = document.getElementById('newBookTotal');
        const availField = document.getElementById('newBookAvailable');

        const title = titleField ? titleField.value.trim() : '';
        const author = authorField ? authorField.value.trim() : '';
        const category = categoryField ? categoryField.value.trim() : '';
        const shelf = shelfField ? shelfField.value.trim() : '';
        const total = totalField ? (parseInt(totalField.value, 10) || 1) : 1;
        const available = availField ? (parseInt(availField.value, 10) || total) : total;

        if (!barcode) {
            showError('Please enter a barcode.');
            return;
        }
        if (!title || !author) {
            showError('Title and Author are required.');
            return;
        }

        try {
            const res = await fetch('ajax_handler.php?action=save_scanned_book', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    barcode,
                    title,
                    author,
                    category,
                    shelf_location: shelf,
                    total_copies: total,
                    available_copies: available
                })
            });
            const json = await res.json();
            if (json.success) {
                playBeep('success');
                bookCount++;
                document.getElementById('bookCount').textContent = bookCount;
                addToHistory(barcode, 'book', title);
                showError(`✓ Book saved: ${title}`);
                saveState();
                setTimeout(() => {
                    closeModal();
                    document.getElementById('barcodeInput').focus();
                }, 1000);
            } else {
                showError(json.message || 'Failed to save book');
            }
        } catch (e) {
            console.error(e);
            showError('Error saving book: ' + e.message);
        }
    }

    function playBeep(type = 'success') {
        // Using Web Audio API to create beep sounds
        const audioContext = new(window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        if (type === 'success') {
            oscillator.frequency.value = 800;
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        } else if (type === 'error') {
            oscillator.frequency.value = 400;
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        } else if (type === 'info') {
            oscillator.frequency.value = 600;
            gainNode.gain.setValueAtTime(0.05, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.15);
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.15);
        }
    }

    function setCurrentMember(data) {
        const member = data.member;
        currentMember = data;

        // Update member status display
        document.getElementById('memberStatus').classList.remove('hidden');
        document.getElementById('currentMemberName').textContent = member.name;
        document.getElementById('currentMemberBarcode').textContent = member.barcode_id;
        document.getElementById('currentMemberPRN').textContent = member.prn_number || 'N/A';
        document.getElementById('scanLabel').textContent = '📚 Now scan books to issue them to ' + member.name;

        // Show member details popup for 2 seconds then auto-close
        showMemberDetails(data);
        setTimeout(() => {
            closeModal();
            document.getElementById('barcodeInput').focus();
        }, 2000);
    }

    function clearMember() {
        currentMember = null;
        document.getElementById('memberStatus').classList.add('hidden');
        document.getElementById('scanLabel').textContent = '👤 Scan Student Barcode First';
        document.getElementById('barcodeInput').value = '';
        document.getElementById('barcodeInput').focus();
        closeModal();
    }

    async function issueBookToMember(book, member) {
        try {
            const issueCount = currentMember.active_issues.length;
            if (issueCount >= member.max_issue_limit) {
                showError(`Member has reached issue limit (${member.max_issue_limit}/${member.max_issue_limit})`);
                playBeep('error');
                return;
            }

            const alreadyIssued = currentMember.active_issues.some(issue => issue.book_id === book.id);
            if (alreadyIssued) {
                showError(`This book is already issued to ${member.name}`);
                playBeep('error');
                return;
            }

            if (book.available_copies <= 0) {
                showError(`No copies available. (${book.available_copies}/${book.total_copies})`);
                playBeep('error');
                return;
            }

            const issueResponse = await fetch('ajax_handler.php?action=issue_book', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    member_id: member.id,
                    book_id: book.id
                })
            });

            const issueData = await issueResponse.json();

            if (issueData.success) {
                currentMember.active_issues.push({
                    id: book.id,
                    title: book.title,
                    barcode_id: book.barcode_id,
                    book_id: book.id,
                    issue_date: new Date().toISOString(),
                    due_date: issueData.data.due_date,
                    status: 'issued'
                });

                showQuickSuccess(book, member, issueData.data.due_date);
                playBeep('success');
            } else {
                showError(issueData.message || 'Failed to issue book');
                playBeep('error');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error issuing book. Please try again.');
            playBeep('error');
        }
    }

    function showQuickSuccess(book, member, dueDate) {
        document.getElementById('modalContent').innerHTML = `
        <div class="text-center py-6">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-green-100 rounded-full mb-3">
                <svg class="w-7 h-7 text-green-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">✅ Issued Successfully!</h3>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 my-4 text-left">
                <p class="text-sm font-bold text-gray-900 mb-2">${htmlEscape(book.title)}</p>
                <p class="text-xs text-gray-600 mb-3">Barcode: <span class="font-mono font-bold">${htmlEscape(book.barcode_id)}</span></p>
                <p class="text-xs text-gray-600">Due: <span class="font-bold">${new Date(dueDate).toLocaleDateString()}</span></p>
            </div>
            
            <p class="text-sm text-gray-700">Ready for next scan...</p>
        </div>
    `;
        document.getElementById('modalTitle').textContent = '📚 Book Issued';
        document.getElementById('detailsModal').classList.remove('hidden');

        // Auto-close after 1.5 seconds for continuous scanning
        setTimeout(() => {
            closeModal();
            document.getElementById('barcodeInput').focus();
        }, 1500);
    }

    function showError(message) {
        document.getElementById('modalContent').innerHTML = `
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
            <p class="text-2xl mb-2">⚠️</p>
            <p class="text-red-800 font-semibold mb-2">Error</p>
            <p class="text-red-700 text-sm">${htmlEscape(message)}</p>
        </div>
    `;
        document.getElementById('modalTitle').textContent = 'Error';
        document.getElementById('detailsModal').classList.remove('hidden');

        // Auto-close after 2 seconds
        setTimeout(() => {
            closeModal();
            document.getElementById('barcodeInput').focus();
        }, 2000);
    }

    function showMemberDetails(data) {
        const member = data.member;
        const issues = data.active_issues || [];

        let modalHTML = `
        <!-- Member Info Section -->
        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">${htmlEscape(member.name)}</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Barcode</p>
                    <p class="text-lg font-mono font-bold text-blue-600">${htmlEscape(member.barcode_id)}</p>
                </div>
                ${member.prn_number ? `
                <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">PRN</p>
                    <p class="text-lg font-mono font-bold text-purple-600">${htmlEscape(member.prn_number)}</p>
                </div>
                ` : ''}
            </div>
        </div>

        <!-- Active Issues Section -->
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">
                📚 Issued (${issues.length}/${member.max_issue_limit})
            </h4>
            ${issues.length > 0 ? `
                <div class="space-y-2">
                    ${issues.map((issue) => `
                        <div class="border border-gray-200 rounded p-2 bg-gray-50 text-xs">
                            <p class="font-semibold text-gray-900">${htmlEscape(issue.title)}</p>
                        </div>
                    `).join('')}
                </div>
            ` : `
                <div class="bg-green-50 border border-green-200 rounded p-2 text-center">
                    <p class="text-green-800 text-xs font-semibold">✓ Ready to issue books</p>
                </div>
            `}
        </div>
    `;

        document.getElementById('modalTitle').textContent = `👤 ${member.name} - Ready`;
        document.getElementById('modalContent').innerHTML = modalHTML;
        document.getElementById('detailsModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('detailsModal').classList.add('hidden');
    }

    function addToHistory(barcode, type, name) {
        const history = document.getElementById('scanHistory');
        if (history.querySelector('p')) {
            history.innerHTML = '';
        }

        const timestamp = new Date().toLocaleTimeString();
        const typeIcon = type === 'member' ? '👤' : type === 'book' ? '📚' : '❌';

        let bgClass = 'bg-gray-50 border-gray-200';
        if (type === 'member') bgClass = 'bg-blue-50 border-blue-200';
        else if (type === 'book') bgClass = 'bg-green-50 border-green-200';

        const item = document.createElement('div');
        item.className = `p-3 border rounded-md ${bgClass}`;
        item.innerHTML = `
        <div class="flex justify-between items-start">
            <div>
                <p class="font-semibold text-gray-900">${typeIcon} ${htmlEscape(name)}</p>
                <p class="text-xs text-gray-600 font-mono">${htmlEscape(barcode)}</p>
            </div>
            <span class="text-xs text-gray-500">${timestamp}</span>
        </div>
    `;
        history.insertBefore(item, history.firstChild);

        while (history.children.length > 10) {
            history.removeChild(history.lastChild);
        }
    }

    function htmlEscape(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Focus on input when page loads
    window.addEventListener('load', () => {
        document.getElementById('barcodeInput').focus();
    });

    // Auto-focus on modal close
    document.getElementById('detailsModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('detailsModal')) {
            closeModal();
            document.getElementById('barcodeInput').focus();
        }
    });
</script>

<style>
    #detailsModal {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<?php include 'footer.php'; ?>