<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

$error   = '';
$success = '';

$classes  = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch teacher
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
if (!$teacher) {
    header('Location: index.php');
    exit;
}

function generateTeacherPassword($length = 8) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

// Fetch linked user
$linked_user = null;
if (!empty($teacher['user_id'])) {
    $u = $conn->prepare("SELECT id, email, role FROM users WHERE id = ? LIMIT 1");
    $u->bind_param('i', $teacher['user_id']);
    $u->execute();
    $linked_user = $u->get_result()->fetch_assoc();
}

// Fetch assignments
$assignments = $conn->prepare("SELECT id, teacher_id, subject_id, class_id, session_id FROM teacher_assignments WHERE teacher_id = ? ORDER BY id");
$assignments->bind_param('i', $id);
$assignments->execute();
$existing_assignments = $assignments->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id     = trim($_POST['staff_id']      ?? '');
    $full_name    = trim($_POST['full_name']      ?? '');
    $department   = trim($_POST['department']     ?? '');
    $phone        = trim($_POST['phone']          ?? '');
    $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $new_password = trim($_POST['new_password']   ?? '');

    if ($full_name === '') {
        $error = 'Full name is required.';
    } else {
        $conn->begin_transaction();
        try {
            if (!empty($teacher['user_id']) && !empty($linked_user) && ($linked_user['role'] ?? '') === ROLE_TEACHER) {
                $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $chk->bind_param('si', $email, $teacher['user_id']);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    throw new Exception('Email is already used by another account.');
                }
                $chk->close();

                if ($new_password !== '') {
                    $pwd_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $ust = $conn->prepare("UPDATE users SET email = ?, password_hash = ?, plain_password = ? WHERE id = ?");
                    $ust->bind_param('sssi', $email, $pwd_hash, $new_password, $teacher['user_id']);
                    $ust->execute();
                } else {
                    $ust = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $ust->bind_param('si', $email, $teacher['user_id']);
                    $ust->execute();
                }

                $user_id_to_use = $teacher['user_id'];
                $generated_password = '';
            } else {
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('A valid email is required for the teacher account.');
                }
                $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $chk->bind_param('s', $email);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    throw new Exception('A user with that email already exists.');
                }
                $chk->close();

                if ($new_password === '') {
                    $generated_password = generateTeacherPassword();
                    $password_to_store  = $generated_password;
                } else {
                    $generated_password = '';
                    $password_to_store  = $new_password;
                }
                $password_hash = password_hash($password_to_store, PASSWORD_BCRYPT);
                $username = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $full_name));
                $username = trim($username, '.') . '.' . time() . rand(10, 99);
                $role = ROLE_TEACHER;

                $ust = $conn->prepare("INSERT INTO users (username, email, password_hash, plain_password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $ust->bind_param('sssss', $username, $email, $password_hash, $password_to_store, $role);
                $ust->execute();
                $user_id_to_use = $conn->insert_id;
            }

            $ust2 = $conn->prepare("UPDATE teachers SET user_id = ?, staff_id = ?, full_name = ?, department = ?, phone = ? WHERE id = ?");
            $ust2->bind_param('isssii', $user_id_to_use, $staff_id, $full_name, $department, $phone, $id);
            $ust2->execute();

            $del = $conn->prepare("DELETE FROM teacher_assignments WHERE teacher_id = ?");
            $del->bind_param('i', $id);
            $del->execute();

            $assign_classes  = $_POST['assign_class']   ?? [];
            $assign_sessions = $_POST['assign_session'] ?? [];

            $ins_no_sub = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, subject_id, class_id, session_id) VALUES (?, NULL, ?, ?)");
            foreach ($assign_classes as $k => $cls) {
                $cls_id = (int)$cls;
                $ses_id = isset($assign_sessions[$k]) ? (int)$assign_sessions[$k] : 0;
                if ($cls_id > 0 && $ses_id > 0) {
                    $ins_no_sub->bind_param('iii', $id, $cls_id, $ses_id);
                    $ins_no_sub->execute();
                }
            }

            $conn->commit();
            $success = 'Teacher updated successfully.';
            if (!empty($generated_password)) {
                $success .= ' Generated password: ' . $generated_password;
            }

            // Refresh
            $assignments->execute();
            $existing_assignments = $assignments->get_result()->fetch_all(MYSQLI_ASSOC);
            if (!empty($user_id_to_use)) {
                $u = $conn->prepare("SELECT id, email FROM users WHERE id = ? LIMIT 1");
                $u->bind_param('i', $user_id_to_use);
                $u->execute();
                $linked_user = $u->get_result()->fetch_assoc();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error updating teacher: ' . $e->getMessage();
        }
    }
}
?>

