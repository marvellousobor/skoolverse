<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
require_once __DIR__ . '/../../includes/notifications_helper.php';

$conn->query("CREATE TABLE IF NOT EXISTS class_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    session_id INT NOT NULL,
    term_id INT NULL,
    fee_category_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (class_id),
    INDEX (session_id),
    INDEX (term_id),
    INDEX (fee_category_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    term_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_term_per_session (session_id, term_name),
    INDEX (session_id),
    INDEX (is_active),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
)");

$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name, is_active FROM sessions ORDER BY is_active DESC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id, is_active FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC) ?? [];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fee_name = trim($_POST['fee_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $term_id = !empty($_POST['term_id']) ? (int)$_POST['term_id'] : null;
    $class_ids = $_POST['class_ids'] ?? [];

    if ($fee_name == '' || $amount <= 0 || $session_id <= 0) {
        $error = "Fee name, amount, and session are required.";
    } elseif (empty($class_ids)) {
        $error = "Select at least one class this fee applies to.";
    } else {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("SELECT id FROM fee_categories WHERE fee_name = ?");
            $stmt->bind_param("s", $fee_name);
            $stmt->execute();
            $category = $stmt->get_result()->fetch_assoc();

            if ($category) {
                $fee_category_id = (int)$category['id'];
                $stmt = $conn->prepare("UPDATE fee_categories SET description = ? WHERE id = ?");
                $stmt->bind_param("si", $description, $fee_category_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO fee_categories (fee_name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $fee_name, $description);
                $stmt->execute();
                $fee_category_id = $conn->insert_id;
            }

            $class_count = 0;
            $student_fee_count = 0;

            foreach ($class_ids as $class_id) {
                $class_id = (int)$class_id;
                if ($class_id <= 0) {
                    continue;
                }

                if ($term_id === null) {
                    $stmt = $conn->prepare("SELECT id FROM class_fees WHERE class_id = ? AND session_id = ? AND term_id IS NULL AND fee_category_id = ?");
                    $stmt->bind_param("iii", $class_id, $session_id, $fee_category_id);
                } else {
                    $stmt = $conn->prepare("SELECT id FROM class_fees WHERE class_id = ? AND session_id = ? AND term_id = ? AND fee_category_id = ?");
                    $stmt->bind_param("iiii", $class_id, $session_id, $term_id, $fee_category_id);
                }
                $stmt->execute();
                $existing_class_fee = $stmt->get_result()->fetch_assoc();

                if ($existing_class_fee) {
                    $stmt = $conn->prepare("UPDATE class_fees SET amount = ? WHERE id = ?");
                    $stmt->bind_param("di", $amount, $existing_class_fee['id']);
                    $stmt->execute();
                } else {
                    $stmt = $conn->prepare("INSERT INTO class_fees (class_id, session_id, term_id, fee_category_id, amount) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiiid", $class_id, $session_id, $term_id, $fee_category_id, $amount);
                    $stmt->execute();
                }

                $class_count++;
                $students_stmt = $conn->prepare("SELECT id FROM students WHERE class_id = ? AND is_active = 1");
                $students_stmt->bind_param("i", $class_id);
                $students_stmt->execute();
                $students = $students_stmt->get_result();

                while ($student = $students->fetch_assoc()) {
                    $student_id = (int)$student['id'];

                    if ($term_id === null) {
                        $stmt = $conn->prepare("SELECT id FROM student_fees WHERE student_id = ? AND session_id = ? AND term_id IS NULL AND fee_category_id = ?");
                        $stmt->bind_param("iii", $student_id, $session_id, $fee_category_id);
                    } else {
                        $stmt = $conn->prepare("SELECT id FROM student_fees WHERE student_id = ? AND session_id = ? AND term_id = ? AND fee_category_id = ?");
                        $stmt->bind_param("iiii", $student_id, $session_id, $term_id, $fee_category_id);
                    }
                    $stmt->execute();
                    $existing_student_fee = $stmt->get_result()->fetch_assoc();

                    if ($existing_student_fee) {
                        $stmt = $conn->prepare("UPDATE student_fees SET amount = ? WHERE id = ?");
                        $stmt->bind_param("di", $amount, $existing_student_fee['id']);
                        $stmt->execute();
                    } else {
                        $stmt = $conn->prepare("INSERT INTO student_fees (student_id, session_id, term_id, fee_category_id, amount, is_paid) VALUES (?, ?, ?, ?, ?, 0)");
                        $stmt->bind_param("iiiid", $student_id, $session_id, $term_id, $fee_category_id, $amount);
                        $stmt->execute();
                        $student_fee_count++;
                    }
                }
            }

            $conn->commit();
            $success = "Fee saved for $class_count class(es). Added $student_fee_count student fee record(s).";

            // Notify students about the new fee
            $term_name_str = '';
            if ($term_id) {
                $tr = $conn->query("SELECT term_name FROM terms WHERE id = $term_id")->fetch_assoc();
                $term_name_str = $tr ? " (" . $tr['term_name'] . ")" : '';
            }
            $fee_label = $fee_name . $term_name_str;
            send_notification_to_role($conn, 'fee', "New Fee: $fee_label", "A new fee of ₦" . number_format($amount, 2) . " has been assigned to your class.", "pages/fees/", 'student');
            send_notification_to_role($conn, 'fee', "New Fee: $fee_label", "A new fee of ₦" . number_format($amount, 2) . " has been assigned to your ward's class.", "pages/fees/", 'parent');
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error saving fee: " . $e->getMessage();
        }
    }
}

$assigned_fees = $conn->query("SELECT class_fees.amount, class_fees.created_at, classes.class_name, sessions.session_name, terms.term_name, fee_categories.fee_name
    FROM class_fees
    INNER JOIN classes ON class_fees.class_id = classes.id
    INNER JOIN sessions ON class_fees.session_id = sessions.id
    LEFT JOIN terms ON class_fees.term_id = terms.id
    INNER JOIN fee_categories ON class_fees.fee_category_id = fee_categories.id
    ORDER BY class_fees.created_at DESC, fee_categories.fee_name, classes.class_name
    LIMIT 100");

// Recent payments (for admin overview)
$recent_payments = $conn->query(
    "SELECT p.id, p.student_id, p.amount_paid,
        COALESCE(p.payment_reference, '') AS reference,
        COALESCE(p.payment_method, '') AS payment_method,
        COALESCE(p.created_at, p.payment_date, '') AS created_at,
        s.first_name, s.last_name, s.admission_no, c.class_name
    FROM payments p
    LEFT JOIN students s ON p.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY COALESCE(p.created_at, p.payment_date) DESC
    LIMIT 200"
)->fetch_all(MYSQLI_ASSOC);
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
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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

    .form-layout {
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        gap: 1.5rem;
        align-items: start;
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
        margin-bottom: 1.25rem;
    }

    .form-group label {
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

    .btn-outline-sm {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border: 1px solid var(--gray-300);
        border-radius: 7px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        background: var(--white);
        color: var(--gray-700);
        transition: all 0.2s;
        font-family: 'Segoe UI', sans-serif;
    }

    .btn-outline-sm:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background: #eff6ff;
    }

    .tab-bar {
        display: flex;
        gap: 0.25rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--white);
    }

    .tab-btn {
        padding: 1rem 1.25rem;
        border: none;
        background: transparent;
        font-weight: 600;
        color: var(--gray-500);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
    }

    .tab-btn.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    .tab-panel {
        padding: 0;
    }

    .table-wrapper { overflow-x: auto; }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .table thead { background: var(--gray-50); }
    .table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: var(--gray-700);
        border-bottom: 2px solid var(--gray-200);
    }
    .table td {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-200);
        vertical-align: top;
    }
    .table tbody tr:hover { background: var(--gray-50); }
    .table tbody tr:last-child td { border-bottom: none; }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .badge-primary { background: var(--primary-light); color: var(--white); }

    .badge-soft {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        background: #eff6ff;
        color: var(--primary-color);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: var(--gray-400);
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state p {
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
    }

    .notice {
        margin-bottom: 1rem;
        padding: 0.95rem 1.1rem;
        border-radius: 10px;
        border: 1px solid transparent;
        font-weight: 600;
    }

    .notice.error {
        background: #fef2f2;
        border-color: #fecaca;
        color: var(--danger-color);
    }

    .notice.success {
        background: #f0fdf4;
        border-color: #bbf7d0;
        color: var(--success-color);
    }

    .form-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--gray-200);
    }

    .checklist {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        max-height: 14rem;
        overflow-y: auto;
        padding-right: 0.25rem;
    }

    .checklist-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.55rem 0.65rem;
        background: var(--gray-50);
        border-radius: 8px;
    }

    .checklist-item input { margin: 0; }

    .toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .toolbar h3 {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0;
    }

    .info-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        padding: 1rem 1.1rem;
        font-size: 0.85rem;
        color: var(--primary-dark);
        display: flex;
        gap: 0.6rem;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .info-box i { margin-top: 0.1rem; flex-shrink: 0; }

    @media (max-width: 1024px) {
        .form-layout { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .main-content { padding: 1rem; }
        .page-header { flex-direction: column; align-items: flex-start; }
        .form-actions { flex-direction: column; align-items: stretch; }
        .btn { justify-content: center; }
    }
</style>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-money-bill-wave"></i> Fees Management</h1>
                <div class="breadcrumb">Arrange class fees and review recent payments</div>
            </div>
            <div class="page-actions">
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Dashboard
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-label">Classes with Fees</div>
                <div class="stat-card-value"><?php echo (int)$assigned_fees->num_rows; ?></div>
                <div class="stat-card-note">Current fee assignments</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Recent Payments</div>
                <div class="stat-card-value"><?php echo count($recent_payments); ?></div>
                <div class="stat-card-note">Latest recorded transactions</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Available Classes</div>
                <div class="stat-card-value"><?php echo count($classes); ?></div>
                <div class="stat-card-note">Classes ready for fee assignment</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="notice error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="notice success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="form-layout">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-file-invoice-dollar"></i> Create Class Fee</h2>
                </div>
                <div class="card-body">
                    <div class="info-box">
                        <i class="fas fa-circle-info"></i>
                        <span>Create one fee category and apply it to one or more classes for a selected session and term.</span>
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Fee Name *</label>
                            <input type="text" name="fee_name" required placeholder="Tuition Fee" class="form-control">
                        </div>

                        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label class="form-label">Amount *</label>
                                <input type="number" name="amount" required min="1" step="0.01" class="form-control">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Session *</label>
                                <select name="session_id" required class="form-control">
                                    <option value="">Select Session</option>
                                    <?php foreach ($sessions as $session): ?>
                                        <option value="<?php echo $session['id']; ?>">
                                            <?php echo htmlspecialchars($session['session_name']); ?><?php echo $session['is_active'] ? ' (Active)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Term</label>
                            <select name="term_id" class="form-control">
                                <option value="">All / No Term</option>
                                <?php foreach ($terms as $term): ?>
                                    <option value="<?php echo $term['id']; ?>">
                                        <?php echo htmlspecialchars($term['term_name']); ?><?php echo $term['is_active'] ? ' (Active)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control"></textarea>
                        </div>

                        <div class="form-group">
                            <div class="toolbar">
                                <h3>Applies To Classes *</h3>
                                <label style="display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.875rem; color: var(--gray-600);">
                                    <input type="checkbox" id="checkAllClasses" class="rounded">
                                    Check all
                                </label>
                            </div>

                            <div class="checklist">
                                <?php if (!empty($classes)): ?>
                                    <?php foreach ($classes as $class): ?>
                                        <label class="checklist-item">
                                            <input type="checkbox" name="class_ids[]" value="<?php echo $class['id']; ?>" class="class-checkbox rounded">
                                            <span><?php echo htmlspecialchars($class['class_name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state" style="padding: 1rem 0; text-align: left;">
                                        <p style="margin-bottom: 0;">No classes have been created yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                                <i class="fas fa-floppy-disk"></i>
                                Save Fee
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="tab-bar">
                    <button type="button" id="tabClassFeesBtn" class="tab-btn active">
                        Class Fees
                    </button>
                    <button type="button" id="tabPaymentsBtn" class="tab-btn">
                        Recent Payments
                        <?php if (!empty($recent_payments)): ?>
                            <span class="badge-soft" style="margin-left: 0.35rem;"><?php echo count($recent_payments); ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <div id="tabClassFees" class="tab-panel fee-tab-panel">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fee</th>
                                    <th>Class</th>
                                    <th>Session</th>
                                    <th>Term</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($assigned_fees && $assigned_fees->num_rows > 0): ?>
                                    <?php while ($fee = $assigned_fees->fetch_assoc()): ?>
                                        <tr>
                                            <td style="font-weight: 600; color: var(--gray-900);"><?php echo htmlspecialchars($fee['fee_name']); ?></td>
                                            <td><?php echo htmlspecialchars($fee['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($fee['session_name']); ?></td>
                                            <td><?php echo htmlspecialchars($fee['term_name'] ?? '-'); ?></td>
                                            <td><span class="badge badge-primary">₦<?php echo number_format($fee['amount'], 2); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <div class="empty-state-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                                <p>No class fees created yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="tabPayments" class="tab-panel fee-tab-panel hidden">
                    <?php if (empty($recent_payments)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-receipt"></i></div>
                            <p>No payments recorded yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Admission No</th>
                                        <th>Class</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $p): ?>
                                        <tr>
                                            <td style="font-weight: 600; color: var(--gray-900);"><?php echo htmlspecialchars(($p['first_name'] || $p['last_name']) ? trim($p['first_name'].' '.$p['last_name']) : '—'); ?></td>
                                            <td><?php echo htmlspecialchars($p['admission_no'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($p['class_name'] ?? '-'); ?></td>
                                            <td><span class="badge badge-primary">₦<?php echo number_format($p['amount_paid'], 2); ?></span></td>
                                            <td><?php echo htmlspecialchars($p['payment_method'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($p['reference'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($p['created_at'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

    <script>
        // Terms data organized by session
        const termsData = <?php echo json_encode(array_reduce($terms, function($acc, $term) {
            $session_id = $term['session_id'];
            if (!isset($acc[$session_id])) {
                $acc[$session_id] = [];
            }
            $acc[$session_id][] = $term;
            return $acc;
        }, [])); ?>;

        const sessionSelect = document.querySelector('select[name="session_id"]');
        const termSelect = document.querySelector('select[name="term_id"]');
        const checkAllClasses = document.getElementById('checkAllClasses');
        const classCheckboxes = Array.from(document.querySelectorAll('.class-checkbox'));

        // Function to update terms dropdown based on selected session
        function updateTermsDropdown() {
            const selectedSessionId = sessionSelect.value;
            const currentTermId = termSelect.value;
            
            // Remove all term options except the first one
            const firstOption = termSelect.querySelector('option:first-child');
            termSelect.innerHTML = '';
            termSelect.appendChild(firstOption);
            
            // Add terms for the selected session
            if (selectedSessionId && termsData[selectedSessionId]) {
                termsData[selectedSessionId].forEach(term => {
                    const option = document.createElement('option');
                    option.value = term.id;
                    option.textContent = term.term_name + (term.is_active ? ' (Active)' : '');
                    termSelect.appendChild(option);
                });
                
                // Restore previous selection if it exists
                if (currentTermId && termSelect.querySelector(`option[value="${currentTermId}"]`)) {
                    termSelect.value = currentTermId;
                }
            }
        }

        // Listen for session selection changes
        sessionSelect.addEventListener('change', updateTermsDropdown);

        // Initialize terms on page load
        document.addEventListener('DOMContentLoaded', () => {
            if (sessionSelect.value) {
                updateTermsDropdown();
            }
        });

        // Check/uncheck all classes functionality
        checkAllClasses.addEventListener('change', () => {
            classCheckboxes.forEach((checkbox) => {
                checkbox.checked = checkAllClasses.checked;
            });
        });

        classCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                checkAllClasses.checked = classCheckboxes.length > 0 && classCheckboxes.every((item) => item.checked);
            });
        });

        // Tab switching: Class Fees <-> Recent Payments
        const tabClassFeesBtn = document.getElementById('tabClassFeesBtn');
        const tabPaymentsBtn = document.getElementById('tabPaymentsBtn');
        const tabClassFees = document.getElementById('tabClassFees');
        const tabPayments = document.getElementById('tabPayments');

        function showTab(tab) {
            const isFees = tab === 'fees';

            tabClassFees.classList.toggle('hidden', !isFees);
            tabPayments.classList.toggle('hidden', isFees);

            tabClassFeesBtn.classList.toggle('active', isFees);
            tabPaymentsBtn.classList.toggle('active', !isFees);
        }

        tabClassFeesBtn?.addEventListener('click', () => showTab('fees'));
        tabPaymentsBtn?.addEventListener('click', () => showTab('payments'));
    </script>
</body>
</html>

<?php include_once '../../includes/footer.php'; ?>