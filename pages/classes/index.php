<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
?>

<?php
// Get all classes
$classes = $conn->query("SELECT * FROM classes ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $class_id = (int)$_GET['delete'];
    
    // Check if class has associated records
    $check_students = $conn->query("SELECT COUNT(*) as count FROM students WHERE class_id = $class_id")->fetch_assoc();
    $check_subjects = $conn->query("SELECT COUNT(*) as count FROM class_subjects WHERE class_id = $class_id")->fetch_assoc();
    $check_results = $conn->query("SELECT COUNT(*) as count FROM student_results WHERE class_id = $class_id")->fetch_assoc();
    $check_assignments = $conn->query("SELECT COUNT(*) as count FROM teacher_assignments WHERE class_id = $class_id")->fetch_assoc();
    
    if ($check_students['count'] > 0) {
        $error = "Cannot delete class with associated students. Remove students first.";
    } elseif ($check_subjects['count'] > 0) {
        $error = "Cannot delete class with assigned subjects. Remove subject assignments first.";
    } elseif ($check_results['count'] > 0) {
        $error = "Cannot delete class with existing results. Remove results first.";
    } elseif ($check_assignments['count'] > 0) {
        $error = "Cannot delete class with teacher assignments. Remove assignments first.";
    } else {
        $delete_sql = "DELETE FROM classes WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $class_id);
        
        if ($stmt->execute()) {
            $success = "Class deleted successfully!";
            // Refresh classes list
            $classes = $conn->query("SELECT * FROM classes ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "Error deleting class: " . $conn->error;
        }
    }
}
?>

<div class="main-wrapper">
    <main class="main-content">

        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-school"></i> Classes</h1>
                <span class="breadcrumb">Manage school classes and levels</span>
            </div>
            <div class="page-actions">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Class
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-circle-check"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Classes</h2>
            </div>

            <?php if (empty($classes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-school-circle-exclamation"></i></div>
                    <p>No classes found. Create your first class to get started.</p>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Class
                    </a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Level</th>
                                <th>Students</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <?php
                                $student_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE class_id = " . $class['id']);
                                $count_result = $student_count->fetch_assoc();
                                $student_count = $count_result['count'];
                                ?>
                                <tr>
                                    <td style="font-weight:600;color:var(--gray-900);">
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($class['level']); ?></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo $student_count; ?> student<?php echo $student_count != 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($class['created_at'])); ?></td>
                                    <td>
                                        <div class="action-group">
                                            <a href="edit.php?id=<?php echo $class['id']; ?>" 
                                               class="btn btn-ghost btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="?delete=<?php echo $class['id']; ?>" 
                                               onclick="return confirm('Are you sure you want to delete this class?');"
                                               class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
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
