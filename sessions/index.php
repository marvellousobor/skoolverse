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
// Get all sessions
$sessions = $conn->query("SELECT * FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $session_id = (int)$_GET['delete'];
    
    // Check if session has associated records
    $check_students = $conn->query("SELECT COUNT(*) as count FROM students WHERE session_id = $session_id")->fetch_assoc();
    $check_fees = $conn->query("SELECT COUNT(*) as count FROM student_fees WHERE session_id = $session_id")->fetch_assoc();
    $check_results = $conn->query("SELECT COUNT(*) as count FROM student_results WHERE session_id = $session_id")->fetch_assoc();
    $check_assignments = $conn->query("SELECT COUNT(*) as count FROM teacher_assignments WHERE session_id = $session_id")->fetch_assoc();
    
    if ($check_students['count'] > 0) {
        $error = "Cannot delete session with associated students. Remove students first.";
    } elseif ($check_fees['count'] > 0) {
        $error = "Cannot delete session with associated fees. Remove fee records first.";
    } elseif ($check_results['count'] > 0) {
        $error = "Cannot delete session with existing results. Remove results first.";
    } elseif ($check_assignments['count'] > 0) {
        $error = "Cannot delete session with teacher assignments. Remove assignments first.";
    } else {
        $delete_sql = "DELETE FROM sessions WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $session_id);
        
        if ($stmt->execute()) {
            $success = "Session deleted successfully!";
            // Refresh sessions list
            $sessions = $conn->query("SELECT * FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "Error deleting session: " . $conn->error;
        }
    }
}

// Handle set as active
if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $session_id = (int)$_GET['activate'];
    
    // First deactivate all sessions
    $conn->query("UPDATE sessions SET is_active = 0");
    
    // Then activate the selected one
    $activate_sql = "UPDATE sessions SET is_active = 1 WHERE id = ?";
    $stmt = $conn->prepare($activate_sql);
    $stmt->bind_param("i", $session_id);
    
    if ($stmt->execute()) {
        $success = "Session activated successfully!";
        // Refresh sessions list
        $sessions = $conn->query("SELECT * FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "Error activating session: " . $conn->error;
    }
}
?>

<style>
    /* ═══════════════════ SESSIONS STYLES ═══════════════════ */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .page-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .page-title h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
    }
    
    .page-title .breadcrumb {
        font-size: 0.875rem;
        color: var(--gray-500);
    }
    
    .page-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    
    /* ═══════════════════ TABLES ═══════════════════ */
    .table-wrapper {
        overflow-x: auto;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    
    .table thead {
        background: var(--gray-50);
    }
    
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
    }
    
    .table tbody tr:hover {
        background: var(--gray-50);
    }
    
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* ═══════════════════ BADGES ═══════════════════ */
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
    
    .badge-primary {
        background: var(--primary-light);
        color: var(--white);
    }
    
    .badge-success {
        background: #d1fae5;
        color: var(--success-color);
    }
    
    .badge-warning {
        background: #fef3c7;
        color: #b45309;
    }
    
    .badge-danger {
        background: #fee2e2;
        color: var(--danger-color);
    }
    
    /* ═══════════════════ BUTTONS ═══════════════════ */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: 'Segoe UI', sans-serif;
    }
    
    .btn-primary {
        background: var(--primary-color);
        color: var(--white);
    }
    
    .btn-primary:hover {
        background: var(--primary-dark);
        box-shadow: 0 2px 8px rgba(30, 64, 175, 0.3);
    }
    
    .btn-secondary {
        background: var(--gray-200);
        color: var(--gray-900);
    }
    
    .btn-secondary:hover {
        background: var(--gray-300);
    }
    
    .btn-outline {
        border: 2px solid var(--primary-color);
        background: transparent;
        color: var(--primary-color);
    }
    
    .btn-outline:hover {
        background: var(--primary-color);
        color: var(--white);
    }
    
    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }
    
    /* ═══════════════════ EMPTY STATE ═══════════════════ */
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
    
    /* ═══════════════════ RESPONSIVE ═══════════════════ */
    @media (max-width: 768px) {
        .main-content {
            padding: 1rem;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .page-title h1 {
            font-size: 1.5rem;
        }
    }
</style>

<!-- Main Content Wrapper -->
<div class="main-wrapper">
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-calendar-alt"></i> Academic Sessions</h1>
                <span class="breadcrumb">Manage academic sessions</span>
            </div>
            <div class="page-actions">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Create New Session
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($sessions)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <p>No academic sessions found.</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Create New Session
                </a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-wrapper">
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
                                        <td class="font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($session['session_name']); ?>
                                        </td>
                                        <td class="text-gray-700">
                                            <?php echo date('M d, Y', strtotime($session['start_date'])); ?>
                                        </td>
                                        <td class="text-gray-700">
                                            <?php echo date('M d, Y', strtotime($session['end_date'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($session['is_active']): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle"></i>
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-circle"></i>
                                                    Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-gray-700 text-sm">
                                            <?php echo date('M d, Y', strtotime($session['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="flex gap-2">
                                                <?php if (!$session['is_active']): ?>
                                                    <a href="?activate=<?php echo $session['id']; ?>" 
                                                       class="btn btn-sm btn-outline">
                                                        <i class="fas fa-check"></i>
                                                        Activate
                                                    </a>
                                                <?php endif; ?>
                                                <a href="edit.php?id=<?php echo $session['id']; ?>" 
                                                   class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-edit"></i>
                                                    Edit
                                                </a>
                                                <a href="?delete=<?php echo $session['id']; ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this session?');" 
                                                   class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Include Footer -->
<?php include_once '../../includes/footer.php'; ?>