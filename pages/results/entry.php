<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN && $user_role != ROLE_TEACHER) {
    header('Location: ../dashboard.php');
    exit();
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
?>

<?php
include_once '../../includes/results_schema.php';
ensure_results_schema($conn);

$error = '';
$success = '';
$is_teacher = ($user_role == ROLE_TEACHER);

// Get teacher ID if teacher
$teacher_id = 0;
if ($is_teacher) {
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $t_row = $stmt->get_result()->fetch_assoc();
    $teacher_id = $t_row ? (int)$t_row['id'] : 0;
}

// Build allowed class IDs for teacher
$allowed_class_ids = [];
if ($is_teacher && $teacher_id > 0) {
    $stmt = $conn->prepare("SELECT DISTINCT class_id FROM teacher_assignments WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $allowed_class_ids = array_map(function($r) { return $r['class_id']; }, $rows);
}

$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : (isset($_GET['class']) ? (int)$_GET['class'] : 0);
$session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : (isset($_GET['session']) ? (int)$_GET['session'] : 0);
$term_id = isset($_POST['term_id']) ? (int)$_POST['term_id'] : (isset($_GET['term']) ? (int)$_GET['term'] : 0);
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : (isset($_GET['student']) ? (int)$_GET['student'] : 0);

$students = [];
$subjects = [];

if ($is_teacher && $teacher_id > 0) {
    // Teachers: only their assigned classes
    if (!empty($allowed_class_ids)) {
        $ids_str = implode(',', $allowed_class_ids);
        $classes = $conn->query("SELECT id, class_name FROM classes WHERE id IN ($ids_str) ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
    } else {
        $classes = [];
    }
    // Validate class_id is in allowed set
    if ($class_id > 0 && !in_array($class_id, $allowed_class_ids)) {
        $class_id = 0;
        $error = 'You are not assigned to this class.';
    }
} else {
    $classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
}

$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

// Get students if class is selected
if ($class_id > 0) {
    $students = $conn->query("SELECT id, first_name, last_name, middle_name FROM students WHERE class_id = $class_id AND is_active = 1 ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);
}

// Get student's department (for filtering subjects)
$student_department = null;
if ($student_id > 0) {
    $dept_row = $conn->query("SELECT department FROM students WHERE id = $student_id")->fetch_assoc();
    $student_department = $dept_row ? $dept_row['department'] : null;
}

// Get subjects (filtered by class and optionally by student department)
if ($class_id > 0) {
    $subj_sql = "
        SELECT s.id, s.subject_name, s.subject_code
        FROM subjects s
        JOIN class_subjects cs ON cs.subject_id = s.id AND cs.class_id = $class_id
        WHERE s.is_active = 1";
    if ($student_department) {
        $subj_sql .= " AND (s.department IS NULL OR s.department = 'general' OR s.department = '$student_department')";
    }
    $subj_sql .= " ORDER BY s.subject_name";
    $subjects = $conn->query($subj_sql)->fetch_all(MYSQLI_ASSOC);
    if (empty($subjects)) {
        $subjects = $conn->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
    }
} else {
    $subjects = $conn->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
}

// Handle score submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_score'])) {
    $score = isset($_POST['score']) && trim($_POST['score']) !== '' ? (float)$_POST['score'] : null;

    if ($student_id <= 0 || $class_id <= 0 || $session_id <= 0 || $term_id <= 0) {
        $error = "Invalid selection. Please select all fields.";
    } elseif ($score !== null && ($score < 0 || $score > 100)) {
        $error = "Score must be between 0 and 100.";
    } else {
        if ($score === null) {
            $stmt = $conn->prepare("DELETE FROM student_results WHERE student_id = ? AND class_id = ? AND session_id = ? AND term_id = ? AND subject_id IN (SELECT id FROM subjects WHERE is_active = 1)");
            $stmt->bind_param("iiii", $student_id, $class_id, $session_id, $term_id);
            $stmt->execute();
        } else {
            $subject_ids = array_column($subjects, 'id');
            foreach ($subject_ids as $subj_id) {
                $ca = isset($_POST["ca_$subj_id"]) && trim($_POST["ca_$subj_id"]) !== '' ? (float)$_POST["ca_$subj_id"] : null;
                $exam = isset($_POST["exam_$subj_id"]) && trim($_POST["exam_$subj_id"]) !== '' ? (float)$_POST["exam_$subj_id"] : null;
                
                if ($ca !== null || $exam !== null) {
                    $total = ($ca ?: 0) + ($exam ?: 0);
                    if ($total > 100) $total = 100;
                    if ($total < 0) $total = 0;

                    if ($is_teacher && $teacher_id > 0) {
                        // Teacher saves with their ID
                        $stmt = $conn->prepare("INSERT INTO student_results (student_id, class_id, session_id, term_id, subject_id, score, ca_score, exam_score, uploaded_by_teacher_id, is_published, published_by_admin_id, published_at) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, $teacher_id, 0, NULL, NULL)
                                              ON DUPLICATE KEY UPDATE score = VALUES(score), ca_score = VALUES(ca_score), exam_score = VALUES(exam_score), uploaded_by_teacher_id = VALUES(uploaded_by_teacher_id), is_published = 0, published_by_admin_id = NULL, published_at = NULL");
                        $stmt->bind_param("iiiiiddd", $student_id, $class_id, $session_id, $term_id, $subj_id, $total, $ca, $exam);
                    } else {
                        // Admin saves (no teacher tracking)
                        $stmt = $conn->prepare("INSERT INTO student_results (student_id, class_id, session_id, term_id, subject_id, score, ca_score, exam_score, uploaded_by_teacher_id, is_published, published_by_admin_id, published_at) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, 0, NULL, NULL)
                                              ON DUPLICATE KEY UPDATE score = VALUES(score), ca_score = VALUES(ca_score), exam_score = VALUES(exam_score), uploaded_by_teacher_id = NULL, is_published = 0, published_by_admin_id = NULL, published_at = NULL");
                        $stmt->bind_param("iiiiiddd", $student_id, $class_id, $session_id, $term_id, $subj_id, $total, $ca, $exam);
                    }
                    $stmt->execute();
                }
            }
        }
        $success = "Scores saved successfully!";
    }
}

// Get student results if student is selected
$student_results = [];
if ($student_id > 0 && $class_id > 0 && $session_id > 0 && $term_id > 0) {
    $result =     $conn->query("
        SELECT sr.id, sr.subject_id, sr.score, sr.ca_score, sr.exam_score, sr.is_published
        FROM student_results sr
        WHERE sr.student_id = $student_id AND sr.class_id = $class_id AND sr.session_id = $session_id AND sr.term_id = $term_id
    ")->fetch_all(MYSQLI_ASSOC);
    
    foreach ($result as $row) {
        $student_results[$row['subject_id']] = $row;
    }
}
?>

<div class="main-wrapper">
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-pen-to-square"></i> Enter Student Scores</h1>
                <div class="breadcrumb">
                    <a href="<?php echo $is_teacher ? BASE_URL . 'pages/teachers/dashboard.php' : 'index.php'; ?>">
                        <i class="fas fa-arrow-left"></i> Back to Results
                    </a>
                </div>
            </div>
            <div class="page-actions">
                <a href="upload.php" class="btn btn-secondary">
                    <i class="fas fa-file-upload"></i> Bulk Upload
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">

            <!-- Main Form -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-sliders"></i> Score Entry</h2>
                </div>
                <div class="card-body">
                    <form method="POST" id="scoreForm">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Class <span class="req">*</span></label>
                                <select name="class_id" required onchange="document.getElementById('scoreForm').submit();" class="form-control">
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($classes as $cls): ?>
                                        <option value="<?php echo $cls['id']; ?>" <?php echo $class_id == $cls['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cls['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if ($class_id > 0): ?>
                                <div class="form-group">
                                    <label>Session <span class="req">*</span></label>
                                    <select name="session_id" required onchange="document.getElementById('scoreForm').submit();" class="form-control">
                                        <option value="">-- Select Session --</option>
                                        <?php foreach ($sessions as $sess): ?>
                                            <option value="<?php echo $sess['id']; ?>" <?php echo $session_id == $sess['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sess['session_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Term <span class="req">*</span></label>
                                    <select name="term_id" required onchange="document.getElementById('scoreForm').submit();" class="form-control">
                                        <option value="">-- Select Term --</option>
                                        <?php foreach ($terms as $trm): ?>
                                            <option value="<?php echo $trm['id']; ?>" <?php echo $term_id == $trm['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($trm['term_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Student <span class="req">*</span></label>
                                    <select name="student_id" required onchange="document.getElementById('scoreForm').submit();" class="form-control">
                                        <option value="">-- Select Student --</option>
                                        <?php foreach ($students as $std): ?>
                                            <?php
                                            $std_name = trim($std['first_name'] . ' ' . $std['last_name']);
                                            if (!empty($std['middle_name'])) {
                                                $std_name = trim($std['first_name'] . ' ' . $std['middle_name'] . ' ' . $std['last_name']);
                                            }
                                            ?>
                                            <option value="<?php echo $std['id']; ?>" <?php echo $student_id == $std['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($std_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($student_id > 0 && !empty($subjects)): ?>
                            <div style="margin-top:1.5rem;border-top:1px solid var(--gray-200);padding-top:1.5rem;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                                    <h3 style="font-weight:600;color:var(--gray-800);font-size:0.95rem;">
                                        <i class="fas fa-book-open"></i> Subjects (<?php echo count($subjects); ?>)
                                    </h3>
                                    <?php if ($student_department): ?>
                                        <span class="badge badge-info" style="font-size:0.8rem;">
                                            <i class="fas fa-layer-group"></i> <?php echo ucfirst($student_department); ?> Department
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div style="background:var(--gray-50);border-radius:10px;padding:1.25rem;">
                                    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 80px;gap:0.75rem;align-items:center;font-size:0.8rem;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:0.05em;padding-bottom:0.75rem;border-bottom:1px solid var(--gray-200);margin-bottom:0.75rem;">
                                        <span>Subject</span>
                                        <span style="text-align:center;">CA (0–40)</span>
                                        <span style="text-align:center;">Exam (0–70)</span>
                                        <span style="text-align:center;">Total</span>
                                    </div>

                                    <div style="display:flex;flex-direction:column;gap:0.5rem;">
                                        <?php foreach ($subjects as $subject): ?>
                                            <?php
                                            $r = $student_results[$subject['id']] ?? null;
                                            $ca_val = $r ? htmlspecialchars($r['ca_score']) : '';
                                            $exam_val = $r ? htmlspecialchars($r['exam_score']) : '';
                                            $total_val = $r ? htmlspecialchars($r['score']) : '';
                                            ?>
                                            <div style="display:grid;grid-template-columns:2fr 1fr 1fr 80px;gap:0.75rem;align-items:center;padding:0.4rem 0;">
                                                <label style="font-weight:500;font-size:0.9rem;color:var(--gray-800);">
                                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                </label>
                                                <input type="number" name="ca_<?php echo $subject['id']; ?>"
                                                       value="<?php echo $ca_val; ?>" min="0" max="40" step="0.01"
                                                       placeholder="CA"
                                                       class="form-control" style="text-align:center;padding:0.45rem 0.5rem;font-size:0.85rem;" />
                                                <input type="number" name="exam_<?php echo $subject['id']; ?>"
                                                       value="<?php echo $exam_val; ?>" min="0" max="70" step="0.01"
                                                       placeholder="Exam"
                                                       class="form-control" style="text-align:center;padding:0.45rem 0.5rem;font-size:0.85rem;" />
                                                <span style="font-weight:700;font-size:0.9rem;text-align:center;color:var(--gray-700);">
                                                    <?php echo $total_val ?: '—'; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button type="submit" name="save_score" value="1" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:1.25rem;">
                                    <i class="fas fa-floppy-disk"></i> Save Scores
                                </button>
                            </div>
                        <?php endif; ?>

                    </form>
                </div>
            </div>

            <!-- Tips Sidebar -->
            <div class="card" style="position:sticky;top:1.5rem;">
                <div class="card-header">
                    <h2><i class="fas fa-lightbulb"></i> Tips</h2>
                </div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:1.25rem;font-size:0.9rem;">
                    <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;font-size:0.85rem;">
                            <i class="fas fa-pencil"></i>
                        </div>
                        <div>
                            <h4 style="font-weight:600;color:var(--gray-900);margin-bottom:0.2rem;">Manual Entry</h4>
                            <p style="color:var(--gray-500);margin:0;font-size:0.85rem;">Enter CA (0–40) and Exam (0–70) scores for each subject. Total is calculated automatically.</p>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--success-color);display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;font-size:0.85rem;">
                            <i class="fas fa-upload"></i>
                        </div>
                        <div>
                            <h4 style="font-weight:600;color:var(--gray-900);margin-bottom:0.2rem;">Bulk Upload</h4>
                            <p style="color:var(--gray-500);margin:0;font-size:0.85rem;">
                                <a href="upload.php" style="color:var(--primary-color);font-weight:600;">Upload a CSV file</a> to enter scores for multiple students at once.
                            </p>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                        <div style="width:32px;height:32px;border-radius:50%;background:#f59e0b;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;font-size:0.85rem;">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <h4 style="font-weight:600;color:var(--gray-900);margin-bottom:0.2rem;">Department Filtering</h4>
                            <p style="color:var(--gray-500);margin:0;font-size:0.85rem;">SSS students only see subjects matching their department + general subjects.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
