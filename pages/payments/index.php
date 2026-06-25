<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header('Location: ' . get_role_home_url($user_role));
    exit();
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

$error = '';
$success = '';

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$term_id = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;

$students = $conn->query("SELECT DISTINCT s.id, s.admission_no, s.first_name, s.last_name, c.class_name
    FROM student_fees sf
    INNER JOIN students s ON sf.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE sf.is_paid = 0
    ORDER BY s.first_name, s.last_name")->fetch_all(MYSQLI_ASSOC);

$sessions = $conn->query("SELECT id, session_name, is_active FROM sessions ORDER BY is_active DESC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

if ($session_id <= 0 && !empty($sessions)) {
    $session_id = (int)$sessions[0]['id'];
}

if ($term_id <= 0 && $session_id > 0) {
    foreach ($terms as $term) {
        if ((int)$term['session_id'] === $session_id) {
            $term_id = (int)$term['id'];
            break;
        }
    }
}

$selected_student = null;
$unpaid_fees = [];
$student_summary = ['total' => 0, 'paid' => 0, 'unpaid' => 0];

if ($student_id > 0) {
    $stmt = $conn->prepare("SELECT s.id, s.first_name, s.last_name, s.admission_no, c.class_name, p.full_name AS parent_name
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN parents p ON s.parent_id = p.id
        WHERE s.id = ?");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $selected_student = $stmt->get_result()->fetch_assoc();

    if ($selected_student) {
        $sql = "SELECT sf.id, sf.amount, sf.is_paid, sf.session_id, sf.term_id, fc.fee_name, s.session_name, t.term_name
            FROM student_fees sf
            LEFT JOIN fee_categories fc ON sf.fee_category_id = fc.id
            LEFT JOIN sessions s ON sf.session_id = s.id
            LEFT JOIN terms t ON sf.term_id = t.id
            WHERE sf.student_id = ?";
        $params = [$student_id];
        $types = 'i';

        if ($session_id > 0) {
            $sql .= " AND sf.session_id = ?";
            $params[] = $session_id;
            $types .= 'i';
        }
        if ($term_id > 0) {
            $sql .= " AND (sf.term_id = ? OR sf.term_id IS NULL)";
            $params[] = $term_id;
            $types .= 'i';
        }

        $sql .= " ORDER BY sf.term_id ASC, sf.id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $unpaid_fees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($unpaid_fees as $fee) {
            $student_summary['total'] += (float)$fee['amount'];
            if ($fee['is_paid']) {
                $student_summary['paid'] += (float)$fee['amount'];
            } else {
                $student_summary['unpaid'] += (float)$fee['amount'];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $record_student_id = (int)($_POST['student_id'] ?? 0);
    $record_fee_ids = array_map('intval', (array)($_POST['fee_ids'] ?? []));
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');
    $payment_reference = trim($_POST['payment_reference'] ?? '');
    $receipt_number = trim($_POST['receipt_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($record_student_id <= 0) {
        $error = 'Select a student first.';
    } elseif (empty($record_fee_ids)) {
        $error = 'Select at least one unpaid fee.';
    } else {
        $placeholders = implode(',', array_fill(0, count($record_fee_ids), '?'));
        $types = 'i' . str_repeat('i', count($record_fee_ids));
        $sql = "SELECT id, amount FROM student_fees WHERE student_id = ? AND is_paid = 0 AND id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $bindValues = array_merge([$record_student_id], $record_fee_ids);
        $stmt->bind_param($types, ...$bindValues);
        $stmt->execute();
        $valid_fees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($valid_fees)) {
            $error = 'No valid unpaid fees were selected.';
        } else {
            $amount_paid = 0;
            foreach ($valid_fees as $fee) {
                $amount_paid += (float)$fee['amount'];
            }

            if ($receipt_number === '') {
                $receipt_number = 'RCPT-' . date('YmdHis') . '-' . $record_student_id;
            }
            if ($payment_reference === '') {
                $payment_reference = 'MANUAL-' . strtoupper(bin2hex(random_bytes(4)));
            }

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO payments (student_id, bursar_id, amount_paid, payment_method, payment_reference, payment_date, receipt_number, notes) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?)");
                $bursar_id = (int)$user_id;
                $stmt->bind_param('iidssss', $record_student_id, $bursar_id, $amount_paid, $payment_method, $payment_reference, $receipt_number, $notes);
                $stmt->execute();

                foreach ($valid_fees as $fee) {
                    $update = $conn->prepare("UPDATE student_fees SET is_paid = 1 WHERE id = ?");
                    $update->bind_param('i', $fee['id']);
                    $update->execute();
                }

                $conn->commit();
                $success = 'Payment recorded successfully.';
                $student_id = $record_student_id;
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error recording payment: ' . $e->getMessage();
            }
        }
    }
}

$recent_payments = $conn->query("SELECT p.id, p.amount_paid, p.payment_method, COALESCE(p.payment_reference, '') AS payment_reference,
    COALESCE(p.receipt_number, '') AS receipt_number, COALESCE(p.payment_date, p.created_at, '') AS payment_date,
    s.first_name, s.last_name, s.admission_no, c.class_name
    FROM payments p
    LEFT JOIN students s ON p.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY COALESCE(p.payment_date, p.created_at) DESC, p.id DESC
    LIMIT 50")->fetch_all(MYSQLI_ASSOC);
?>

<style>
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title .breadcrumb {
        font-size: 0.875rem;
        color: var(--gray-500);
        margin-top: 0.25rem;
    }

    .page-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: var(--white);
        border-radius: 12px;
        padding: 1.25rem;
        border: 1px solid var(--gray-200);
        box-shadow: var(--shadow-sm);
    }

    .stat-card-label {
        font-size: 0.8rem;
        color: var(--gray-600);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .stat-card-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-top: 0.4rem;
    }

    .stat-card-note {
        font-size: 0.85rem;
        color: var(--gray-500);
        margin-top: 0.35rem;
    }

    .content-grid {
        display: grid;
        grid-template-columns: minmax(320px, 1.1fr) minmax(420px, 1.5fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .card {
        background: var(--white);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }

    .card-header h2 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin: 0;
    }

    .card-body { padding: 1.5rem; }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin-bottom: 1rem;
    }

    .form-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--gray-700);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .form-control {
        padding: 0.6rem 0.9rem;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 0.9rem;
        color: var(--gray-900);
        background: var(--white);
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        width: 100%;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.6rem 1.25rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: inherit;
        white-space: nowrap;
    }

    .btn-primary { background: var(--primary-color); color: var(--white); }
    .btn-primary:hover { background: var(--primary-dark); box-shadow: 0 2px 8px rgba(30, 64, 175, 0.3); }
    .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
    .btn-secondary:hover { background: var(--gray-300); }
    .btn-outline { border: 1px solid var(--primary-color); background: transparent; color: var(--primary-color); }
    .btn-outline:hover { background: var(--primary-color); color: var(--white); }
    .btn-sm { padding: 0.4rem 0.85rem; font-size: 0.8rem; }

    .table-wrapper { overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .table thead { background: var(--gray-50); }
    .table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: var(--gray-700);
        border-bottom: 2px solid var(--gray-200);
        white-space: nowrap;
    }
    .table td {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-200);
        color: var(--gray-800);
        vertical-align: middle;
    }
    .table tbody tr:hover { background: var(--gray-50); }
    .table tbody tr:last-child td { border-bottom: none; }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.65rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-primary { background: #eff6ff; color: var(--primary-color); }

    .empty-state {
        text-align: center;
        padding: 4rem 1.5rem;
        color: var(--gray-400);
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.4;
        display: block;
    }

    .notice {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }

    .notice-error { background: #fee2e2; color: var(--danger-color); border: 1px solid #fca5a5; }
    .notice-success { background: #d1fae5; color: var(--success-color); border: 1px solid #6ee7b7; }

    .fee-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .fee-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border: 1px solid var(--gray-200);
        border-radius: 10px;
        background: var(--gray-50);
    }

    .fee-item strong { color: var(--gray-900); }

    .fee-checkbox {
        transform: scale(1.05);
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    @media (max-width: 1024px) {
        .content-grid { grid-template-columns: 1fr; }
        .summary-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; }
        .main-content { padding: 1rem; }
    }
</style>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-credit-card"></i> Payments</h1>
                <div class="breadcrumb">Record manual payments and review recent receipts</div>
            </div>
            <div class="page-actions">
                <a href="<?php echo BASE_URL; ?>pages/fees/" class="btn btn-secondary"><i class="fas fa-money-bill"></i> Fee Setup</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="notice notice-error"><i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="notice notice-success"><i class="fas fa-circle-check"></i><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-label">Pending Students</div>
                <div class="stat-card-value"><?php echo count($students); ?></div>
                <div class="stat-card-note">Students with unpaid fees</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Recent Payments</div>
                <div class="stat-card-value"><?php echo count($recent_payments); ?></div>
                <div class="stat-card-note">Latest receipts recorded</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Selected Student</div>
                <div class="stat-card-value"><?php echo $selected_student ? 'Yes' : 'No'; ?></div>
                <div class="stat-card-note">Choose a student to record payment</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-user-check"></i> Select Student</h2>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="form-group">
                            <label class="form-label">Student</label>
                            <select name="student_id" class="form-control" onchange="this.form.submit()">
                                <option value="">Select a student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo (int)$student['id']; ?>" <?php echo $student_id === (int)$student['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' - ' . $student['admission_no'] . ' (' . ($student['class_name'] ?? 'No Class') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="summary-grid">
                            <div class="stat-card">
                                <div class="stat-card-label">Total Due</div>
                                <div class="stat-card-value">₦<?php echo number_format($student_summary['total'], 2); ?></div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-card-label">Paid</div>
                                <div class="stat-card-value">₦<?php echo number_format($student_summary['paid'], 2); ?></div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-card-label">Outstanding</div>
                                <div class="stat-card-value">₦<?php echo number_format($student_summary['unpaid'], 2); ?></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Session</label>
                            <select name="session_id" class="form-control" onchange="this.form.submit()">
                                <option value="0">All sessions</option>
                                <?php foreach ($sessions as $session): ?>
                                    <option value="<?php echo (int)$session['id']; ?>" <?php echo $session_id === (int)$session['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($session['session_name']); ?><?php echo $session['is_active'] ? ' (Active)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Term</label>
                            <select name="term_id" class="form-control" onchange="this.form.submit()">
                                <option value="0">All terms</option>
                                <?php foreach ($terms as $term): ?>
                                    <?php if ($session_id <= 0 || (int)$term['session_id'] === $session_id): ?>
                                        <option value="<?php echo (int)$term['id']; ?>" <?php echo $term_id === (int)$term['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($term['term_name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-receipt"></i> Manual Payment Entry</h2>
                </div>
                <div class="card-body">
                    <?php if (!$selected_student): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash empty-state-icon"></i>
                            <p>Select a student to view unpaid fees and record a manual payment.</p>
                        </div>
                    <?php elseif (empty($unpaid_fees)): ?>
                        <div class="empty-state">
                            <i class="fas fa-circle-check empty-state-icon"></i>
                            <p>No fee records found for the selected student.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" id="manualPaymentForm">
                            <input type="hidden" name="record_payment" value="1">
                            <input type="hidden" name="student_id" value="<?php echo (int)$selected_student['id']; ?>">

                            <div class="form-group">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-control">
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="POS">POS</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Reference</label>
                                <input type="text" name="payment_reference" class="form-control" placeholder="Optional manual reference">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Receipt Number</label>
                                <input type="text" name="receipt_number" class="form-control" placeholder="Optional receipt number">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-control" placeholder="Optional note"></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Select Fees to Mark as Paid</label>
                                <div class="fee-list">
                                    <?php foreach ($unpaid_fees as $fee): ?>
                                        <?php if (!(int)$fee['is_paid']): ?>
                                            <label class="fee-item">
                                                <span>
                                                    <strong><?php echo htmlspecialchars($fee['fee_name'] ?? 'Fee'); ?></strong>
                                                    <span style="display:block;color:var(--gray-500);font-size:0.8rem;">
                                                        <?php echo htmlspecialchars($fee['term_name'] ?? 'Session-level'); ?> · <?php echo htmlspecialchars($fee['session_name'] ?? ''); ?>
                                                    </span>
                                                </span>
                                                <span style="display:flex;align-items:center;gap:0.75rem;">
                                                    <span class="badge badge-primary">₦<?php echo number_format((float)$fee['amount'], 2); ?></span>
                                                    <input type="checkbox" name="fee_ids[]" value="<?php echo (int)$fee['id']; ?>" class="fee-checkbox" data-amount="<?php echo (float)$fee['amount']; ?>">
                                                </span>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="stat-card" style="margin-bottom:1rem;">
                                <div class="stat-card-label">Auto Calculated Amount</div>
                                <div class="stat-card-value" id="manualPaymentTotal">₦0.00</div>
                                <div class="stat-card-note">Based on selected fee items</div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                                <i class="fas fa-save"></i> Record Payment
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-clock-rotate-left"></i> Recent Payments</h2>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($recent_payments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt empty-state-icon"></i>
                        <p>No payment records found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Receipt</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td style="font-weight:600;color:var(--gray-900);">
                                            <?php echo htmlspecialchars(trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''))); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['class_name'] ?? '—'); ?></td>
                                        <td><span class="badge badge-primary">₦<?php echo number_format((float)$payment['amount_paid'], 2); ?></span></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_reference'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['receipt_number'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_date'] ?? '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    const manualPaymentTotal = document.getElementById('manualPaymentTotal');
    const feeCheckboxes = Array.from(document.querySelectorAll('.fee-checkbox'));

    function updateManualPaymentTotal() {
        const total = feeCheckboxes
            .filter((checkbox) => checkbox.checked)
            .reduce((sum, checkbox) => sum + (parseFloat(checkbox.dataset.amount || '0') || 0), 0);

        manualPaymentTotal.textContent = '₦' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    feeCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateManualPaymentTotal));
    updateManualPaymentTotal();
</script>

<?php include_once '../../includes/footer.php'; ?>