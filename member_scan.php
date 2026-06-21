<?php
require_once 'config/db.php';
include 'header.php';
?>

<div class="page-header mb-8">
    <h1 class="page-title text-2xl font-bold text-slate-800">Member Profile Scanner</h1>
    <p class="text-slate-500 mt-1">Scan a student/member barcode ID to instantly view full profile and history.</p>
</div>

<!-- Search Bar -->
<div class="card border-l-4 border-indigo-500 mb-8 bg-white p-4 sm:p-6 shadow-sm rounded-lg">
    <label class="font-semibold block mb-2 text-slate-700">Scan Member Barcode</label>
    <div class="flex flex-col sm:flex-row gap-4">
        <input type="text" id="scanInput" placeholder="Click here and scan barcode..." autofocus
            class="flex-1 p-3.5 text-lg border-2 border-indigo-100 rounded-lg outline-none focus:border-indigo-500 transition-colors w-full">
        <button onclick="scanMember()" class="px-8 py-3.5 bg-indigo-500 hover:bg-indigo-600 text-white font-semibold rounded-lg transition-colors w-full sm:w-auto">Search</button>
    </div>
</div>

<!-- Results Container -->
<div id="profileContainer" style="display: none;">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Personal details -->
        <div class="card bg-white p-6 shadow-sm rounded-lg border border-slate-200 lg:col-span-1">
            <div class="text-center pb-5 border-b border-slate-100 mb-5">
                <div class="w-24 h-24 bg-indigo-50 text-indigo-500 rounded-full mx-auto mb-4 flex items-center justify-center text-4xl shadow-sm border border-indigo-100">👤</div>
                <h2 id="p_name" class="mb-1 text-slate-800 text-xl font-bold">-</h2>
                <div id="p_barcode" class="bg-slate-100 inline-block px-3 py-1 rounded-full text-sm text-slate-500 font-semibold tracking-wide border border-slate-200">-</div>
            </div>

            <div class="flex flex-col gap-4 text-sm">
                <div>
                    <span class="block text-xs text-slate-400 uppercase font-semibold mb-0.5">Status</span>
                    <span id="p_status" class="font-semibold">-</span>
                </div>
                <div>
                    <span class="block text-xs text-slate-400 uppercase font-semibold mb-0.5">Email</span>
                    <span id="p_email" class="text-slate-800">-</span>
                </div>
                <div>
                    <span class="block text-xs text-slate-400 uppercase font-semibold mb-0.5">Phone</span>
                    <span id="p_phone" class="text-slate-800">-</span>
                </div>
                <div>
                    <span class="block text-xs text-slate-400 uppercase font-semibold mb-0.5">Address</span>
                    <span id="p_address" class="text-slate-600 leading-relaxed">-</span>
                </div>
                <div>
                    <span class="block text-xs text-slate-400 uppercase font-semibold mb-0.5">Issue Limit</span>
                    <span id="p_limit" class="text-slate-800">-</span>
                </div>
            </div>
        </div>

        <!-- Right Column: Books -->
        <div class="flex flex-col gap-6 lg:col-span-2">

            <!-- CURRENTLY ISSUED -->
            <div class="card border-t-4 border-amber-500 bg-white p-6 shadow-sm rounded-b-lg rounded-t-sm">
                <h3 class="mb-4 flex items-center gap-2 font-bold text-lg text-slate-800">
                    📚 Currently Issued Books
                    <span id="issue_count" class="bg-amber-100 text-amber-700 px-2.5 py-0.5 rounded-full text-xs font-bold border border-amber-200">0</span>
                </h3>
                <div id="active_table_container" class="overflow-x-auto w-full">
                    <!-- Table goes here -->
                </div>
            </div>

            <!-- HISTORY -->
            <div class="card border-t-4 border-emerald-500 bg-white p-6 shadow-sm rounded-b-lg rounded-t-sm">
                <h3 class="mb-4 font-bold text-lg text-slate-800">📜 Recent History</h3>
                <div id="history_table_container" class="overflow-x-auto w-full">
                    <!-- Table goes here -->
                </div>
            </div>

        </div>

    </div>

