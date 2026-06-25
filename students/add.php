<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

// Get classes, sessions, parents
$classes  = $conn->query("SELECT * FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT * FROM sessions")->fetch_all(MYSQLI_ASSOC);
$parents  = $conn->query("SELECT id, full_name FROM parents")->fetch_all(MYSQLI_ASSOC);

$class_map   = array_column($classes,  'id', 'class_name');
$session_map = array_column($sessions, 'id', 'session_name');
$parent_map  = array_column($parents,  'id', 'full_name');

$error   = '';
$success = '';
$selected_csv_class_id   = isset($_POST['csv_class_id'])   ? (int)$_POST['csv_class_id']   : 0;
$selected_csv_session_id = isset($_POST['csv_session_id']) ? (int)$_POST['csv_session_id'] : 0;

// ─── Sample CSV download ─────────────────────────────────────────────────────
if (isset($_GET['download_sample']) && $_GET['download_sample'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sample_students.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['admission_no','first_name','middle_name','last_name','gender','dob','email','phone','parent_name']);
    fputcsv($output, ['ADM001','John','','Doe','Male','2010-03-15','john.doe@example.com','08012345678','']);
    fputcsv($output, ['ADM002','Amaka','','Obi','Female','2011-07-22','amaka.obi@example.com','08023456789','']);
    fclose($output);
    exit;
}

