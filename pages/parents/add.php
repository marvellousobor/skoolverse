<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

if (isset($_GET['template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="parent_import_template.csv"');
    echo "first_name,last_name,email,admission_nos\n";
    echo "John,Doe,john.doe@example.com,FUTA/2023/001;FUTA/2023/002\n";
    echo "Jane,Smith,jane.smith@example.com,FUTA/2023/045\n";
    exit;
}

$students = $conn->query("SELECT id, admission_no, first_name, last_name FROM students WHERE is_active = 1 ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);
$error = '';
$success = '';
$generated_password = '';
$import_results = [];

function generateParentPassword($length = 5) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

function createParentWithStudents($conn, $first_name, $last_name, $email, $student_ids) {
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) { $check->close(); throw new Exception("A user with this email already exists."); }
    $check->close();

    $generated_password = generateParentPassword();
    $password_hash = password_hash($generated_password, PASSWORD_BCRYPT);
    $full_name = $first_name . ' ' . $last_name;
    $username = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $first_name . '.' . $last_name));
    $username = trim($username, '.') . '.' . time() . rand(10, 99);
    $role = ROLE_PARENT;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, plain_password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssss", $username, $email, $password_hash, $generated_password, $role);
        $stmt->execute();
        $parent_user_id = $conn->insert_id;

        $stmt = $conn->prepare("INSERT INTO parents (user_id, full_name) VALUES (?, ?)");
        $stmt->bind_param("is", $parent_user_id, $full_name);
        $stmt->execute();
        $parent_id = $conn->insert_id;

        $link = $conn->prepare("INSERT INTO student_parent_links (student_id, parent_id, relationship) VALUES (?, ?, 'Parent')");
        $update_student = $conn->prepare("UPDATE students SET parent_id = ? WHERE id = ?");
        foreach ($student_ids as $student_id) {
            $student_id = (int)$student_id;
            if ($student_id <= 0) continue;
            $link->bind_param("ii", $student_id, $parent_id);
            $link->execute();
            $update_student->bind_param("ii", $parent_id, $student_id);
            $update_student->execute();
        }
        $conn->commit();
        return $generated_password;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_single'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $student_ids = $_POST['student_ids'] ?? [];

        if ($first_name == '' || $last_name == '' || $email == '') {
            $error = "First name, last name, and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Enter a valid email address.";
        } elseif (empty($student_ids)) {
            $error = "Select at least one child/student.";
        } else {
            try {
                $generated_password = createParentWithStudents($conn, $first_name, $last_name, $email, $student_ids);
                $success = "Parent added successfully. Generated password: " . $generated_password;
            } catch (Exception $e) {
                $error = "Error adding parent: " . $e->getMessage();
            }
        }
    }

    if (isset($_POST['import_csv'])) {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $error = "Please choose a valid CSV file to upload.";
        } else {
            $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $error = "Only .csv files are accepted.";
            } else {
                $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
                if ($handle === false) {
                    $error = "Could not read the uploaded file.";
                } else {
                    $header = fgetcsv($handle);
                    if ($header === false) {
                        $error = "The CSV file appears to be empty.";
                    } else {
                        $header = array_map(function ($h) { return strtolower(trim($h)); }, $header);
                        $col = array_flip($header);
                        $required_cols = ['first_name', 'last_name', 'email', 'admission_nos'];
                        $missing = array_diff($required_cols, array_keys($col));
                        if (!empty($missing)) {
                            $error = "CSV header is missing column(s): " . implode(', ', $missing) . ". Expected columns: first_name,last_name,email,admission_nos";
                        } else {
                            $row_num = 1;
                            while (($row = fgetcsv($handle)) !== false) {
                                $row_num++;
                                if (count(array_filter($row, function ($v) { return trim((string)$v) !== ''; })) === 0) continue;
                                $f_name = trim($row[$col['first_name']] ?? '');
                                $l_name = trim($row[$col['last_name']] ?? '');
                                $r_email = filter_var(trim($row[$col['email']] ?? ''), FILTER_SANITIZE_EMAIL);
                                $adm_field = trim($row[$col['admission_nos']] ?? '');
                                $result = ['row'=>$row_num,'name'=>trim($f_name.' '.$l_name),'email'=>$r_email,'status'=>'error','message'=>'','password'=>''];
                                if ($f_name == '' || $l_name == '' || $r_email == '') { $result['message']="Missing first name, last name, or email."; $import_results[]=$result; continue; }
                                if (!filter_var($r_email, FILTER_VALIDATE_EMAIL)) { $result['message']="Invalid email address."; $import_results[]=$result; continue; }
                                if ($adm_field == '') { $result['message']="No admission number(s) provided."; $import_results[]=$result; continue; }
                                $admission_nos = array_filter(array_map('trim', preg_split('/[;,]/', $adm_field)));
                                $student_ids = []; $not_found = [];
                                foreach ($admission_nos as $adm_no) {
                                    $find = $conn->prepare("SELECT id FROM students WHERE admission_no = ? AND is_active = 1");
                                    $find->bind_param("s", $adm_no);
                                    $find->execute();
                                    $found_row = $find->get_result()->fetch_assoc();
                                    $find->close();
                                    if ($found_row) $student_ids[] = $found_row['id']; else $not_found[] = $adm_no;
                                }
                                if (empty($student_ids)) { $result['message']="No matching active student found for: ".implode(', ', $admission_nos); $import_results[]=$result; continue; }
                                try {
                                    $pwd = createParentWithStudents($conn, $f_name, $l_name, $r_email, $student_ids);
                                    $result['status']='success'; $result['password']=$pwd;
                                    $result['message'] = !empty($not_found) ? "Linked ".count($student_ids)." student(s). Not found: ".implode(', ', $not_found) : "Linked ".count($student_ids)." student(s).";
                                } catch (Exception $e) { $result['message'] = $e->getMessage(); }
                                $import_results[] = $result;
                            }
                            $success_count = count(array_filter($import_results, function ($r) { return $r['status'] === 'success'; }));
                            $success = "Import finished: {$success_count} of " . count($import_results) . " row(s) added successfully.";
                        }
                    }
                    fclose($handle);
                }
            }
        }
    }
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-user-plus"></i> Add Parent</h1>
                <span class="breadcrumb">Parents / Add</span>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Parents</a>
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

        <?php if (!empty($import_results)): ?>
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h2><i class="fas fa-file-import"></i> Import Results</h2></div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr><th>Row</th><th>Name</th><th>Email</th><th>Status</th><th>Password</th><th>Notes</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($import_results as $r): ?>
                                <tr>
                                    <td><?php echo (int)$r['row']; ?></td>
                                    <td style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['email']); ?></td>
                                    <td><?php echo $r['status']==='success' ? '<span class="badge badge-success">Added</span>' : '<span class="badge badge-danger">Failed</span>'; ?></td>
                                    <td style="font-family:monospace;"><?php echo htmlspecialchars($r['password']); ?></td>
                                    <td style="color:var(--gray-600);"><?php echo htmlspecialchars($r['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="padding:1rem 1.5rem;font-size:0.85rem;color:var(--gray-500);border-top:1px solid var(--gray-200);">
                    Passwords are also saved and can be viewed or edited anytime from the Parents page.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <div class="card-header"><h2><i class="fas fa-user"></i> Add a Single Parent</h2></div>
                <div class="card-body">
                    <form method="POST" style="display:flex;flex-direction:column;gap:1rem;">
                        <input type="hidden" name="add_single" value="1">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--gray-700);margin-bottom:0.5rem;">First Name *</label>
                                <input type="text" name="first_name" required style="width:100%;padding:0.6rem;border:1px solid var(--gray-300);border-radius:8px;">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--gray-700);margin-bottom:0.5rem;">Last Name *</label>
                                <input type="text" name="last_name" required style="width:100%;padding:0.6rem;border:1px solid var(--gray-300);border-radius:8px;">
                            </div>
                        </div>
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--gray-700);margin-bottom:0.5rem;">Email *</label>
                            <input type="email" name="email" required style="width:100%;padding:0.6rem;border:1px solid var(--gray-300);border-radius:8px;">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--gray-700);margin-bottom:0.5rem;">Children / Students *</label>
                            <select name="student_ids[]" multiple required size="8" style="width:100%;padding:0.6rem;border:1px solid var(--gray-300);border-radius:8px;">
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['first_name'].' '.$student['last_name'].' — '.$student['admission_no']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p style="font-size:0.75rem;color:var(--gray-500);margin-top:0.5rem;">Hold Ctrl/Cmd to select multiple.</p>
                        </div>
                        <div style="display:flex;gap:0.75rem;padding-top:0.5rem;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Parent</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2><i class="fas fa-file-csv"></i> Bulk Import via CSV</h2></div>
                <div class="card-body">
                    <p style="font-size:0.85rem;color:var(--gray-600);margin-bottom:1.25rem;">
                        Columns required: <code style="background:var(--gray-100);padding:2px 6px;border-radius:4px;">first_name, last_name, email, admission_nos</code>.
                        Separate multiple admission numbers with a semicolon, e.g.
                        <code style="background:var(--gray-100);padding:2px 6px;border-radius:4px;">FUTA/2023/001;FUTA/2023/002</code>.
                    </p>
                    <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:1rem;">
                        <input type="hidden" name="import_csv" value="1">
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--gray-700);margin-bottom:0.5rem;">CSV File *</label>
                            <input type="file" name="csv_file" accept=".csv" required style="width:100%;padding:0.6rem;border:1px solid var(--gray-300);border-radius:8px;background:var(--white);">
                        </div>
                        <div style="display:flex;gap:0.75rem;padding-top:0.5rem;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Import CSV</button>
                            <a href="?template=1" class="btn btn-outline"><i class="fas fa-download"></i> Download Template</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
