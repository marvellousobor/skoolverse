<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
include_once '../../includes/results_schema.php';
ensure_results_schema($conn);

$error = '';
$success = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];
$subj = $conn->query("SELECT * FROM subjects WHERE id = $id")->fetch_assoc();
if (!$subj) {
    header("Location: index.php");
    exit;
}

$subject_name = $subj['subject_name'];
$subject_code = $subj['subject_code'];

$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$assigned = $conn->query("SELECT class_id FROM class_subjects WHERE subject_id = $id")->fetch_all(MYSQLI_ASSOC);
$assigned_ids = array_map(function($r) { return $r['class_id']; }, $assigned);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = strtoupper(trim($_POST['subject_code'] ?? ''));
    $class_ids = isset($_POST['class_ids']) ? (array)$_POST['class_ids'] : [];

    if (empty($subject_name)) {
        $error = "Subject name is required.";
    } else {
        $check = $conn->query("SELECT id FROM subjects WHERE LOWER(subject_name) = LOWER('" . $conn->real_escape_string($subject_name) . "') AND id != $id");
        if ($check->num_rows > 0) {
            $error = "Subject name already exists.";
        } else {
            $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, subject_code = ? WHERE id = ?");
            $stmt->bind_param("ssi", $subject_name, $subject_code, $id);
            if ($stmt->execute()) {
                $conn->query("DELETE FROM class_subjects WHERE subject_id = $id");
                if (!empty($class_ids)) {
                    $ins = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id, is_compulsory) VALUES (?, ?, 1)");
                    foreach ($class_ids as $cid) {
                        $cid = (int)$cid;
                        if ($cid > 0) {
                            $ins->bind_param("ii", $cid, $id);
                            $ins->execute();
                        }
                    }
                }
                $success = "Subject updated successfully!";
                $assigned_ids = array_map('intval', $class_ids);
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-book-open"></i> Edit Subject</h1>
                <div class="breadcrumb">
                    <a href="index.php">Subjects</a> &rsaquo; Edit
                </div>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="content-grid">
                <div class="card">
                    <div class="card-header"><h2><i class="fas fa-pen-to-square"></i> Subject Details</h2></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Subject Name <span style="color:var(--danger-color);">*</span></label>
                            <input type="text" name="subject_name" required placeholder="e.g., Mathematics" value="<?php echo htmlspecialchars($subject_name); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Subject Code</label>
                            <input type="text" name="subject_code" placeholder="e.g., MTH" value="<?php echo htmlspecialchars($subject_code); ?>" class="form-control" maxlength="10">
                        </div>
                        <div class="form-actions" style="margin-top:1.5rem;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h2><i class="fas fa-school"></i> Assign to Classes</h2></div>
                    <div class="card-body">
                        <p style="font-size:0.85rem;color:var(--gray-500);margin-bottom:0.75rem;">Select which classes offer this subject:</p>
                        <?php if (empty($classes)): ?>
                            <p style="color:var(--gray-400);font-size:0.85rem;">No classes found.</p>
                        <?php else: ?>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:0.5rem;">
                                <?php foreach ($classes as $c): ?>
                                    <label style="display:flex;align-items:center;gap:0.5rem;padding:0.5rem;border:1px solid var(--gray-200);border-radius:6px;cursor:pointer;font-size:0.85rem;<?php echo in_array($c['id'], $assigned_ids) ? 'background:#eff6ff;border-color:#93c5fd;' : ''; ?>">
                                        <input type="checkbox" name="class_ids[]" value="<?php echo $c['id']; ?>"
                                            <?php echo in_array($c['id'], $assigned_ids) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($c['class_name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