</div>

<div id="notFoundMsg" class="hidden text-center mt-10 text-red-500 text-lg font-medium p-4 bg-red-50 rounded-lg border border-red-200 max-w-2xl mx-auto">
    Member not found with that Barcode ID.
</div>

<script>
    const input = document.getElementById('scanInput');

    // Auto-search on enter
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') scanMember();
    });

    function scanMember() {
        const barcode = input.value.trim();
        if (!barcode) return;

        fetch(`ajax_handler.php?action=get_member&barcode=${encodeURIComponent(barcode)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showProfile(data.data);
                } else {
                    document.getElementById('profileContainer').style.display = 'none';
                    document.getElementById('notFoundMsg').style.display = 'block';
                    document.getElementById('notFoundMsg').innerText = data.message;
                }
            })
            .catch(e => alert("System Error: " + e));
    }

    function showProfile(data) {
        document.getElementById('notFoundMsg').style.display = 'none';
        document.getElementById('profileContainer').style.display = 'block';

        const m = data.member;

        // Fill Left Column
        document.getElementById('p_name').innerText = m.name;
        document.getElementById('p_barcode').innerText = m.barcode_id;
        document.getElementById('p_email').innerText = m.email || "N/A";
        document.getElementById('p_phone').innerText = m.phone || "N/A";
        document.getElementById('p_address').innerText = m.address || "N/A";
        document.getElementById('p_limit').innerText = `${data.active_issues.length} / ${m.max_issue_limit}`;

        const statusEl = document.getElementById('p_status');
        statusEl.innerText = m.status.toUpperCase();
        statusEl.style.color = m.status === 'active' ? '#10b981' : '#ef4444';

        // Fill Active Issues
        document.getElementById('issue_count').innerText = data.active_issues.length;
        if (data.active_issues.length > 0) {
            let html = `<table class="w-full text-left min-w-[600px] border-collapse">
                        <thead class="bg-slate-50 text-slate-500 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
                            <tr><th class="p-4">Book Title</th><th class="p-4">Issued On</th><th class="p-4">Due Date</th></tr>
                        </thead><tbody class="divide-y divide-slate-100">`;
            data.active_issues.forEach(i => {
                html += `<tr class="text-sm">
                            <td class="p-4">
                                <div class="font-bold text-slate-800">${i.title}</div>
                                <div class="text-xs font-mono text-slate-500 mt-1">${i.book_barcode}</div>
                            </td>
                            <td class="p-4 text-slate-600">${i.issue_date.split(' ')[0]}</td>
                            <td class="p-4 font-medium ${isOverdue(i.due_date) ? 'text-red-500' : 'text-slate-700'}">${i.due_date.split(' ')[0]}</td>
                         </tr>`;
            });
            html += `</tbody></table>`;
            document.getElementById('active_table_container').innerHTML = html;
        } else {
            document.getElementById('active_table_container').innerHTML = '<div class="p-6 text-center text-slate-400 italic">No books currently issued.</div>';
        }

        // Fill History
        if (data.history.length > 0) {
            let html = `<table class="w-full text-left min-w-[500px] border-collapse">
                        <thead class="bg-slate-50 text-slate-500 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
                             <tr><th class="p-4">Book Title</th><th class="p-4">Returned On</th></tr>
                        </thead><tbody class="divide-y divide-slate-100">`;
            data.history.forEach(h => {
                html += `<tr class="text-sm">
                            <td class="p-4 font-medium text-slate-800">${h.title}</td>
                            <td class="p-4 text-slate-500">${h.return_date.split(' ')[0]}</td>
                         </tr>`;
            });
            html += `</tbody></table>`;
            document.getElementById('history_table_container').innerHTML = html;
        } else {
            document.getElementById('history_table_container').innerHTML = '<div class="p-6 text-center text-slate-400 italic">No history available.</div>';
        }
    }

    function isOverdue(dateStr) {
        return new Date(dateStr) < new Date();
    }
</script>

<?php include 'footer.php'; ?>