// ─── CSV Bulk Import ─────────────────────────────────────────────────────────
$csv_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $class_exists   = in_array($selected_csv_class_id,   array_map('intval', array_column($classes, 'id')),   true);
    $session_exists = in_array($selected_csv_session_id, array_map('intval', array_column($sessions, 'id')), true);

    if (!$class_exists) {
        $error = "Please select the class for this import.";
    } elseif (!$session_exists) {
        $error = "Please select the academic session for this import.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload failed. Please try again.";
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $error = "Only CSV files are accepted.";
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        $expected_headers = ['admission_no','first_name','middle_name','last_name','gender','dob','email','phone','parent_name'];
        $header_row = fgetcsv($handle);

        if ($header_row === false) {
            $error = "CSV file is empty.";
            fclose($handle);
        } else {
            $headers = array_map('strtolower', array_map('trim', $header_row));
            $missing = array_diff($expected_headers, $headers);

            if (!empty($missing)) {
                $error = "CSV is missing required columns: " . implode(', ', $missing);
                fclose($handle);
            } else {
                $row_num = 1;
                $success_count = 0;
                $error_count   = 0;

                while (($row = fgetcsv($handle)) !== false) {
                    $row_num++;
                    $row  = array_slice(array_pad($row, count($headers), ''), 0, count($headers));
                    $data = array_combine($headers, $row);

                    $admission_no = trim($data['admission_no']);
                    $first_name   = htmlspecialchars(trim($data['first_name']));
                    $last_name    = htmlspecialchars(trim($data['last_name']));
                    $middle_name  = htmlspecialchars(trim($data['middle_name']));
                    $gender       = trim($data['gender']);
                    $dob          = trim($data['dob']);
                    $email        = htmlspecialchars(trim($data['email']));
                    $phone        = htmlspecialchars(trim($data['phone']));
                    $parent_name  = trim($data['parent_name']);
                    $display_name = "$first_name $last_name (Row $row_num)";

                    $row_error = '';
                    if (empty($admission_no) || empty($first_name) || empty($last_name) || empty($email)) {
                        $row_error = "Missing required fields (admission_no, first_name, last_name, email).";
                    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $row_error = "Invalid email: $email";
                    } else {
                        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
                        $chk->bind_param("s", $email);
                        $chk->execute();
                        if ($chk->get_result()->num_rows > 0) $row_error = "Email already exists: $email";
                        $chk->close();

                        if (!$row_error) {
                            $username = strtolower($admission_no);
                            $chk2 = $conn->prepare("SELECT id FROM users WHERE username = ?");
                            $chk2->bind_param("s", $username);
                            $chk2->execute();
                            if ($chk2->get_result()->num_rows > 0) $row_error = "Admission number already exists: $admission_no";
                            $chk2->close();
                        }
                    }

                    if ($row_error) {
                        $csv_results[] = ['row'=>$row_num,'name'=>$display_name,'status'=>'error','message'=>$row_error];
                        $error_count++;
                        continue;
                    }

                    $class_id   = $selected_csv_class_id;
                    $session_id = $selected_csv_session_id;
                    $parent_id  = !empty($parent_name) && isset($parent_map[$parent_name]) ? (int)$parent_map[$parent_name] : NULL;

                    $password_hash = password_hash("password123", PASSWORD_BCRYPT);
                    $role     = ROLE_STUDENT;
                    $username = strtolower($admission_no);

                    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
                    $stmt->bind_param("ssss", $username, $email, $password_hash, $role);

                    if (!$stmt->execute()) {
                        $csv_results[] = ['row'=>$row_num,'name'=>$display_name,'status'=>'error','message'=>"Failed to create user account: ".$conn->error];
                        $stmt->close(); $error_count++; continue;
                    }

                    $user_id = $conn->insert_id;
                    $stmt->close();

                    if ($parent_id !== NULL) {
                        $stmt2 = $conn->prepare("INSERT INTO students (user_id, admission_no, first_name, last_name, middle_name, gender, date_of_birth, class_id, session_id, parent_id, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                        $stmt2->bind_param("issssssiiis", $user_id, $admission_no, $first_name, $last_name, $middle_name, $gender, $dob, $class_id, $session_id, $parent_id, $phone);
                    } else {
                        $stmt2 = $conn->prepare("INSERT INTO students (user_id, admission_no, first_name, last_name, middle_name, gender, date_of_birth, class_id, session_id, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                        $stmt2->bind_param("issssssiis", $user_id, $admission_no, $first_name, $last_name, $middle_name, $gender, $dob, $class_id, $session_id, $phone);
                    }

                    if (!$stmt2->execute()) {
                        $conn->query("DELETE FROM users WHERE id = $user_id");
                        $csv_results[] = ['row'=>$row_num,'name'=>$display_name,'status'=>'error','message'=>"Failed to save student record: ".$conn->error];
                        $stmt2->close(); $error_count++; continue;
                    }

                    $student_id = $conn->insert_id;
                    $stmt2->close();

                    if ($parent_id !== NULL) {
                        $lnk = $conn->prepare("INSERT INTO student_parent_links (student_id, parent_id, relationship) VALUES (?, ?, 'Parent')");
                        $lnk->bind_param("ii", $student_id, $parent_id);
                        $lnk->execute(); $lnk->close();
                    }

                    $csv_results[] = ['row'=>$row_num,'name'=>$display_name,'status'=>'success','message'=>"Added successfully."];
                    $success_count++;
                }

                fclose($handle);

                if ($success_count > 0 && $error_count === 0) {
                    $success = "All $success_count student(s) imported successfully.";
                } elseif ($success_count > 0) {
                    $success = "$success_count student(s) imported. $error_count row(s) had errors.";
                } else {
                    $error = "Import failed — $error_count row(s) had errors. No students were added.";
                }
            }
        }
    }
}

// ─── Single Student ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['csv_file'])) {
    $admission_no  = htmlspecialchars($_POST['admission_no']);
    $first_name    = htmlspecialchars($_POST['first_name']);
    $last_name     = htmlspecialchars($_POST['last_name']);
    $middle_name   = htmlspecialchars($_POST['middle_name']);
    $gender        = $_POST['gender'];
    $date_of_birth = $_POST['dob'];
    $class_id      = (int)$_POST['class_id'];
    $session_id    = (int)$_POST['session_id'];
    $parent_id     = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
    $email         = htmlspecialchars($_POST['email']);
    $phone         = htmlspecialchars($_POST['phone']);
    $username      = strtolower($admission_no);

    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $error = "Email already exists.";
    } elseif (!$check_stmt->prepare("SELECT id FROM users WHERE username = ?")) {
        $error = "Database error: " . $conn->error;
    } else {
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Student with this admission number already exists.";
        } else {
            $password_hash = password_hash("password123", PASSWORD_BCRYPT);
            $role = ROLE_STUDENT;
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->bind_param("ssss", $username, $email, $password_hash, $role);

            if (!$stmt->execute()) {
                $error = "Error creating user account: " . $conn->error;
            } else {
                $user_id = $conn->insert_id;
                $stmt->close();

                if ($parent_id !== NULL) {
                    $sql = "INSERT INTO students (user_id, admission_no, first_name, last_name, middle_name, gender, date_of_birth, class_id, session_id, parent_id, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issssssiiis", $user_id, $admission_no, $first_name, $last_name, $middle_name, $gender, $date_of_birth, $class_id, $session_id, $parent_id, $phone);
                } else {
                    $sql = "INSERT INTO students (user_id, admission_no, first_name, last_name, middle_name, gender, date_of_birth, class_id, session_id, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issssssiis", $user_id, $admission_no, $first_name, $last_name, $middle_name, $gender, $date_of_birth, $class_id, $session_id, $phone);
                }

                if ($stmt->execute()) {
                    $student_id = $conn->insert_id;
                    if ($parent_id !== NULL) {
                        $lnk = $conn->prepare("INSERT INTO student_parent_links (student_id, parent_id, relationship) VALUES (?, ?, 'Parent')");
                        $lnk->bind_param("ii", $student_id, $parent_id);
                        $lnk->execute(); $lnk->close();
                    }
                    $success = "Student added successfully. Login: admission number / password: password123.";
                } else {
                    $error = "Error adding student: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
    $check_stmt->close();
}

