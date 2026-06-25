<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_STUDENT) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

$stmt = $conn->prepare("SELECT s.id, s.first_name, s.last_name, s.admission_no, s.phone, c.class_name
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student ? (int)$student['id'] : 0;

$fee_count = 0;
$unpaid_amount = 0;
$result_count = 0;

if ($student_id) {
    $active_session = $conn->query("SELECT id FROM sessions WHERE is_active = 1 LIMIT 1")->fetch_assoc();
    if ($active_session) {
        $sid = (int)$active_session['id'];
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total FROM student_fees WHERE student_id = ? AND session_id = ? AND is_paid = 0");
        $stmt->bind_param("ii", $student_id, $sid);
        $stmt->execute();
        $fees = $stmt->get_result()->fetch_assoc();
        $fee_count = (int)$fees['cnt'];
        $unpaid_amount = (float)$fees['total'];

        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM student_results WHERE student_id = ? AND session_id = ? AND is_published = 1");
        $stmt->bind_param("ii", $student_id, $sid);
        $stmt->execute();
        $result_count = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    }
}
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-graduation-cap"></i> Student Dashboard</h1>
                <span class="breadcrumb">Welcome, <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] ?? $current_user['email']); ?></span>
            </div>
            <div class="page-actions">
                <a href="results.php" class="btn btn-primary"><i class="fas fa-chart-bar"></i> My Results</a>
            </div>
        </div>

        <?php if (!$student): ?>
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Student profile not found. Please contact the administrator.
                    </div>
                </div>
            </div>
        <?php else: ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Admission No</div>
                        <div class="stat-card-value" style="font-size:1.5rem;"><?php echo htmlspecialchars($student['admission_no']); ?></div>
                        <div class="stat-card-change positive"><i class="fas fa-circle-check"></i> Active</div>
                    </div>
                    <div class="stat-card-icon primary"><i class="fas fa-id-card"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Published Results</div>
                        <div class="stat-card-value"><?php echo $result_count; ?></div>
                        <div class="stat-card-change positive"><i class="fas fa-circle-check"></i> Current session</div>
                    </div>
                    <div class="stat-card-icon success"><i class="fas fa-chart-bar"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Unpaid Fees</div>
                        <div class="stat-card-value"><?php echo $fee_count; ?> items</div>
                        <div class="stat-card-change negative">₦<?php echo number_format($unpaid_amount, 2); ?> outstanding</div>
                    </div>
                    <div class="stat-card-icon warning"><i class="fas fa-money-bill"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Class</div>
                        <div class="stat-card-value" style="font-size:1.5rem;"><?php echo htmlspecialchars($student['class_name'] ?? '—'); ?></div>
                        <div class="stat-card-change positive"><i class="fas fa-school"></i> Enrolled</div>
                    </div>
                    <div class="stat-card-icon info"><i class="fas fa-school"></i></div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-user"></i> My Profile</h2>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div>
                            <div style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Full Name</div>
                            <div style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Phone</div>
                            <div style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($student['phone'] ?? '—'); ?></div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Class</div>
                            <div style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($student['class_name'] ?? '—'); ?></div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Admission No</div>
                            <div style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($student['admission_no']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-lightning-bolt"></i> Quick Actions</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="results.php" class="btn btn-primary"><i class="fas fa-chart-bar"></i> View My Results</a>
                        <a href="fees.php" class="btn btn-secondary"><i class="fas fa-receipt"></i> View My Fees</a>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
