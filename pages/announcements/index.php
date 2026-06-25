<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN && $user_role != 'teacher') {
    header('Location: ../dashboard.php');
    exit();
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

$error = '';
$success = '';
$is_admin = ($user_role == ROLE_ADMIN);
$is_teacher = ($user_role == 'teacher');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) $success = 'Announcement deleted.';
    else $error = 'Failed to delete.';
}

// Handle publish toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $tog_id = (int)$_GET['toggle'];
    $r = $conn->query("SELECT is_published FROM announcements WHERE id = $tog_id")->fetch_assoc();
    if ($r) {
        $new_val = $r['is_published'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE announcements SET is_published = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_val, $tog_id);
        $stmt->execute();
        $success = $new_val ? 'Announcement published.' : 'Announcement unpublished.';
    }
}

// Fetch announcements
if ($is_admin) {
    $announcements = $conn->query("SELECT a.*, u.username, c.class_name as target_class FROM announcements a LEFT JOIN users u ON u.id = a.posted_by_user_id LEFT JOIN classes c ON c.id = a.target_class_id ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);
} else {
    // Teachers see: their own posts + all broadcast announcements
    $announcements = $conn->query("SELECT a.*, u.username, c.class_name as target_class FROM announcements a LEFT JOIN users u ON u.id = a.posted_by_user_id LEFT JOIN classes c ON c.id = a.target_class_id WHERE a.target_class_id IS NULL OR a.posted_by_user_id = $user_id ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);
}
?>
<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-bullhorn"></i> Announcements</h1>
                <div class="breadcrumb">Manage school announcements and notices</div>
            </div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Announcement
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <?php if (count($announcements) > 0): ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Posted By</th>
                                <th>Audience</th>
                                <th>Date</th>
                                <th>Status</th>
                                <?php if ($is_admin): ?>
                                    <th style="text-align:center;">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $a): ?>
                                <tr>
                                    <td style="font-weight:600;color:var(--gray-900);">
                                        <?php echo htmlspecialchars($a['title']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($a['posted_by_name']); ?>
                                        <span style="font-size:0.75rem;color:var(--gray-400);">
                                            (<?php echo htmlspecialchars(ucfirst($a['posted_by_role'])); ?>)
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($a['target_class']): ?>
                                            <span class="badge badge-info">
                                                <i class="fas fa-users"></i> <?php echo htmlspecialchars($a['target_class']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success" style="background:#e0f2fe;color:#0369a1;">
                                                <i class="fas fa-globe"></i> Everyone
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($a['created_at'])); ?></td>
                                    <td>
                                        <?php if ($a['is_published']): ?>
                                            <span class="badge badge-success"><span class="badge-dot"></span> Published</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><span class="badge-dot"></span> Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($is_admin): ?>
                                        <td>
                                            <div class="action-group" style="justify-content:center;">
                                                <a href="edit.php?id=<?php echo $a['id']; ?>" class="btn btn-ghost btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?toggle=<?php echo $a['id']; ?>" class="btn btn-ghost btn-sm" title="<?php echo $a['is_published'] ? 'Unpublish' : 'Publish'; ?>">
                                                    <i class="fas <?php echo $a['is_published'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                </a>
                                                <a href="?delete=<?php echo $a['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this announcement?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    <?php elseif ($a['posted_by_user_id'] == $user_id): ?>
                                        <td>
                                            <div class="action-group" style="justify-content:center;">
                                                <a href="edit.php?id=<?php echo $a['id']; ?>" class="btn btn-ghost btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php if (!empty($a['content'])): ?>
                                    <tr style="background:var(--gray-50);">
                                        <td colspan="<?php echo $is_admin ? 6 : 5; ?>" style="padding:0.5rem 1rem 0.75rem 1rem;color:var(--gray-600);font-size:0.85rem;line-height:1.5;">
                                            <?php echo nl2br(htmlspecialchars($a['content'])); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <p>No announcements yet.</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create the first announcement
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include_once '../../includes/footer.php'; ?>
