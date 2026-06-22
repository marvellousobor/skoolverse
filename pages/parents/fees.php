<?php
include_once '../../includes/auth_check.php';
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

if ($user_role != ROLE_PARENT) {
    header('Location: ../dashboard.php');
    exit();
}

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($student_id <= 0) {
    header('Location: children.php');
    exit();
}

$stmt = $conn->prepare("SELECT s.id, s.first_name, s.last_name, s.admission_no, c.class_name, s.parent_id FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo '<div class="main-wrapper"><main class="main-content"><div class="card"><div class="card-body"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-user-slash"></i></div><p>Student not found.</p></div></div></div></main></div>';
    include_once '../../includes/footer.php';
    exit();
}

$stmt = $conn->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();

if (!$parent) {
    header('Location: children.php');
    exit();
}

$stmt = $conn->prepare("SELECT 1 FROM students WHERE id = ? AND (parent_id = ? OR id IN (SELECT student_id FROM student_parent_links WHERE parent_id = ?))");
$stmt->bind_param('iii', $student_id, $parent['id'], $parent['id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo '<div class="main-wrapper"><main class="main-content"><div class="card"><div class="card-body"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-lock"></i></div><p>You do not have permission to manage fees for this student.</p></div></div></div></main></div>';
    include_once '../../includes/footer.php';
    exit();
}

$active_session = $conn->query("SELECT id, session_name FROM sessions WHERE is_active = 1 LIMIT 1")->fetch_assoc();
$session_id = $active_session ? (int)$active_session['id'] : 0;

$fees = [];
if ($session_id > 0) {
    $stmt = $conn->prepare("SELECT sf.id, sf.amount, sf.term_id, sf.fee_category_id, sf.is_paid, fc.fee_name, t.term_name
        FROM student_fees sf
        LEFT JOIN fee_categories fc ON sf.fee_category_id = fc.id
        LEFT JOIN terms t ON sf.term_id = t.id
        WHERE sf.student_id = ? AND sf.session_id = ?
        ORDER BY sf.term_id ASC");
    $stmt->bind_param('ii', $student_id, $session_id);
    $stmt->execute();
    $fees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$total_amount = 0; $paid_amount = 0; $unpaid_amount = 0;
foreach ($fees as $f) {
    $total_amount += (float)$f['amount'];
    if ($f['is_paid']) { $paid_amount += (float)$f['amount']; } else { $unpaid_amount += (float)$f['amount']; }
}
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-money-bill-wave"></i> Pay Fees</h1>
                <span class="breadcrumb"><?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?> · <?php echo htmlspecialchars($student['admission_no']); ?> · <?php echo htmlspecialchars($student['class_name'] ?? '—'); ?></span>
            </div>
            <div class="page-actions">
                <a href="children.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="results.php?student_id=<?php echo $student_id; ?>" class="btn btn-outline"><i class="fas fa-chart-bar"></i> View Results</a>
            </div>
        </div>

        <?php if ($session_id === 0): ?>
            <div class="card"><div class="card-body">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-triangle-exclamation"></i></div>
                    <p>No active session configured. Ask admin to activate a session.</p>
                </div>
            </div></div>
        <?php else: ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Total Fees</div>
                            <div class="stat-card-value">₦<?php echo number_format($total_amount, 2); ?></div>
                            <div class="stat-card-change positive"><?php echo htmlspecialchars($active_session['session_name']); ?></div>
                        </div>
                        <div class="stat-card-icon primary"><i class="fas fa-coins"></i></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Paid</div>
                            <div class="stat-card-value">₦<?php echo number_format($paid_amount, 2); ?></div>
                            <div class="stat-card-change positive"><i class="fas fa-check"></i> Settled</div>
                        </div>
                        <div class="stat-card-icon success"><i class="fas fa-circle-check"></i></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Outstanding</div>
                            <div class="stat-card-value">₦<?php echo number_format($unpaid_amount, 2); ?></div>
                            <div class="stat-card-change negative"><i class="fas fa-clock"></i> Pending</div>
                        </div>
                        <div class="stat-card-icon danger"><i class="fas fa-triangle-exclamation"></i></div>
                    </div>
                </div>
            </div>

            <?php if (empty($fees)): ?>
                <div class="card"><div class="card-body">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
                        <p>No fees assigned for <?php echo htmlspecialchars($active_session['session_name']); ?>.</p>
                    </div>
                </div></div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-receipt"></i> Fees Breakdown</h2>
                    </div>
                    <div class="card-body">
                        <form id="feesForm">
                        <?php
                            $term_map = [];
                            foreach ($fees as $ff) {
                                $key = $ff['term_id'] === null ? 'session' : $ff['term_id'];
                                if (!isset($term_map[$key])) {
                                    $term_map[$key] = $ff['term_name'] ?? 'All / Session';
                                }
                            }
                        ?>
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;flex-wrap:wrap;">
                            <label style="font-size:0.85rem;font-weight:600;color:var(--gray-700);">Filter by:</label>
                            <select id="termFilter" style="padding:0.5rem 0.75rem;border:1px solid var(--gray-300);border-radius:8px;">
                                <option value="all">All Terms & Session</option>
                                <?php foreach ($term_map as $k => $name): ?>
                                    <option value="<?php echo $k; ?>"><?php echo htmlspecialchars($name === 'All / Session' ? 'Session-level fees' : $name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="selectVisibleBtn" class="btn btn-secondary btn-sm"><i class="fas fa-check-double"></i> Select Visible</button>
                            <button type="button" id="deselectVisibleBtn" class="btn btn-secondary btn-sm"><i class="fas fa-xmark"></i> Deselect Visible</button>
                        </div>

                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Term</th>
                                        <th>Fee</th>
                                        <th style="text-align:right;">Amount (₦)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="feesTableBody">
                                    <?php foreach ($fees as $f): ?>
                                        <?php $term_key = $f['term_id'] === null ? 'session' : $f['term_id']; ?>
                                        <tr class="fee-row" data-term="<?php echo $term_key; ?>">
                                            <td>
                                                <?php if (!$f['is_paid']): ?>
                                                    <div style="display:flex;align-items:center;gap:0.5rem;">
                                                        <input type="checkbox" name="fee_ids[]" value="<?php echo $f['id']; ?>" class="fee-checkbox" data-term="<?php echo $term_key; ?>" data-amount="<?php echo $f['amount']; ?>">
                                                        <button type="button" class="btn btn-primary btn-sm pay-one" data-feeid="<?php echo $f['id']; ?>"><i class="fas fa-credit-card"></i> Pay</button>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color:var(--gray-400);">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($f['term_name'] ?? 'All / Session'); ?></td>
                                            <td style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($f['fee_name'] ?? 'Fee'); ?></td>
                                            <td style="text-align:right;" class="amount-cell"><?php echo number_format($f['amount'],2); ?></td>
                                            <td><?php echo $f['is_paid'] ? '<span class="badge badge-success"><i class="fas fa-check"></i> Paid</span>' : '<span class="badge badge-danger"><i class="fas fa-clock"></i> Unpaid</span>'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="display:flex;gap:0.75rem;margin-top:1.25rem;flex-wrap:wrap;align-items:center;">
                            <button type="button" id="paySelectedBtn" class="btn btn-primary"><i class="fas fa-credit-card"></i> Pay Selected</button>
                            <button type="button" id="payAllBtn" class="btn btn-outline"><i class="fas fa-wallet"></i> Pay All Unpaid</button>
                        </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
function initiatePay(feeIds) {
    const checked = feeIds.map(String);
    if (checked.length === 0) { alert('Select at least one unpaid fee to pay.'); return; }
    let total = 0;
    checked.forEach(id => {
        const checkbox = document.querySelector('.fee-checkbox[value="'+id+'"]');
        if (!checkbox) return;
        const row = checkbox.closest('tr');
        const amtText = row.querySelector('.amount-cell').innerText.replace(/,/g,'');
        total += parseFloat(amtText) || 0;
    });
    if (!total || total <= 0) { alert('Invalid amount'); return; }
    if (typeof PaystackPop === 'undefined') { alert('Payment service failed to load. Please refresh the page and try again.'); return; }

    const handler = PaystackPop.setup({
        key: '<?php echo defined('PAYSTACK_PUBLIC_KEY') ? PAYSTACK_PUBLIC_KEY : ''; ?>',
        email: '<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>',
        amount: Math.round(total * 100),
        metadata: { custom_fields: [
            { display_name: "Student ID", variable_name: "student_id", value: '<?php echo $student_id; ?>' },
            { display_name: "Fee IDs", variable_name: "fee_ids", value: checked.join(',') }
        ]},
        callback: function(response){
            fetch('verify_paystack.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ reference: response.reference, student_id: <?php echo $student_id; ?>, fee_ids: checked })
            }).then(r => r.json()).then(data => {
                if (data.success) { alert('Payment recorded successfully.'); location.reload(); }
                else { alert('Verification failed: ' + (data.message || 'Unknown')); }
            });
        },
        onClose: function(){}
    });
    handler.openIframe();
}

document.getElementById('payAllBtn')?.addEventListener('click', function(){
    const ids = Array.from(document.querySelectorAll('.fee-checkbox')).map(cb => cb.value);
    initiatePay(ids);
});
document.getElementById('paySelectedBtn')?.addEventListener('click', function(){
    const ids = Array.from(document.querySelectorAll('.fee-checkbox:checked')).map(cb => cb.value);
    initiatePay(ids);
});
document.getElementById('feesTableBody')?.addEventListener('click', function(e){
    if (e.target && e.target.closest('.pay-one')) {
        const fid = e.target.closest('.pay-one').getAttribute('data-feeid');
        if (fid) initiatePay([fid]);
    }
});

const termFilter = document.getElementById('termFilter');
function applyFilter() {
    if (!termFilter) return;
    const val = termFilter.value;
    document.querySelectorAll('.fee-row').forEach(row => {
        const term = row.getAttribute('data-term');
        row.style.display = (val === 'all' || String(term) === String(val)) ? '' : 'none';
    });
}
termFilter?.addEventListener('change', applyFilter);
applyFilter();

document.getElementById('selectVisibleBtn')?.addEventListener('click', function(){
    document.querySelectorAll('.fee-row').forEach(row => { if (row.style.display !== 'none') { const cb = row.querySelector('.fee-checkbox'); if (cb) cb.checked = true; } });
});
document.getElementById('deselectVisibleBtn')?.addEventListener('click', function(){
    document.querySelectorAll('.fee-row').forEach(row => { if (row.style.display !== 'none') { const cb = row.querySelector('.fee-checkbox'); if (cb) cb.checked = false; } });
});
</script>

<?php include_once '../../includes/footer.php'; ?>
