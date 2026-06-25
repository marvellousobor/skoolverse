<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN) {
    header('Location: ../dashboard.php');
    exit;
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
?>

<?php
$sessions = $conn->query("SELECT * FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$error = '';
$success = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $session_id = (int)$_GET['delete'];
    $check_students = $conn->query("SELECT COUNT(*) as count FROM students WHERE session_id = $session_id")->fetch_assoc();
    $check_fees = $conn->query("SELECT COUNT(*) as count FROM student_fees WHERE session_id = $session_id")->fetch_assoc();
    $check_results = $conn->query("SELECT COUNT(*) as count FROM student_results WHERE session_id = $session_id")->fetch_assoc();
    $check_assignments = $conn->query("SELECT COUNT(*) as count FROM teacher_assignments WHERE session_id = $session_id")->fetch_assoc();

    if ($check_students['count'] > 0) {
        $error = 'Cannot delete session with associated students. Remove students first.';
    } elseif ($check_fees['count'] > 0) {
        $error = 'Cannot delete session with associated fees. Remove fee records first.';
    } elseif ($check_results['count'] > 0) {
        $error = 'Cannot delete session with existing results. Remove results first.';
    } elseif ($check_assignments['count'] > 0) {
        $error = 'Cannot delete session with teacher assignments. Remove assignments first.';
    } else {
        $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param('i', $session_id);
        if ($stmt->execute()) {
            $success = 'Session deleted successfully!';
            $sessions = $conn->query("SELECT * FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = 'Error deleting session: ' . $conn->error;
        }
    }
}

if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $session_id = (int)$_GET['activate'];
    $conn->query("UPDATE sessions SET is_active = 0");
    $stmt = $conn->prepare("UPDATE sessions SET is_active = 1 WHERE id = ?");
    $stmt->bind_param('i', $session_id);
    if ($stmt->execute()) {
        $success = 'Session activated successfully!';
        $sessions = $conn->query("SELECT * FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = 'Error activating session: ' . $conn->error;
    }
}

$activeCount = 0;
foreach ($sessions as $session) {
    if (!empty($session['is_active'])) {
        $activeCount++;
    }
}
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-calendar-days"></i> Academic Sessions</h1>
                <div class="breadcrumb">Create and manage the school years used across fees, classes, and results.</div>
            </div>
            <div class="page-actions">
                <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create New Session</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="card" style="margin-bottom:1.25rem;border-left:4px solid #dc2626;">
                <div class="card-body" style="padding:1rem 1.25rem;color:#b91c1c;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="card" style="margin-bottom:1.25rem;border-left:4px solid #059669;">
                <div class="card-body" style="padding:1rem 1.25rem;color:#047857;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="stats-grid" style="margin-bottom:1.5rem;">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Total Sessions</div>
                        <div class="stat-card-value"><?php echo count($sessions); ?></div>
                    </div>
                    <div class="stat-card-icon primary"><i class="fas fa-layer-group"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Active Sessions</div>
                        <div class="stat-card-value"><?php echo $activeCount; ?></div>
                    </div>
                    <div class="stat-card-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>

        <?php if (empty($sessions)): ?>
            <div class="card">
                <div class="empty-state">
                    <i class="fas fa-calendar-plus"></i>
                    <p>No academic sessions found.</p>
                    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create one now</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrapper card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Session Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($session['session_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($session['start_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($session['end_date'])); ?></td>
                                <td>
                                    <?php if ($session['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($session['created_at'])); ?></td>
                                <td>
                                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                        <?php if (!$session['is_active']): ?>
                                            <a href="?activate=<?php echo $session['id']; ?>" class="btn btn-success"><i class="fas fa-bolt"></i> Activate</a>
                                        <?php endif; ?>
                                        <a href="edit.php?id=<?php echo $session['id']; ?>" class="btn btn-secondary"><i class="fas fa-pen"></i> Edit</a>
                                        <a href="?delete=<?php echo $session['id']; ?>" onclick="return confirm('Are you sure you want to delete this session?');" class="btn btn-primary" style="background:#dc2626;"><i class="fas fa-trash"></i> Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>