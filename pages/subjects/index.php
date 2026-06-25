<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
include_once '../../includes/results_schema.php';
ensure_results_schema($conn);

$error = '';
$success = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $check_results = $conn->query("SELECT COUNT(*) as cnt FROM student_results WHERE subject_id = $id")->fetch_assoc();
    $check_assignments = $conn->query("SELECT COUNT(*) as cnt FROM teacher_assignments WHERE subject_id = $id")->fetch_assoc();
    if ($check_results['cnt'] > 0) {
        $error = "Cannot delete subject with existing results. Deactivate it instead.";
    } elseif ($check_assignments['cnt'] > 0) {
        $error = "Cannot delete subject with teacher assignments. Remove assignments first.";
    } else {
        $conn->query("DELETE FROM class_subjects WHERE subject_id = $id");
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Subject deleted successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $subj = $conn->query("SELECT is_active FROM subjects WHERE id = $id")->fetch_assoc();
    if ($subj) {
        $new = $subj['is_active'] ? 0 : 1;
        $conn->query("UPDATE subjects SET is_active = $new WHERE id = $id");
        $success = "Subject " . ($new ? 'activated' : 'deactivated') . "!";
    }
}

$subjects = $conn->query("
    SELECT s.*, 
    (SELECT COUNT(*) FROM student_results WHERE subject_id = s.id) as result_count,
    (SELECT GROUP_CONCAT(c.class_name SEPARATOR ', ') FROM class_subjects cs JOIN classes c ON c.id = cs.class_id WHERE cs.subject_id = s.id) as offered_in
    FROM subjects s 
    ORDER BY s.subject_name
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-book-open"></i> Subjects</h1>
                <span class="breadcrumb">Manage school subjects and class assignments</span>
            </div>
            <div class="page-actions">
                <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Subject</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Subjects</h2>
                <span style="font-size:0.8rem;color:var(--gray-500);"><?php echo count($subjects); ?> subject(s)</span>
            </div>
            <?php if (empty($subjects)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-book-open"></i></div>
                    <p>No subjects found. Create your first subject.</p>
                    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create Subject</a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Subject Name</th>
                                <th>Code</th>
                                <th>Offered In</th>
                                <th>Status</th>
                                <th>Results</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $s): ?>
                            <tr>
                                <td style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($s['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['subject_code'] ?: '—'); ?></td>
                                <td style="font-size:0.8rem;">
                                    <?php if ($s['offered_in']): ?>
                                        <span class="class-pills">
                                            <?php foreach (explode(', ', $s['offered_in']) as $cls): ?>
                                                <span class="class-pill"><?php echo htmlspecialchars($cls); ?></span>
                                            <?php endforeach; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--gray-400);">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $s['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><span class="badge badge-primary"><?php echo $s['result_count']; ?></span></td>
                                <td>
                                    <div class="action-group">
                                        <a href="edit.php?id=<?php echo $s['id']; ?>" class="btn btn-ghost btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?toggle=<?php echo $s['id']; ?>" class="btn btn-sm <?php echo $s['is_active'] ? 'btn-secondary' : 'btn-primary'; ?>">
                                            <i class="fas fa-<?php echo $s['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            <?php echo $s['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                        <a href="?delete=<?php echo $s['id']; ?>" onclick="return confirm('Delete this subject permanently?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
