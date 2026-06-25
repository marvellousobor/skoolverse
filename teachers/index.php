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
    /* ═══════════════════ TEACHERS PAGE STYLES ═══════════════════ */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title .breadcrumb {
        font-size: 0.875rem;
        color: var(--gray-500);
        margin-top: 0.25rem;
    }

    .page-actions {
        display: flex;
        gap: 0.75rem;
    }

    /* Buttons */
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

    .btn-sm {
        padding: 0.4rem 0.9rem;
        font-size: 0.82rem;
    }

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

    /* Alert */
    .alert {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }

    .alert-success {
        background: #d1fae5;
        color: var(--success-color);
        border: 1px solid #6ee7b7;
    }

    .alert-danger {
        background: #fee2e2;
        color: var(--danger-color);
        border: 1px solid #fca5a5;
    }

    /* Card */
    .card {
        background: var(--white);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }

    .card-header h2 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--gray-900);
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin: 0;
    }

    /* Table */
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
        padding: 1rem 1.25rem;
        text-align: left;
        font-weight: 600;
        color: var(--gray-700);
        border-bottom: 2px solid var(--gray-200);
        white-space: nowrap;
    }

    .table td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--gray-200);
        color: var(--gray-800);
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: var(--gray-50);
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Avatar / Name cell */
    .teacher-name-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .teacher-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 0.85rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .teacher-name-text {
        font-weight: 600;
        color: var(--gray-900);
    }

    .teacher-staff-id {
        font-size: 0.78rem;
        color: var(--gray-500);
    }

    /* Badge */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.65rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-primary {
        background: #eff6ff;
        color: var(--primary-color);
    }

    .badge-warning {
        background: #fef3c7;
        color: #b45309;
    }

    /* Password cell */
    .password-cell {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .password-mono {
        font-family: monospace;
        font-size: 0.9rem;
        color: var(--gray-700);
        background: var(--gray-100);
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
    }

    .edit-password-form {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .edit-password-form input {
        border: 1px solid var(--gray-300);
        border-radius: 6px;
        padding: 0.35rem 0.6rem;
        font-size: 0.85rem;
        font-family: monospace;
        width: 9rem;
        outline: none;
        transition: border-color 0.2s;
    }

    .edit-password-form input:focus {
        border-color: var(--primary-color);
    }

    /* Actions cell */
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

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 4rem 1.5rem;
        color: var(--gray-400);
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.4;
    }

    .empty-state p {
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
    }

    /* Assigned classes pill list */
    .class-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    .class-pill {
        background: #f0f9ff;
        color: #0369a1;
        font-size: 0.73rem;
        font-weight: 600;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
    }

    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; }
        .table th, .table td { padding: 0.75rem; }
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
                                        <div class="teacher-name-cell">
                                            <div class="teacher-avatar"><?php echo htmlspecialchars($initials); ?></div>
                                            <div>
                                                <div class="teacher-name-text"><?php echo htmlspecialchars($t['full_name']); ?></div>
                                                <?php if ($t['staff_id']): ?>
                                                    <div class="teacher-staff-id"><?php echo htmlspecialchars($t['staff_id']); ?></div>
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