$active_tab = (!empty($csv_results) || isset($_FILES['csv_file'])) ? 'csv' : 'single';
?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<style>
    .main-content { padding: 2rem; }

    .page-header {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--gray-500);
        font-size: 0.875rem;
        text-decoration: none;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        border: 1px solid var(--gray-200);
        background: var(--white);
        transition: all 0.2s;
    }

    .back-link:hover { background: var(--gray-100); color: var(--gray-900); }

    .page-title h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Alerts */
    .alert {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.9rem 1.25rem;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }

    .alert i { margin-top: 0.1rem; flex-shrink: 0; }
    .alert-error  { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
    .alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }

    /* Main card */
    .card {
        background: var(--white);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        max-width: 860px;
    }

    /* Tabs */
    .tab-bar {
        display: flex;
        border-bottom: 2px solid var(--gray-200);
        background: var(--gray-50);
    }

    .tab-btn {
        flex: 1;
        padding: 1rem 1.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-500);
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        font-family: inherit;
    }

    .tab-btn:hover { color: var(--primary-color); background: rgba(30,64,175,0.04); }

    .tab-btn.active {
        color: var(--primary-color);
        background: var(--white);
        border-bottom-color: var(--primary-color);
    }

    .tab-panel { padding: 2rem; }
    .tab-panel.hidden { display: none; }

    /* Form layout */
    .form-section {
        margin-bottom: 1.75rem;
    }

    .form-section-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--gray-100);
    }

    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.1rem;
    }

    .form-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 1.1rem;
    }

    .form-group { display: flex; flex-direction: column; gap: 0.4rem; }

    .form-group label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--gray-700);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .form-group label .req { color: var(--danger-color); }

    .form-control {
        padding: 0.6rem 0.9rem;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 0.9rem;
        color: var(--gray-900);
        background: var(--white);
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: inherit;
    }

    .btn:disabled { opacity: 0.4; cursor: not-allowed; }

    .btn-primary { background: var(--primary-color); color: var(--white); }
    .btn-primary:hover:not(:disabled) { background: var(--primary-dark); box-shadow: 0 2px 8px rgba(30,64,175,0.3); }

    .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
    .btn-secondary:hover { background: var(--gray-300); }

    .btn-ghost {
        background: transparent;
        color: var(--primary-color);
        border: 1px solid var(--primary-color);
    }

    .btn-ghost:hover { background: var(--primary-color); color: var(--white); }

    .btn-sm { padding: 0.45rem 1rem; font-size: 0.82rem; }

    .form-actions {
        display: flex;
        gap: 0.75rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--gray-100);
        margin-top: 0.5rem;
    }

    /* CSV info box */
    .info-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
    }

    .info-box h3 {
        font-size: 0.875rem;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 0.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-box p { font-size: 0.85rem; color: #1e40af; margin: 0 0 0.75rem 0; }

    .info-box code {
        display: block;
        background: var(--white);
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        padding: 0.6rem 0.9rem;
        font-size: 0.78rem;
        color: #1e3a8a;
        font-family: 'Courier New', monospace;
        word-break: break-all;
        margin-bottom: 0.75rem;
    }

    .info-list {
        list-style: none;
        margin: 0 0 0.75rem 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }

    .info-list li {
        font-size: 0.8rem;
        color: #1e40af;
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
    }

    .info-list li i { color: #3b82f6; margin-top: 0.15rem; flex-shrink: 0; }

    .download-sample {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--primary-color);
        text-decoration: none;
    }

    .download-sample:hover { text-decoration: underline; }

    /* Drop zone */
    .drop-zone {
        border: 2px dashed var(--gray-300);
        border-radius: 10px;
        padding: 2.5rem 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        margin-bottom: 1.25rem;
    }

    .drop-zone:hover,
    .drop-zone.drag-over { border-color: var(--primary-color); background: #eff6ff; }

    .drop-zone-icon { font-size: 2rem; color: var(--gray-400); margin-bottom: 0.75rem; }
    .drop-zone p { font-size: 0.9rem; font-weight: 600; color: var(--gray-600); margin: 0; }
    .drop-zone small { font-size: 0.78rem; color: var(--gray-400); }

    .file-name-display {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.75rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--primary-color);
        background: #eff6ff;
        padding: 0.4rem 0.85rem;
        border-radius: 6px;
    }

    /* Preview table */
    .preview-wrapper {
        margin-top: 1.25rem;
    }

    .preview-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--gray-700);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 0.6rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .preview-title span { font-weight: 400; color: var(--gray-500); text-transform: none; letter-spacing: 0; }

    .preview-table-wrap {
        overflow-x: auto;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
    }

    .preview-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.78rem;
    }

    .preview-table thead { background: var(--gray-100); }

    .preview-table th {
        padding: 0.55rem 0.75rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: var(--gray-600);
        white-space: nowrap;
    }

    .preview-table td {
        padding: 0.55rem 0.75rem;
        border-top: 1px solid var(--gray-100);
        color: var(--gray-700);
        white-space: nowrap;
    }

    .preview-hint { font-size: 0.75rem; color: var(--gray-400); margin-top: 0.35rem; }

    /* Import results table */
    .results-table-wrap {
        margin-top: 1.75rem;
    }

    .results-table-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 0.75rem;
    }

    .results-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .results-table thead { background: var(--gray-50); }

    .results-table th {
        padding: 0.7rem 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: var(--gray-600);
        border-bottom: 2px solid var(--gray-200);
    }

    .results-table td {
        padding: 0.7rem 1rem;
        border-bottom: 1px solid var(--gray-100);
        vertical-align: middle;
    }

    .results-table tr.row-success { background: #f0fdf4; }
    .results-table tr.row-error   { background: #fef2f2; }

    .status-success { display: inline-flex; align-items: center; gap: 0.35rem; color: #065f46; font-weight: 700; font-size: 0.8rem; }
    .status-error   { display: inline-flex; align-items: center; gap: 0.35rem; color: #991b1b; font-weight: 700; font-size: 0.8rem; }

    @media (max-width: 640px) {
        .main-content { padding: 1rem; }
        .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
        .tab-panel { padding: 1.25rem; }
    }
</style>

<div class="main-wrapper">
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Students
            </a>
            <div class="page-title">
                <h1><i class="fas fa-user-plus" style="color:var(--primary-color);font-size:1.5rem;"></i> Add New Student</h1>
            </div>
        </div>

        <!-- Global alerts (non-CSV) -->
        <?php if ($error && empty($csv_results)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success && empty($csv_results)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <!-- Tab Bar -->
            <div class="tab-bar">
                <button class="tab-btn <?php echo $active_tab === 'single' ? 'active' : ''; ?>"
                    id="tab-single" onclick="switchTab('single')">
                    <i class="fas fa-user"></i> Single Student
                </button>
                <button class="tab-btn <?php echo $active_tab === 'csv' ? 'active' : ''; ?>"
                    id="tab-csv" onclick="switchTab('csv')">
                    <i class="fas fa-file-csv"></i> Bulk CSV Import
                </button>
            </div>

            <!-- ── Single Student Panel ──────────────────────────────────── -->
            <div id="panel-single" class="tab-panel <?php echo $active_tab === 'csv' ? 'hidden' : ''; ?>">
                <form method="POST">

                    <div class="form-section">
                        <div class="form-section-title">Account Details</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Admission Number <span class="req">*</span></label>
                                <input type="text" name="admission_no" required class="form-control"
                                    placeholder="e.g. ADM2024001" />
                            </div>
                            <div class="form-group">
                                <label>Email Address <span class="req">*</span></label>
                                <input type="email" name="email" required class="form-control"
                                    placeholder="student@example.com" />
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Personal Information</div>
                        <div class="form-grid-3" style="margin-bottom:1.1rem;">
                            <div class="form-group">
                                <label>First Name <span class="req">*</span></label>
                                <input type="text" name="first_name" required class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="req">*</span></label>
                                <input type="text" name="last_name" required class="form-control" />
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" class="form-control"
                                    placeholder="08012345678" />
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Academic Placement</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Class <span class="req">*</span></label>
                                <select name="class_id" required class="form-control">
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $cls): ?>
                                        <option value="<?php echo $cls['id']; ?>"><?php echo htmlspecialchars($cls['class_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Academic Session <span class="req">*</span></label>
                                <select name="session_id" required class="form-control">
                                    <option value="">Select Session</option>
                                    <?php foreach ($sessions as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['session_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Parent (Optional)</label>
                                <select name="parent_id" class="form-control">
                                    <option value="">No parent selected</option>
                                    <?php foreach ($parents as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add Student
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-xmark"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- ── CSV Import Panel ──────────────────────────────────────── -->
            <div id="panel-csv" class="tab-panel <?php echo $active_tab === 'single' ? 'hidden' : ''; ?>">

                <!-- CSV Import alerts -->
                <?php if ($success && !empty($csv_results)): ?>
                    <div class="alert alert-success" style="margin-bottom:1.5rem;">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error && !empty($csv_results)): ?>
                    <div class="alert alert-error" style="margin-bottom:1.5rem;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Instructions -->
                <div class="info-box">
                    <h3><i class="fas fa-circle-info"></i> CSV Format Instructions</h3>
                    <p>Upload a CSV file with the following columns in the header row:</p>
                    <code>admission_no, first_name, middle_name, last_name, gender, dob, email, phone, parent_name</code>
                    <ul class="info-list">
                        <li><i class="fas fa-circle-dot"></i> <strong>dob</strong> format: YYYY-MM-DD (e.g. 2010-03-15)</li>
                        <li><i class="fas fa-circle-dot"></i> <strong>gender</strong>: Male, Female, or Other</li>
                        <li><i class="fas fa-circle-dot"></i> <strong>parent_name</strong>: optional — leave blank if not applicable</li>
                        <li><i class="fas fa-circle-dot"></i> Default login password for all imported students: <strong>password123</strong></li>
                        <li><i class="fas fa-circle-dot"></i> Select class and session below before uploading</li>
                    </ul>
                    <a href="?download_sample=csv" class="download-sample">
                        <i class="fas fa-download"></i> Download Sample CSV
                    </a>
                </div>

                <form method="POST" enctype="multipart/form-data" id="csv-form">

                    <div class="form-grid-2" style="margin-bottom:1.5rem;">
                        <div class="form-group">
                            <label>Class <span class="req">*</span></label>
                            <select name="csv_class_id" required class="form-control">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $cls): ?>
                                    <option value="<?php echo $cls['id']; ?>"
                                        <?php echo $selected_csv_class_id === (int)$cls['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cls['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Academic Session <span class="req">*</span></label>
                            <select name="csv_session_id" required class="form-control">
                                <option value="">Select Session</option>
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"
                                        <?php echo $selected_csv_session_id === (int)$s['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['session_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Drop Zone -->
                    <div class="drop-zone" id="drop-zone"
                        onclick="document.getElementById('csv_file_input').click()">
                        <div class="drop-zone-icon">
                            <i class="fas fa-cloud-arrow-up"></i>
                        </div>
                        <p>Click to choose a CSV file</p>
                        <small>or drag and drop here</small>
                        <div id="file-name-display" class="file-name-display" style="display:none;">
                            <i class="fas fa-file-csv"></i>
                            <span id="file-name-text"></span>
                        </div>
                        <input type="file" id="csv_file_input" name="csv_file" accept=".csv"
                            style="display:none;" onchange="handleFileSelect(this)" />
                    </div>

                    <!-- Preview -->
                    <div id="preview-wrapper" class="preview-wrapper" style="display:none;">
                        <div class="preview-title">
                            Preview <span id="preview-count"></span>
                        </div>
                        <div class="preview-table-wrap">
                            <table class="preview-table">
                                <thead><tr id="preview-header-row"></tr></thead>
                                <tbody id="preview-body"></tbody>
                            </table>
                        </div>
                        <div class="preview-hint">Showing first 5 rows.</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" id="import-btn" disabled class="btn btn-primary">
                            <i class="fas fa-file-import"></i> Import Students
                        </button>
                        <button type="button" onclick="clearFile()" class="btn btn-secondary">
                            <i class="fas fa-rotate-left"></i> Clear
                        </button>
                    </div>
                </form>

                <!-- Import Results -->
                <?php if (!empty($csv_results)): ?>
                    <div class="results-table-wrap">
                        <div class="results-table-title">
                            <i class="fas fa-list-check" style="color:var(--primary-color);"></i>
                            Import Results
                        </div>
                        <div style="border:1px solid var(--gray-200);border-radius:10px;overflow:hidden;">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">Row</th>
                                        <th>Student</th>
                                        <th style="width:100px;">Status</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($csv_results as $r): ?>
                                        <tr class="<?php echo $r['status'] === 'success' ? 'row-success' : 'row-error'; ?>">
                                            <td style="color:var(--gray-400);font-size:0.8rem;"><?php echo $r['row']; ?></td>
                                            <td style="font-weight:600;"><?php echo htmlspecialchars($r['name']); ?></td>
                                            <td>
                                                <?php if ($r['status'] === 'success'): ?>
                                                    <span class="status-success">
                                                        <i class="fas fa-circle-check"></i> Success
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-error">
                                                        <i class="fas fa-circle-xmark"></i> Error
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size:0.82rem;color:var(--gray-600);"><?php echo htmlspecialchars($r['message']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div><!-- /panel-csv -->
        </div><!-- /card -->

    </main>
</div>

<script>
// Tab switching
function switchTab(tab) {
    ['single','csv'].forEach(t => {
        document.getElementById('panel-' + t).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById('tab-' + t);
        btn.classList.toggle('active', t === tab);
    });
}

// Drag and drop
const dropZone = document.getElementById('drop-zone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
        document.getElementById('csv_file_input').files = e.dataTransfer.files;
        handleFileSelect(document.getElementById('csv_file_input'));
    }
});

// File selection
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;

    const nameDisplay = document.getElementById('file-name-display');
    document.getElementById('file-name-text').textContent = file.name;
    nameDisplay.style.display = 'inline-flex';
    document.getElementById('import-btn').disabled = false;

    const reader = new FileReader();
    reader.onload = function(e) {
        const lines = e.target.result.split('\n').filter(l => l.trim());
        if (lines.length < 2) return;

        const headers = parseCSVLine(lines[0]);
        const headerRow = document.getElementById('preview-header-row');
        const body = document.getElementById('preview-body');

        headerRow.innerHTML = headers.map(h => `<th>${escHtml(h)}</th>`).join('');
        body.innerHTML = '';
        lines.slice(1, 6).forEach(line => {
            const cols = parseCSVLine(line);
            body.innerHTML += '<tr>' + cols.map(c => `<td>${escHtml(c)}</td>`).join('') + '</tr>';
        });

        const total = lines.length - 1;
        document.getElementById('preview-count').textContent = `(${total} student${total !== 1 ? 's' : ''})`;
        document.getElementById('preview-wrapper').style.display = 'block';
    };
    reader.readAsText(file);
}

function clearFile() {
    document.getElementById('csv_file_input').value = '';
    document.getElementById('file-name-display').style.display = 'none';
    document.getElementById('file-name-text').textContent = '';
    document.getElementById('preview-wrapper').style.display = 'none';
    document.getElementById('import-btn').disabled = true;
}

function parseCSVLine(line) {
    const result = [];
    let cur = '', inQuotes = false;
    for (let i = 0; i < line.length; i++) {
        if (line[i] === '"') { inQuotes = !inQuotes; }
        else if (line[i] === ',' && !inQuotes) { result.push(cur.trim()); cur = ''; }
        else { cur += line[i]; }
    }
    result.push(cur.trim());
    return result;
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php include_once '../../includes/footer.php'; ?>