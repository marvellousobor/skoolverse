<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN || !$is_super_admin) {
    header('Location: ../dashboard.php');
    exit();
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

$error = '';
$success = '';

// Handle delete admin
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Prevent self-deletion
    if ($del_id == $user_id) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = 'Admin deleted.';
        } else {
            $error = 'Admin not found.';
        }
    }
}

// Handle toggle active/suspended
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $tog_id = (int)$_GET['toggle'];
    if ($tog_id == $user_id) {
        $error = 'You cannot suspend your own account.';
    } else {
        $r = $conn->query("SELECT status FROM users WHERE id = $tog_id AND role = 'admin'")->fetch_assoc();
        if ($r) {
            $new_status = $r['status'] === 'active' ? 'suspended' : 'active';
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $tog_id);
            $stmt->execute();
            $success = "Admin " . ($new_status === 'active' ? 'activated.' : 'suspended.');
        }
    }
}

$admins = $conn->query("SELECT id, username, email, status, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-user-shield"></i> Manage Admins</h1>
                <div class="breadcrumb"><a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></div>
            </div>
            <a href="create_admin.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Admin
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $a): ?>
                            <tr>
                                <td style="font-weight:600;color:var(--gray-900);">
                                    <i class="fas fa-user-shield" style="color:var(--primary-color);margin-right:0.5rem;"></i>
                                    <?php echo htmlspecialchars($a['username']); ?>
                                    <?php if ($a['id'] == $user_id): ?>
                                        <span class="badge badge-info" style="font-size:0.7rem;">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($a['email']); ?></td>
                                <td><?php echo date('d M Y', strtotime($a['created_at'])); ?></td>
                                <td>
                                    <?php if ($a['status'] === 'active'): ?>
                                        <span class="badge badge-success"><span class="badge-dot"></span> Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><span class="badge-dot"></span> Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-group" style="justify-content:center;">
                                        <a href="edit_admin.php?id=<?php echo $a['id']; ?>" class="btn btn-ghost btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($a['id'] != $user_id): ?>
                                            <a href="?toggle=<?php echo $a['id']; ?>" class="btn btn-ghost btn-sm" title="<?php echo $a['status'] === 'active' ? 'Suspend' : 'Activate'; ?>">
                                                <i class="fas <?php echo $a['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                            </a>
                                            <a href="?delete=<?php echo $a['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this admin? This cannot be undone.');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include_once '../../includes/footer.php'; ?>
