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

$classes  = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

function generateTeacherPassword($length = 8) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id   = trim($_POST['staff_id']   ?? '');
    $full_name  = trim($_POST['full_name']  ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if ($full_name === '') {
        $error = 'Full name is required.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'A valid email is required for the teacher account.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param('s', $email);
        $chk->execute();
        $exists = $chk->get_result()->num_rows > 0;
        $chk->close();

        if ($exists) {
            $error = 'A user with that email already exists. Please use a different email.';
        } else {
            $generated_password = generateTeacherPassword();
            $password_hash = password_hash($generated_password, PASSWORD_BCRYPT);
            $username = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $full_name));
            $username = trim($username, '.') . '.' . time() . rand(10, 99);
            $role = ROLE_TEACHER;

            $conn->begin_transaction();
            try {
                $ust = $conn->prepare("INSERT INTO users (username, email, password_hash, plain_password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $ust->bind_param('sssss', $username, $email, $password_hash, $generated_password, $role);
                $ust->execute();
                $user_id = $conn->insert_id;

                $stmt = $conn->prepare("INSERT INTO teachers (user_id, staff_id, full_name, department, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('issss', $user_id, $staff_id, $full_name, $department, $phone);
                $stmt->execute();
                $teacher_id = $conn->insert_id;

                $assign_classes  = $_POST['assign_class']   ?? [];
                $assign_sessions = $_POST['assign_session'] ?? [];

                $ins_no_subject = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, subject_id, class_id, session_id) VALUES (?, NULL, ?, ?)");
                foreach ($assign_classes as $k => $cls) {
                    $cls_id = (int)$cls;
                    $ses_id = isset($assign_sessions[$k]) ? (int)$assign_sessions[$k] : 0;
                    if ($cls_id > 0 && $ses_id > 0) {
                        $ins_no_subject->bind_param('iii', $teacher_id, $cls_id, $ses_id);
                        $ins_no_subject->execute();
                    }
                }

                $conn->commit();
                $success = 'Teacher created successfully. Generated password: ' . $generated_password;
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error creating teacher: ' . $e->getMessage();
            }
        }
    }
}
?>

<style>
    /* ═══════════════════ CREATE TEACHER STYLES ═══════════════════ */
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

    .card-body {
        padding: 1.5rem;
    }

    /* Form fields */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 0.4rem;
    }

    .form-label .required {
        color: var(--danger-color);
        margin-left: 0.2rem;
    }

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
        color: var(--gray-800);
    }

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

    .btn-outline-sm:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background: #eff6ff;
    }

    /* Side card info box */
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

    .form-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--gray-200);
    }

    @media (max-width: 900px) {
        .form-layout { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .assignment-row { grid-template-columns: 1fr 1fr auto; }
    }
</style>

<div class="main-wrapper">
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-user-plus"></i> Create Teacher</h1>
                <div class="breadcrumb">
                    <a href="index.php" style="color:var(--primary-color);text-decoration:none;">Teachers</a>
                    &rsaquo; New Teacher
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
                // Split "Teacher created successfully. Generated password: XXXXX"
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
                                    <input type="text" name="full_name" class="form-control" placeholder="e.g. John Adeyemi" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Staff ID</label>
                                    <input type="text" name="staff_id" class="form-control" placeholder="e.g. TCH-001">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Department</label>
                                    <input type="text" name="department" class="form-control" placeholder="e.g. Sciences">
                                </div>
                                <div class="form-group" style="grid-column:1/-1;">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" placeholder="e.g. 08012345678">
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
                                <input type="email" name="email" class="form-control" placeholder="teacher@school.edu.ng" required>
                                <p class="form-hint">
                                    <i class="fas fa-circle-info"></i>
                                    A teacher account will be created automatically. A generated password will be shown after creation.
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
                                    <button type="button" class="remove-row-btn first-remove" style="visibility:hidden;" onclick="removeRow(this)">
                                        <i class="fas fa-xmark"></i>
                                    </button>
                                </div>
                            </div>
                            <div style="margin-top:0.75rem;">
                                <button type="button" id="addAssign" class="btn-outline-sm">
                                    <i class="fas fa-plus"></i> Add Assignment
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT: Summary sidebar -->
                <div>
                    <div class="card" style="position:sticky;top:1.5rem;">
                        <div class="card-header">
                            <h2><i class="fas fa-clipboard-check"></i> Summary</h2>
                        </div>
                        <div class="card-body">
                            <div class="info-box">
                                <i class="fas fa-circle-info"></i>
                                <span>A secure password will be auto-generated and displayed after you submit. You can always update it later from the teachers list.</span>
                            </div>
                            <div class="form-actions" style="margin-top:0;padding-top:0;border-top:none;flex-direction:column;gap:0.6rem;">
                                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                                    <i class="fas fa-user-plus"></i> Create Teacher
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
    const container = document.getElementById('assignments');

    function buildOptions(name, data, labelKey, valKey) {
        return data.map(item =>
            `<option value="${item[valKey]}">${item[labelKey].replace(/</g,'&lt;').replace(/>/g,'&gt;')}</option>`
        ).join('');
    }

    const classOptions   = <?php echo json_encode(array_map(fn($c)  => ['id'=>$c['id'],  'n'=>$c['class_name']],   $classes));  ?>;
    const sessionOptions = <?php echo json_encode(array_map(fn($s)  => ['id'=>$s['id'],  'n'=>$s['session_name']], $sessions)); ?>;

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

    function removeRow(btn) {
        btn.closest('.assignment-row').remove();
    }
</script>

<?php include_once '../../includes/footer.php'; ?>