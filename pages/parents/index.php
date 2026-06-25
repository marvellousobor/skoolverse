<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $new_password = trim($_POST['new_password'] ?? '');

    if ($target_user_id <= 0) {
        $error = "Invalid parent reference.";
    } elseif ($new_password == '') {
        $error = "Password cannot be empty.";
    } elseif (strlen($new_password) > 10) {
        $error = "Password must be 10 characters or fewer.";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, plain_password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_hash, $new_password, $target_user_id);
        if ($stmt->execute()) {
            $success = "Password updated successfully.";
        } else {
            $error = "Error updating password: " . $stmt->error;
        }
        $stmt->close();
    }
}

$sql = "SELECT parents.id, parents.full_name, parents.user_id, users.email, users.plain_password,
            COALESCE(child_counts.child_count, 0) AS child_count
        FROM parents
        INNER JOIN users ON parents.user_id = users.id
        LEFT JOIN (
            SELECT parent_id, COUNT(DISTINCT student_id) AS child_count
            FROM (
                SELECT parent_id, id AS student_id FROM students WHERE parent_id IS NOT NULL
                UNION
                SELECT parent_id, student_id FROM student_parent_links
            ) AS linked_students
            GROUP BY parent_id
        ) AS child_counts ON child_counts.parent_id = parents.id
        ORDER BY parents.full_name";
$parents = $conn->query($sql);
$parent_rows = $parents ? $parents->fetch_all(MYSQLI_ASSOC) : [];
$total_parents = count($parent_rows);
$total_children = 0;
foreach ($parent_rows as $p) { $total_children += (int)$p['child_count']; }

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-people-roof"></i> Parents</h1>
                <span class="breadcrumb">Manage parent accounts</span>
            </div>
            <div class="page-actions">
                <a href="add.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Parent</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="card" style="border-left:4px solid var(--danger-color);margin-bottom:1.5rem;">
                <div class="card-body" style="display:flex;align-items:center;gap:0.75rem;color:var(--danger-color);">
                    <i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="card" style="border-left:4px solid var(--success-color);margin-bottom:1.5rem;">
                <div class="card-body" style="display:flex;align-items:center;gap:0.75rem;color:var(--success-color);">
                    <i class="fas fa-circle-check"></i><?php echo htmlspecialchars($success); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Total Parents</div>
                        <div class="stat-card-value"><?php echo $total_parents; ?></div>
                    </div>
                    <div class="stat-card-icon primary"><i class="fas fa-people-roof"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Linked Children</div>
                        <div class="stat-card-value"><?php echo $total_children; ?></div>
                    </div>
                    <div class="stat-card-icon success"><i class="fas fa-graduation-cap"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Parent Accounts</h2>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (!empty($parent_rows)): ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Children</th>
                                <th>Password</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parent_rows as $parent): ?>
                                <tr>
                                    <td style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($parent['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($parent['email']); ?></td>
                                    <td><span class="badge badge-primary"><?php echo (int)$parent['child_count']; ?></span></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:0.5rem;" data-row="<?php echo (int)$parent['user_id']; ?>">
                                            <span class="view-mode" style="display:flex;align-items:center;gap:0.5rem;">
                                                <span style="font-family:monospace;" class="password-text">••••••</span>
                                                <button type="button" class="btn btn-secondary btn-sm toggle-visibility" data-password="<?php echo htmlspecialchars($parent['plain_password'] ?? ''); ?>">
                                                    <i class="fas fa-eye"></i> Show
                                                </button>
                                                <button type="button" class="btn btn-outline btn-sm edit-btn"><i class="fas fa-pen"></i> Edit</button>
                                            </span>
                                            <form method="POST" class="edit-form" style="display:none;align-items:center;gap:0.5rem;">
                                                <input type="hidden" name="update_password" value="1">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$parent['user_id']; ?>">
                                                <input type="text" name="new_password" maxlength="10"
                                                       value="<?php echo htmlspecialchars($parent['plain_password'] ?? ''); ?>"
                                                       style="padding:0.4rem 0.6rem;border:1px solid var(--gray-300);border-radius:6px;font-family:monospace;width:8rem;">
                                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Save</button>
                                                <button type="button" class="btn btn-secondary btn-sm cancel-btn"><i class="fas fa-xmark"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-user-slash"></i></div>
                        <p>No parents found.</p>
                        <a href="add.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add First Parent</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
document.querySelectorAll('[data-row]').forEach(function (row) {
    const viewMode = row.querySelector('.view-mode');
    const editForm = row.querySelector('.edit-form');
    const toggleBtn = row.querySelector('.toggle-visibility');
    const passwordText = row.querySelector('.password-text');
    const editBtn = row.querySelector('.edit-btn');
    const cancelBtn = row.querySelector('.cancel-btn');
    let visible = false;

    toggleBtn.addEventListener('click', function () {
        visible = !visible;
        passwordText.textContent = visible ? (toggleBtn.dataset.password || '(none)') : '••••••';
        toggleBtn.innerHTML = visible ? '<i class="fas fa-eye-slash"></i> Hide' : '<i class="fas fa-eye"></i> Show';
    });
    editBtn.addEventListener('click', function () {
        viewMode.style.display = 'none';
        editForm.style.display = 'flex';
    });
    cancelBtn.addEventListener('click', function () {
        editForm.style.display = 'none';
        viewMode.style.display = 'flex';
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>
