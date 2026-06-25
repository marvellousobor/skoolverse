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

// Get class ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$class_id = (int)$_GET['id'];

// Fetch current class data
$result = $conn->query("SELECT * FROM classes WHERE id = $class_id");
if ($result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$class = $result->fetch_assoc();
$class_name = $class['class_name'];
$level = $class['level'];

// Determine if this is a Senior Secondary class
$is_sss = stripos($level, 'Senior') !== false;

// Fetch all active subjects (with department info for SSS grouping)
$all_subjects = $conn->query("SELECT id, subject_name, subject_code, department FROM subjects WHERE is_active = 1 ORDER BY department, subject_name")->fetch_all(MYSQLI_ASSOC);

// Group subjects by department for SSS display
$grouped = [];
if ($is_sss) {
    foreach ($all_subjects as $s) {
        $dept = $s['department'] ?? 'general';
        $grouped[$dept][] = $s;
    }
    // Define display order for departments
    $dept_order = ['general', 'science', 'commercial', 'arts'];
    $ordered = [];
    foreach ($dept_order as $d) {
        if (isset($grouped[$d])) {
            $ordered[$d] = $grouped[$d];
        }
    }
    $grouped = $ordered;
}

// Fetch currently assigned subject IDs for this class
$assigned = $conn->query("SELECT subject_id FROM class_subjects WHERE class_id = $class_id")->fetch_all(MYSQLI_ASSOC);
$assigned_ids = array_map(function($r) { return $r['subject_id']; }, $assigned);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_name = htmlspecialchars($_POST['class_name']);
    $level = htmlspecialchars($_POST['level']);
    $subject_ids = isset($_POST['subject_ids']) ? (array)$_POST['subject_ids'] : [];
    
    // Validate inputs
    if (empty($class_name)) {
        $error = "Class name is required";
    } elseif (empty($level)) {
        $error = "Level is required";
    } else {
        // Check if class name already exists (excluding current class)
        $check = $conn->query("SELECT id FROM classes WHERE class_name = '$class_name' AND id != $class_id");
        if ($check->num_rows > 0) {
            $error = "Class name already exists";
        } else {
            // Update class record
            $sql = "UPDATE classes SET class_name = ?, level = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $class_name, $level, $class_id);
            
            if ($stmt->execute()) {
                // Update subject assignments
                $conn->query("DELETE FROM class_subjects WHERE class_id = $class_id");
                if (!empty($subject_ids)) {
                    $ins = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id, is_compulsory) VALUES (?, ?, 1)");
                    foreach ($subject_ids as $sid) {
                        $sid = (int)$sid;
                        if ($sid > 0) {
                            $ins->bind_param("ii", $class_id, $sid);
                            $ins->execute();
                        }
                    }
                }
                $success = "Class updated successfully!";
                $assigned_ids = array_map('intval', $subject_ids);
            } else {
                $error = "Error updating class: " . $conn->error;
            }
        }
    }
}
?>

<style>
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

    .form-layout {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 1.5rem;
        align-items: start;
    }

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

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin-bottom: 1.25rem;
    }

    .form-group:last-child { margin-bottom: 0; }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-700);
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

    .subject-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 0.5rem;
    }

    .subject-checkbox {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 0.7rem;
        border: 1px solid var(--gray-200);
        border-radius: 7px;
        cursor: pointer;
        font-size: 0.85rem;
        color: var(--gray-700);
        background: var(--white);
        transition: all 0.15s ease;
        user-select: none;
    }

    .subject-checkbox:hover {
        border-color: var(--primary-light);
        background: #f8faff;
    }

    .subject-checkbox.checked {
        background: #eff6ff;
        border-color: #93c5fd;
        color: var(--primary-color);
        font-weight: 500;
    }

    .subject-checkbox input[type="checkbox"] {
        width: 1rem;
        height: 1rem;
        accent-color: var(--primary-color);
        cursor: pointer;
        flex-shrink: 0;
    }

    .subject-code-tag {
        font-size: 0.7rem;
        color: var(--gray-400);
        margin-left: auto;
        font-weight: 600;
        flex-shrink: 0;
    }

    .dept-section {
        margin-bottom: 1rem;
    }

    .dept-section:last-child {
        margin-bottom: 0;
    }

    .dept-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 0.7rem;
        margin-bottom: 0.5rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
    }

    .dept-header i {
        font-size: 0.85rem;
        width: 16px;
        text-align: center;
    }

    .dept-header .dept-count {
        font-weight: 600;
        font-size: 0.72rem;
        color: var(--gray-500);
        text-transform: none;
        letter-spacing: 0;
        margin-left: auto;
    }

    .select-all-bar {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--gray-200);
        font-size: 0.82rem;
    }

    .select-all-bar button {
        background: none;
        border: 1px solid var(--gray-300);
        border-radius: 6px;
        padding: 0.3rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        color: var(--gray-600);
        transition: all 0.15s;
        font-family: 'Segoe UI', sans-serif;
    }

    .select-all-bar button:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .assign-count {
        font-size: 0.78rem;
        color: var(--gray-500);
        margin-left: auto;
    }

    @media (max-width: 900px) {
        .form-layout { grid-template-columns: 1fr; }
        .subject-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
    }

    @media (max-width: 768px) {
        .main-content { padding: 1rem; }
        .page-header { flex-direction: column; align-items: flex-start; }
        .form-actions { flex-direction: column; align-items: stretch; }
        .btn { justify-content: center; }
    }
