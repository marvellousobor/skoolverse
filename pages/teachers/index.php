<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

$error = '';
$success = '';

// Handle password update for teacher user accounts
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_password = trim($_POST['new_password'] ?? '');

    if ($user_id <= 0) {
        $error = "Invalid teacher user reference.";
    } elseif ($new_password == '') {
        $error = "Password cannot be empty.";
    } elseif (strlen($new_password) > 50) {
        $error = "Password must be 50 characters or fewer.";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, plain_password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_hash, $new_password, $user_id);

        if ($stmt->execute()) {
            $success = "Password updated successfully.";
        } else {
            $error = "Error updating password: " . $stmt->error;
        }
        $stmt->close();
    }
}

$sql = "SELECT t.id, t.user_id, t.staff_id, t.full_name, t.department, t.phone, t.created_at, u.email, u.plain_password,
    GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name SEPARATOR ', ') AS assigned_classes
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN teacher_assignments ta ON ta.teacher_id = t.id
    LEFT JOIN classes c ON c.id = ta.class_id
    GROUP BY t.id
    ORDER BY t.created_at DESC";
$teachers = $conn->query($sql);
?>

<style>
    /* ═══════════════════ TEACHERS PAGE SPECIFIC STYLES ═══════════════════ */
    .btn-ghost {
        background: transparent;
        color: var(--primary-color);
        border: none;
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: underline;
        font-family: 'Segoe UI', sans-serif;
    }

    .btn-ghost:hover { color: var(--primary-dark); }

    .btn-ghost-danger {
        background: transparent;
        color: var(--gray-500);
        border: none;
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: underline;
        font-family: 'Segoe UI', sans-serif;
    }

    .btn-ghost-success {
        background: transparent;
        color: var(--success-color);
        border: none;
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: underline;
        font-family: 'Segoe UI', sans-serif;
    }

    .actions-cell {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .action-link {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.85rem;
        border-radius: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .action-link-primary {
        background: #eff6ff;
        color: var(--primary-color);
    }

    .action-link-primary:hover {
        background: var(--primary-color);
        color: var(--white);
    }
</style>

<div class="main-wrapper">
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-chalkboard-user"></i> Teachers</h1>
                <div class="breadcrumb">Manage teaching staff and their assignments</div>
            </div>
            <div class="page-actions">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> New Teacher
                </a>
            </div>
        </div>

        <!-- Alerts -->
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

        <!-- Teachers Table Card -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Teachers</h2>
            </div>

            <?php if (!$teachers || $teachers->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-chalkboard-user"></i></div>
                    <p>No teachers found. Add your first teacher to get started.</p>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add Teacher
                    </a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user" style="margin-right:0.4rem;opacity:.6;"></i>Name</th>
                                <th><i class="fas fa-envelope" style="margin-right:0.4rem;opacity:.6;"></i>Email</th>
                                <th><i class="fas fa-school" style="margin-right:0.4rem;opacity:.6;"></i>Assigned Classes</th>
                                <th><i class="fas fa-building" style="margin-right:0.4rem;opacity:.6;"></i>Department</th>
                                <th><i class="fas fa-phone" style="margin-right:0.4rem;opacity:.6;"></i>Phone</th>
                                <th><i class="fas fa-key" style="margin-right:0.4rem;opacity:.6;"></i>Password</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($t = $teachers->fetch_assoc()): ?>
                                <?php
                                    $initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $t['full_name']), 0, 2)));
                                    $classes_list = $t['assigned_classes'] ? explode(', ', $t['assigned_classes']) : [];
                                ?>
                                <tr>
                                    <!-- Name + Staff ID -->
                                    <td>
                                        <div class="name-cell">
                                            <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
                                            <div>
                                                <div class="name-text"><?php echo htmlspecialchars($t['full_name']); ?></div>
                                                <?php if ($t['staff_id']): ?>
                                                    <div class="name-sub"><?php echo htmlspecialchars($t['staff_id']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Email -->
                                    <td><?php echo htmlspecialchars($t['email'] ?? '—'); ?></td>

                                    <!-- Assigned Classes -->
                                    <td>
                                        <?php if ($classes_list): ?>
                                            <div class="class-pills">
                                                <?php foreach ($classes_list as $cls): ?>
                                                    <span class="class-pill"><?php echo htmlspecialchars($cls); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:var(--gray-400);font-size:0.85rem;">None assigned</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Department -->
                                    <td><?php echo htmlspecialchars($t['department'] ?: '—'); ?></td>

                                    <!-- Phone -->
                                    <td><?php echo htmlspecialchars($t['phone'] ?: '—'); ?></td>

                                    <!-- Password -->
                                    <td>
                                        <?php if (!empty($t['user_id'])): ?>
                                            <div data-row="<?php echo (int)$t['user_id']; ?>">
                                                <!-- View mode -->
                                                <div class="view-mode password-cell">
                                                    <span class="password-mono password-text">••••••</span>
                                                    <button type="button"
                                                            class="btn-ghost toggle-visibility"
                                                            data-password="<?php echo htmlspecialchars($t['plain_password'] ?? ''); ?>">
                                                        <i class="fas fa-eye"></i> Show
                                                    </button>
                                                    <button type="button" class="btn-ghost edit-btn">
                                                        <i class="fas fa-pencil"></i> Edit
                                                    </button>
                                                </div>

                                                <!-- Edit mode -->
                                                <form method="POST" class="edit-form hidden edit-password-form">
                                                    <input type="hidden" name="update_password" value="1">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$t['user_id']; ?>">
                                                    <input type="text"
                                                           name="new_password"
                                                           maxlength="50"
                                                           value="<?php echo htmlspecialchars($t['plain_password'] ?? ''); ?>">
                                                    <button type="submit" class="btn-ghost-success">
                                                        <i class="fas fa-check"></i> Save
                                                    </button>
                                                    <button type="button" class="cancel-btn btn-ghost-danger">
                                                        <i class="fas fa-xmark"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <div style="display:flex;flex-direction:column;gap:0.25rem;">
                                                <span class="badge badge-warning"><i class="fas fa-triangle-exclamation"></i> No account</span>
                                                <a href="edit.php?id=<?php echo $t['id']; ?>" class="btn-ghost" style="font-size:0.75rem;">Create account</a>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="actions-cell">
                                            <a href="edit.php?id=<?php echo $t['id']; ?>" class="action-link action-link-primary">
                                                <i class="fas fa-pen-to-square"></i> Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
    document.querySelectorAll('[data-row]').forEach(function (row) {
        const viewMode  = row.querySelector('.view-mode');
        const editForm  = row.querySelector('.edit-form');
        const toggleBtn = row.querySelector('.toggle-visibility');
        const pwdText   = row.querySelector('.password-text');
        const editBtn   = row.querySelector('.edit-btn');
        const cancelBtn = row.querySelector('.cancel-btn');

        if (!viewMode) return;
        let visible = false;

        toggleBtn.addEventListener('click', function () {
            visible = !visible;
            pwdText.textContent = visible ? (toggleBtn.dataset.password || '(none)') : '••••••';
            toggleBtn.innerHTML = visible
                ? '<i class="fas fa-eye-slash"></i> Hide'
                : '<i class="fas fa-eye"></i> Show';
        });

        editBtn.addEventListener('click', function () {
            viewMode.classList.add('hidden');
            editForm.classList.remove('hidden');
        });

        cancelBtn.addEventListener('click', function () {
            editForm.classList.add('hidden');
            viewMode.classList.remove('hidden');
        });
    });
</script>

<?php include_once '../../includes/footer.php'; ?>