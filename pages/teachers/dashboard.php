<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_TEACHER) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

$stmt = $conn->prepare("SELECT id, full_name, staff_id, department FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher ? (int)$teacher['id'] : 0;

$assigned_classes = [];
$student_count = 0;

if ($teacher_id) {
    $stmt = $conn->prepare("
        SELECT c.id, c.class_name, c.level,
               (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) AS students_in_class
        FROM teacher_assignments ta
        INNER JOIN classes c ON ta.class_id = c.id
        WHERE ta.teacher_id = ?
        GROUP BY c.id
        ORDER BY c.class_name
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $assigned_classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $student_count = array_sum(array_column($assigned_classes, 'students_in_class'));
}
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-chalkboard-user"></i> Teacher Dashboard</h1>
                <span class="breadcrumb">Welcome, <?php echo htmlspecialchars($teacher['full_name'] ?? $current_user['email']); ?></span>
            </div>
            <div class="page-actions">
                <a href="<?php echo BASE_URL; ?>pages/results/upload.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload CSV</a>
                <a href="<?php echo BASE_URL; ?>pages/results/entry.php" class="btn btn-secondary"><i class="fas fa-pen-to-square"></i> Enter Scores</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Assigned Classes</div>
                        <div class="stat-card-value"><?php echo count($assigned_classes); ?></div>
                        <div class="stat-card-change positive"><i class="fas fa-circle-check"></i> Active</div>
                    </div>
                    <div class="stat-card-icon primary"><i class="fas fa-school"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Total Students</div>
                        <div class="stat-card-value"><?php echo $student_count; ?></div>
                        <div class="stat-card-change positive"><i class="fas fa-arrow-up"></i> Across all classes</div>
                    </div>
                    <div class="stat-card-icon success"><i class="fas fa-graduation-cap"></i></div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-school"></i> My Classes</h2>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (!empty($assigned_classes)): ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Level</th>
                                        <th>Students</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assigned_classes as $cls): ?>
                                        <tr>
                                            <td style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($cls['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($cls['level'] ?? '—'); ?></td>
                                            <td><span class="badge badge-primary"><?php echo (int)$cls['students_in_class']; ?></span></td>
                                            <td style="text-align:right;">
                                                <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                                                    <a href="<?php echo BASE_URL; ?>pages/results/entry.php?class=<?php echo $cls['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-pen"></i> Enter Scores</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-school-circle-exclamation"></i></div>
                            <p>No classes assigned yet.</p>
                            <p style="font-size:0.85rem;color:var(--gray-500);">Contact the administrator to get class assignments.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-lightning-bolt"></i> Quick Actions</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="<?php echo BASE_URL; ?>pages/results/entry.php" class="btn btn-primary"><i class="fas fa-pen-to-square"></i> Enter Scores</a>
                        <a href="<?php echo BASE_URL; ?>pages/results/upload.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload CSV</a>
                        <a href="<?php echo BASE_URL; ?>pages/results/broadsheet.php" class="btn btn-secondary"><i class="fas fa-table-cells-large"></i> Broadsheet</a>
                        <a href="<?php echo BASE_URL; ?>pages/teacher_assignments/" class="btn btn-secondary"><i class="fas fa-diagram-project"></i> View Assignments</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
