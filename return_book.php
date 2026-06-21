<?php
require_once 'config/db.php';
include 'header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_issue_id'])) {
    $issue_id = $_POST['return_issue_id'];
    $book_id = $_POST['book_id'];

    try {
        $pdo->beginTransaction();

        // 1. Mark Issue as Returned
        $stmt = $pdo->prepare("UPDATE issues SET return_date = NOW(), status = 'returned' WHERE id = ? AND status = 'issued'");
        $stmt->execute([$issue_id]);

        if ($stmt->rowCount() == 0) {
            throw new Exception("Could not find active issue to return.");
        }

        // 2. Increment Stock
        $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
        $stmt->execute([$book_id]);

        $pdo->commit();
        $message = "Book returned successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error returning book: " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_return_barcode'])) {
    $barcode = trim($_POST['auto_return_barcode']);
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id, title FROM books WHERE barcode_id = ?");
        $stmt->execute([$barcode]);
        $book = $stmt->fetch();

        if ($book) {
            $stmt = $pdo->prepare("SELECT id FROM issues WHERE book_id = ? AND status = 'issued'");
            $stmt->execute([$book['id']]);
            $issue = $stmt->fetch();

            if ($issue) {
                // Return it
                $stmt = $pdo->prepare("UPDATE issues SET return_date = NOW(), status = 'returned' WHERE id = ?");
                $stmt->execute([$issue['id']]);

                // Increment stock
                $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                $stmt->execute([$book['id']]);

                $pdo->commit();
                $message = "Book '{$book['title']}' returned successfully.";
            } else {
                $pdo->rollBack();
                $error = "Book '{$book['title']}' is not currently issued to anyone.";
            }
        } else {
            $pdo->rollBack();
            $error = "Barcode does not match any book in the system.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error returning book: " . $e->getMessage();
    }
}
?>

<div class="page-header mb-8">
    <h1 class="page-title text-2xl font-bold text-slate-800">Return Book</h1>
    <p class="text-slate-500 mt-1">Scan member card to view loans and process returns.</p>
</div>

<?php if ($message): ?>
    <div class="bg-emerald-50 text-emerald-800 p-4 rounded-lg mb-5 border border-emerald-200 font-medium">
        <?php echo $message; ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-50 text-red-800 p-4 rounded-lg mb-5 border border-red-200 font-medium">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card bg-white p-4 sm:p-6 shadow-sm rounded-lg mb-8 border-l-4 border-indigo-500">
    <div class="flex flex-col sm:flex-row gap-4">
        <input type="text" id="member_barcode_return" placeholder="Scan Book OR Member Barcode..." autofocus
            class="flex-1 p-3.5 text-lg border-2 border-indigo-100 rounded-lg outline-none focus:border-indigo-500 transition-colors w-full">
        <button type="button" onclick="loadLoans()" class="px-8 py-3.5 bg-indigo-500 hover:bg-indigo-600 text-white font-semibold rounded-lg transition-colors w-full sm:w-auto whitespace-nowrap">Find Loans</button>
    </div>
</div>

<div id="loansContainer"></div>

<script>
    const returnInput = document.getElementById('member_barcode_return');
    returnInput.addEventListener('change', loadLoans);

    // Handle barcode scanner Enter key press
    returnInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadLoans();
        }
    });

    function loadLoans() {
        const barcode = returnInput.value.trim();
        if (!barcode) return;

        const container = document.getElementById('loansContainer');
        container.innerHTML = '<p>Loading loans...</p>';

        fetch(`ajax_handler.php?action=scan_barcode&barcode=${encodeURIComponent(barcode)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (data.data.type === 'book') {
                        // A book was scanned. Auto-return it!
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `<input type="hidden" name="auto_return_barcode" value="${barcode}">`;
                        document.body.appendChild(form);
                        form.submit();
                    } else if (data.data.type === 'member') {
                        // A member was scanned. Show their active loans.
                        displayLoansHTML(data.data.active_issues, container);
                    }
                } else {
                    container.innerHTML = `<div class="card bg-red-50 p-6 rounded-lg text-red-600 text-center font-medium border border-red-200">${data.message}</div>`;
                }
            });
    }

    function displayLoansHTML(issues, container) {
        if (!issues || issues.length === 0) {
            container.innerHTML = '<div class="card">No active loans for this member.</div>';
            return;
        }

        let html = '<div class="card bg-white p-0 sm:p-6 shadow-sm rounded-lg overflow-hidden border border-slate-200"><h3 class="font-bold text-lg text-slate-800 mb-4 px-4 sm:px-0 mt-4 sm:mt-0">Active Loans</h3><div class="overflow-x-auto w-full"><table class="w-full text-left min-w-[600px] border-collapse">';
        html += `<thead class="bg-slate-50 text-slate-500 font-semibold text-xs uppercase tracking-wider border-b border-slate-200"><tr><th class="p-4">Book</th><th class="p-4">Issue Date</th><th class="p-4">Due Date</th><th class="p-4">Action</th></tr></thead><tbody class="divide-y divide-slate-100">`;

        issues.forEach(issue => {
            html += `<tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-4">
                            <strong class="text-slate-800">${issue.title}</strong><br>
                            <small class="text-slate-500 font-mono">${issue.book_barcode}</small>
                        </td>
                        <td class="p-4 text-slate-600 text-sm whitespace-nowrap">${issue.issue_date.split(' ')[0]}</td>
                        <td class="p-4 text-slate-600 text-sm whitespace-nowrap ${isOverdueLocally(issue.due_date) ? 'text-red-500 font-semibold' : ''}">${issue.due_date.split(' ')[0]}</td>
                        <td class="p-4">
                            <form method="POST" onsubmit="return confirm('Confirm return?');">
                                <input type="hidden" name="return_issue_id" value="${issue.id}">
                                <input type="hidden" name="book_id" value="${issue.book_id}">
                                <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-2 px-4 rounded-lg transition-colors whitespace-nowrap">Return Book</button>
                            </form>
                        </td>
                     </tr>`;
        });

        html += '</tbody></table></div></div>';
        container.innerHTML = html;
    }

    function isOverdueLocally(dateStr) {
        return new Date(dateStr) < new Date();
    }
</script>

<?php include 'footer.php'; ?>