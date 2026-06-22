<?php include_once '../../includes/auth_check.php'; ?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<?php
// Only admins can view this page
if ($user_role != ROLE_ADMIN) {
    header('Location: ../dashboard.php');
    exit();
}

// Get student ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$student_id = (int)$_GET['id'];
$error = '';
$success = '';

// Fetch student data
$student_sql = "SELECT * FROM students WHERE id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$student = $student_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name   = trim($_POST['first_name'] ?? '');
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender       = trim($_POST['gender'] ?? '');
    $class_id     = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
    $is_active    = isset($_POST['is_active']) ? 1 : 0;

    if (empty($first_name)) {
        $error = 'First name is required.';
    } elseif (empty($last_name)) {
        $error = 'Last name is required.';
    } elseif (empty($gender)) {
        $error = 'Gender is required.';
    } else {
        $update_sql = "UPDATE students SET 
            first_name = ?, middle_name = ?, last_name = ?,
            phone = ?, date_of_birth = ?, gender = ?,
            class_id = ?, is_active = ?
            WHERE id = ?";

        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param(
            "sssssssii",
            $first_name, $middle_name, $last_name,
            $phone, $date_of_birth, $gender,
            $class_id, $is_active, $student_id
        );

        if ($update_stmt->execute()) {
            $success = 'Student updated successfully!';
            $student['first_name']   = $first_name;
            $student['middle_name']  = $middle_name;
            $student['last_name']    = $last_name;
            $student['phone']        = $phone;
            $student['date_of_birth'] = $date_of_birth;
            $student['gender']       = $gender;
            $student['class_id']     = $class_id;
            $student['is_active']    = $is_active;
        } else {
            $error = 'Failed to update student. Please try again.';
        }
    }
}

// Get all classes for dropdown
$classes_result = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}
?>

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

    .back-link:hover {
        background: var(--gray-100);
        color: var(--gray-900);
        border-color: var(--gray-300);
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
    }

    /* Card */
    .card {
        background: var(--white);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        max-width: 780px;
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.75rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }

    .card-header h2 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin: 0;
    }

    .card-body { padding: 2rem 1.75rem; }

    /* Alerts */
    .alert {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1.25rem;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }

    .alert-error  { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
    .alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }

    /* Form */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .form-group { display: flex; flex-direction: column; gap: 0.4rem; }

    .form-group label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--gray-700);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .form-group label .required { color: var(--danger-color); }

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

    /* Toggle switch for is_active */
    .toggle-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: var(--gray-50);
        border-radius: 8px;
        border: 1px solid var(--gray-200);
        cursor: pointer;
        grid-column: span 2;
    }

    .toggle-row input[type="checkbox"] {
        width: 1.1rem;
        height: 1.1rem;
        accent-color: var(--primary-color);
        cursor: pointer;
    }

    .toggle-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-700);
    }

    .toggle-hint {
        font-size: 0.8rem;
        color: var(--gray-500);
        margin-left: auto;
    }

    /* Form actions */
    .form-actions {
        display: flex;
        gap: 0.75rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--gray-200);
    }

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

    .btn-primary { background: var(--primary-color); color: var(--white); }
    .btn-primary:hover { background: var(--primary-dark); box-shadow: 0 2px 8px rgba(30,64,175,0.3); }

    .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
    .btn-secondary:hover { background: var(--gray-300); }

    /* Student meta strip */
    .student-meta {
        display: flex;
        gap: 1.5rem;
        padding: 1rem 1.75rem;
        background: var(--primary-color);
        color: rgba(255,255,255,0.9);
        font-size: 0.8rem;
        border-bottom: 1px solid var(--gray-200);
        flex-wrap: wrap;
    }

    .student-meta-item { display: flex; align-items: center; gap: 0.4rem; }
    .student-meta-item strong { color: var(--white); }

    @media (max-width: 640px) {
        .main-content { padding: 1rem; }
        .form-grid { grid-template-columns: 1fr; }
        .toggle-row { grid-column: span 1; }
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
                <h1><i class="fas fa-user-pen" style="color:var(--primary-color);font-size:1.5rem;"></i> Edit Student</h1>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Edit Card -->
        <div class="card">
            <!-- Student meta bar -->
            <div class="student-meta">
                <div class="student-meta-item">
                    <i class="fas fa-id-card"></i>
                    Admission No: <strong><?php echo htmlspecialchars($student['admission_no'] ?? '—'); ?></strong>
                </div>
                <div class="student-meta-item">
                    <i class="fas fa-calendar-plus"></i>
                    Enrolled: <strong><?php echo !empty($student['created_at']) ? date('d M Y', strtotime($student['created_at'])) : '—'; ?></strong>
                </div>
            </div>

            <div class="card-header">
                <h2><i class="fas fa-pen-to-square"></i> Student Information</h2>
            </div>

            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-grid">

                        <!-- First Name -->
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name"
                                value="<?php echo htmlspecialchars($student['first_name']); ?>"
                                required class="form-control" />
                        </div>

                        <!-- Middle Name -->
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name"
                                value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>"
                                class="form-control" />
                        </div>

                        <!-- Last Name -->
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name"
                                value="<?php echo htmlspecialchars($student['last_name']); ?>"
                                required class="form-control" />
                        </div>

                        <!-- Phone -->
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>"
                                class="form-control" />
                        </div>

                        <!-- Date of Birth -->
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth"
                                value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>"
                                class="form-control" />
                        </div>

                        <!-- Gender -->
                        <div class="form-group">
                            <label for="gender">Gender <span class="required">*</span></label>
                            <select id="gender" name="gender" required class="form-control">
                                <option value="">Select Gender</option>
                                <option value="Male"   <?php echo $student['gender'] === 'Male'   ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other"  <?php echo $student['gender'] === 'Other'  ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <!-- Class -->
                        <div class="form-group">
                            <label for="class_id">Class</label>
                            <select id="class_id" name="class_id" class="form-control">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $cls): ?>
                                    <option value="<?php echo $cls['id']; ?>"
                                        <?php echo $student['class_id'] == $cls['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cls['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Active Status -->
                        <div class="form-group" style="justify-content:flex-end;">
                            <label>Status</label>
                            <label class="toggle-row" style="grid-column:unset;">
                                <input type="checkbox" id="is_active" name="is_active" value="1"
                                    <?php echo $student['is_active'] ? 'checked' : ''; ?> />
                                <span class="toggle-label">Active Student</span>
                                <span class="toggle-hint">Student can log in when active</span>
                            </label>
                        </div>

                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i> Save Changes
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-xmark"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>