</style>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-pen-to-square"></i> Edit Class</h1>
                <div class="breadcrumb">
                    <a href="index.php" style="color:var(--primary-color);text-decoration:none;">Classes</a>
                    &rsaquo; <?php echo htmlspecialchars($class_name); ?>
                </div>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Classes
                </a>
            </div>
        </div>

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

        <form method="POST">
            <div class="form-layout">
                <div style="display:flex;flex-direction:column;gap:1.5rem;">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-pen-to-square"></i> Class Information</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Class Name <span class="required">*</span></label>
                                <input type="text" name="class_name" required
                                       placeholder="e.g., JSS 1A, SS 2B"
                                       value="<?php echo htmlspecialchars($class_name); ?>"
                                       class="form-control">
                            </div>

                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Level <span class="required">*</span></label>
                                <input type="text" name="level" required
                                       placeholder="e.g., Junior Secondary, Senior Secondary"
                                       value="<?php echo htmlspecialchars($level); ?>"
                                       class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-book-open"></i> Subjects Offered</h2>
                            <span style="font-size:0.8rem;color:var(--gray-500);"><?php echo count($assigned_ids); ?> / <?php echo count($all_subjects); ?> assigned</span>
                        </div>
                        <div class="card-body">
                            <?php if ($is_sss): ?>
                                <div class="select-all-bar">
                                    <button type="button" onclick="toggleAll(true)"><i class="fas fa-check-square"></i> Select All</button>
                                    <button type="button" onclick="toggleAll(false)"><i class="fas fa-square"></i> Deselect All</button>
                                    <span class="assign-count" id="assignCount"><?php echo count($assigned_ids); ?> selected</span>
                                </div>
                                <?php $dept_labels = ['general' => 'General', 'science' => 'Science', 'commercial' => 'Commercial', 'arts' => 'Arts']; ?>
                                <?php $dept_icons = ['general' => 'fas fa-star', 'science' => 'fas fa-flask', 'commercial' => 'fas fa-briefcase', 'arts' => 'fas fa-palette']; ?>
                                <?php foreach ($grouped as $dept => $subjects): ?>
                                    <div class="dept-section">
                                        <div class="dept-header">
                                            <i class="<?php echo $dept_icons[$dept] ?? 'fas fa-book'; ?>"></i>
                                            <?php echo $dept_labels[$dept] ?? ucfirst($dept); ?>
                                            <span class="dept-count"><?php echo count($subjects); ?> subjects</span>
                                        </div>
                                        <div class="subject-grid">
                                            <?php foreach ($subjects as $s): ?>
                                                <?php $checked = in_array($s['id'], $assigned_ids); ?>
                                                <label class="subject-checkbox<?php echo $checked ? ' checked' : ''; ?>">
                                                    <input type="checkbox" name="subject_ids[]"
                                                           value="<?php echo $s['id']; ?>"
                                                           onchange="this.parentElement.classList.toggle('checked')"
                                                           <?php echo $checked ? 'checked' : ''; ?>>
                                                    <?php echo htmlspecialchars($s['subject_name']); ?>
                                                    <?php if ($s['subject_code']): ?>
                                                        <span class="subject-code-tag"><?php echo htmlspecialchars($s['subject_code']); ?></span>
                                                    <?php endif; ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="select-all-bar">
                                    <button type="button" onclick="toggleAll(true)"><i class="fas fa-check-square"></i> Select All</button>
                                    <button type="button" onclick="toggleAll(false)"><i class="fas fa-square"></i> Deselect All</button>
                                    <span class="assign-count" id="assignCount"><?php echo count($assigned_ids); ?> selected</span>
                                </div>
                                <div class="subject-grid">
                                    <?php foreach ($all_subjects as $s): ?>
                                        <?php $checked = in_array($s['id'], $assigned_ids); ?>
                                        <label class="subject-checkbox<?php echo $checked ? ' checked' : ''; ?>">
                                            <input type="checkbox" name="subject_ids[]"
                                                   value="<?php echo $s['id']; ?>"
                                                   onchange="this.parentElement.classList.toggle('checked')"
                                                   <?php echo $checked ? 'checked' : ''; ?>>
                                            <?php echo htmlspecialchars($s['subject_name']); ?>
                                            <?php if ($s['subject_code']): ?>
                                                <span class="subject-code-tag"><?php echo htmlspecialchars($s['subject_code']); ?></span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card" style="position: sticky; top: 1.5rem;">
                        <div class="card-header">
                            <h2><i class="fas fa-clipboard-check"></i> Summary</h2>
                        </div>
                        <div class="card-body">
                            <div class="info-box">
                                <i class="fas fa-circle-info"></i>
                                <span>Update the class name and level as needed. Select the subjects offered by this class.</span>
                            </div>

                            <div class="form-actions" style="margin-top:0;padding-top:0;border-top:none;flex-direction:column;gap:0.6rem;">
                                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                                    <i class="fas fa-save"></i> Update Class
                                </button>
                                <a href="index.php" class="btn btn-secondary" style="width:100%;justify-content:center;">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script>
        function toggleAll(select) {
            const checkboxes = document.querySelectorAll('.subject-checkbox input[type="checkbox"]');
            checkboxes.forEach(cb => {
                cb.checked = select;
                cb.parentElement.classList.toggle('checked', select);
            });
            updateCount();
        }

        document.querySelectorAll('.subject-checkbox input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', updateCount);
        });

        function updateCount() {
            const count = document.querySelectorAll('.subject-checkbox input[type="checkbox"]:checked').length;
            document.getElementById('assignCount').textContent = count + ' selected';
        }
        </script>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>