<style>
    /* ═══════════════════ EDIT TEACHER STYLES ═══════════════════ */
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

    .alert-success .generated-pwd {
        font-family: monospace;
        font-size: 1rem;
        font-weight: 700;
        background: rgba(255,255,255,0.6);
        padding: 0.1rem 0.5rem;
        border-radius: 4px;
        margin-left: 0.25rem;
    }

    .alert-danger {
        background: #fee2e2;
        color: var(--danger-color);
        border: 1px solid #fca5a5;
    }

    /* Layout */
    .form-layout {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 1.5rem;
        align-items: start;
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
        gap: 0.6rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }

    .card-header h2 {
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .card-body { padding: 1.5rem; }

    /* Form */
    .form-group { margin-bottom: 1.25rem; }
    .form-group:last-child { margin-bottom: 0; }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 0.4rem;
    }

    .form-label .required { color: var(--danger-color); margin-left: 0.2rem; }

    .form-control {
        width: 100%;
        padding: 0.65rem 0.9rem;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 0.9rem;
        font-family: 'Segoe UI', sans-serif;
        color: var(--gray-900);
        background: var(--white);
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.08);
    }

    .form-hint {
        font-size: 0.78rem;
        color: var(--gray-500);
        margin-top: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    /* Password toggle field */
    .password-field-wrap {
        position: relative;
    }

    .password-field-wrap .form-control {
        padding-right: 2.8rem;
    }

    .toggle-pwd-btn {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: var(--gray-500);
        font-size: 0.9rem;
        padding: 0;
        transition: color 0.2s;
    }

    .toggle-pwd-btn:hover { color: var(--primary-color); }

    /* Assignments */
    .assignment-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 0.5rem;
        align-items: center;
        margin-bottom: 0.6rem;
    }

    .assignment-row select {
        width: 100%;
        padding: 0.55rem 0.75rem;
        border: 1px solid var(--gray-300);
        border-radius: 7px;
        font-size: 0.875rem;
        font-family: 'Segoe UI', sans-serif;
        color: var(--gray-800);
        background: var(--white);
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .assignment-row select:focus {
        outline: none;
        border-color: var(--primary-color);
    }

    .remove-row-btn {
        width: 30px;
        height: 30px;
        border: none;
        border-radius: 6px;
        background: #fee2e2;
        color: var(--danger-color);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        flex-shrink: 0;
        transition: background 0.2s;
    }

    .remove-row-btn:hover { background: #fca5a5; }

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

    .btn-primary { background: var(--primary-color); color: var(--white); }
    .btn-primary:hover { background: var(--primary-dark); box-shadow: 0 2px 8px rgba(30, 64, 175, 0.3); }

    .btn-secondary { background: var(--gray-200); color: var(--gray-800); }
    .btn-secondary:hover { background: var(--gray-300); }

    .btn-outline-sm {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border: 1px solid var(--gray-300);
        border-radius: 7px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        background: var(--white);
        color: var(--gray-700);
        transition: all 0.2s;
        font-family: 'Segoe UI', sans-serif;
    }

    .btn-outline-sm:hover { border-color: var(--primary-color); color: var(--primary-color); background: #eff6ff; }

    /* Info box */
    .info-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        padding: 1rem 1.1rem;
        font-size: 0.85rem;
        color: var(--primary-dark);
        display: flex;
        gap: 0.6rem;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .info-box i { margin-top: 0.1rem; flex-shrink: 0; }

    /* Teacher meta badge row */
    .teacher-meta {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.7rem;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        background: var(--gray-100);
        color: var(--gray-600);
    }

    @media (max-width: 900px) { .form-layout { grid-template-columns: 1fr; } }
</style>

<div class="main-wrapper">
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-pen-to-square"></i> Edit Teacher</h1>
                <div class="breadcrumb">
                    <a href="index.php" style="color:var(--primary-color);text-decoration:none;">Teachers</a>
                    &rsaquo; <?php echo htmlspecialchars($teacher['full_name']); ?>
                </div>
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
            <?php
                $pwd_part = '';
                $msg_part = $success;
                if (preg_match('/Generated password: (\S+)$/', $success, $m)) {
                    $pwd_part = $m[1];
                    $msg_part = trim(str_replace('Generated password: ' . $pwd_part, '', $success));
                }
            ?>
            <div class="alert alert-success">
                <i class="fas fa-circle-check"></i>
                <div>
                    <?php echo htmlspecialchars($msg_part); ?>
                    <?php if ($pwd_part): ?>
                        Generated password: <span class="generated-pwd"><?php echo htmlspecialchars($pwd_part); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-layout">

                <!-- LEFT: Main fields -->
                <div style="display:flex;flex-direction:column;gap:1.5rem;">

                    <!-- Personal Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-id-card"></i> Teacher Information</h2>
                        </div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                                <div class="form-group" style="grid-column:1/-1;">
                                    <label class="form-label">Full Name <span class="required">*</span></label>
                                    <input type="text" name="full_name"
                                           value="<?php echo htmlspecialchars($teacher['full_name']); ?>"
                                           class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Staff ID</label>
                                    <input type="text" name="staff_id"
                                           value="<?php echo htmlspecialchars($teacher['staff_id']); ?>"
                                           class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Department</label>
                                    <input type="text" name="department"
                                           value="<?php echo htmlspecialchars($teacher['department']); ?>"
                                           class="form-control">
                                </div>
                                <div class="form-group" style="grid-column:1/-1;">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone"
                                           value="<?php echo htmlspecialchars($teacher['phone']); ?>"
                                           class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Card -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-circle-user"></i> Login Account</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="required">*</span></label>
                                <input type="email" name="email"
                                       value="<?php echo htmlspecialchars($linked_user['email'] ?? ''); ?>"
                                       class="form-control" required>
                                <p class="form-hint">
                                    <i class="fas fa-circle-info"></i>
                                    A user account will be created or updated for this teacher.
                                </p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Set New Password <span style="color:var(--gray-400);font-weight:400;">(optional)</span></label>
                                <div class="password-field-wrap">
                                    <input type="password" name="new_password" id="newPwdInput"
                                           class="form-control" autocomplete="new-password">
                                    <button type="button" class="toggle-pwd-btn" onclick="togglePwd()">
                                        <i class="fas fa-eye" id="pwdEyeIcon"></i>
                                    </button>
                                </div>
                                <p class="form-hint">
                                    <i class="fas fa-circle-info"></i>
                                    Leave blank to keep the existing password, or let the system generate one for a new account.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Assignments Card -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-school"></i> Class Assignments</h2>
                        </div>
                        <div class="card-body">
                            <div id="assignments">
                                <?php if (empty($existing_assignments)): ?>
                                    <div class="assignment-row">
                                        <select name="assign_class[]">
                                            <option value="">— Select Class —</option>
                                            <?php foreach ($classes as $c): ?>
                                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="assign_session[]">
                                            <option value="">— Select Session —</option>
                                            <?php foreach ($sessions as $se): ?>
                                                <option value="<?php echo $se['id']; ?>"><?php echo htmlspecialchars($se['session_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="remove-row-btn" style="visibility:hidden;" onclick="this.closest('.assignment-row').remove()">
                                            <i class="fas fa-xmark"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($existing_assignments as $ea): ?>
                                        <div class="assignment-row">
                                            <select name="assign_class[]">
                                                <option value="">— Select Class —</option>
                                                <?php foreach ($classes as $c): ?>
                                                    <option value="<?php echo $c['id']; ?>" <?php echo $ea['class_id']==$c['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($c['class_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="assign_session[]">
                                                <option value="">— Select Session —</option>
                                                <?php foreach ($sessions as $se): ?>
                                                    <option value="<?php echo $se['id']; ?>" <?php echo $ea['session_id']==$se['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($se['session_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="remove-row-btn" onclick="this.closest('.assignment-row').remove()">
                                                <i class="fas fa-xmark"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div style="margin-top:0.75rem;">
                                <button type="button" id="addAssign" class="btn-outline-sm">
                                    <i class="fas fa-plus"></i> Add Assignment
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT: Sidebar -->
                <div>
                    <div class="card" style="position:sticky;top:1.5rem;">
                        <div class="card-header">
                            <h2><i class="fas fa-clipboard-check"></i> Save Changes</h2>
                        </div>
                        <div class="card-body">
                            <!-- Teacher meta info -->
                            <div class="teacher-meta">
                                <?php if ($teacher['staff_id']): ?>
                                    <span class="meta-chip"><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($teacher['staff_id']); ?></span>
                                <?php endif; ?>
                                <?php if ($linked_user): ?>
                                    <span class="meta-chip"><i class="fas fa-circle-check" style="color:var(--success-color);"></i> Account linked</span>
                                <?php else: ?>
                                    <span class="meta-chip"><i class="fas fa-triangle-exclamation" style="color:#b45309;"></i> No account yet</span>
                                <?php endif; ?>
                            </div>

                            <div class="info-box">
                                <i class="fas fa-circle-info"></i>
                                <span>All existing class assignments will be replaced with the ones listed here when you save.</span>
                            </div>

                            <div style="display:flex;flex-direction:column;gap:0.6rem;">
                                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                                    <i class="fas fa-floppy-disk"></i> Save Changes
                                </button>
                                <a href="index.php" class="btn btn-secondary" style="width:100%;justify-content:center;">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </main>
</div>

<script>
    // Password toggle
    function togglePwd() {
        const input = document.getElementById('newPwdInput');
        const icon  = document.getElementById('pwdEyeIcon');
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    }

    // Dynamic assignment rows
    const container      = document.getElementById('assignments');
    const classOptions   = <?php echo json_encode(array_map(fn($c) => ['id'=>$c['id'], 'n'=>$c['class_name']],   $classes));  ?>;
    const sessionOptions = <?php echo json_encode(array_map(fn($s) => ['id'=>$s['id'], 'n'=>$s['session_name']], $sessions)); ?>;

    function makeRow() {
        const row = document.createElement('div');
        row.className = 'assignment-row';

        const clsSel = document.createElement('select');
        clsSel.name = 'assign_class[]';
        clsSel.innerHTML = '<option value="">— Select Class —</option>' +
            classOptions.map(c => `<option value="${c.id}">${c.n}</option>`).join('');

        const sesSel = document.createElement('select');
        sesSel.name = 'assign_session[]';
        sesSel.innerHTML = '<option value="">— Select Session —</option>' +
            sessionOptions.map(s => `<option value="${s.id}">${s.n}</option>`).join('');

        const rmBtn = document.createElement('button');
        rmBtn.type = 'button';
        rmBtn.className = 'remove-row-btn';
        rmBtn.innerHTML = '<i class="fas fa-xmark"></i>';
        rmBtn.onclick = () => row.remove();

        row.appendChild(clsSel);
        row.appendChild(sesSel);
        row.appendChild(rmBtn);
        return row;
    }

    document.getElementById('addAssign').addEventListener('click', () => {
        container.appendChild(makeRow());
    });
</script>

<?php include_once '../../includes/footer.php'; ?>