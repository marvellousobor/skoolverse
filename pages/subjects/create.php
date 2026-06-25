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
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = strtoupper(trim($_POST['subject_code'] ?? ''));
    $class_ids = isset($_POST['class_ids']) ? (array)$_POST['class_ids'] : [];

    if (empty($subject_name)) {
        $error = "Subject name is required.";
    } else {
        $check = $conn->query("SELECT id FROM subjects WHERE LOWER(subject_name) = LOWER('" . $conn->real_escape_string($subject_name) . "')");
        if ($check->num_rows > 0) {
            $error = "Subject name already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code) VALUES (?, ?)");
            $stmt->bind_param("ss", $subject_name, $subject_code);
            if ($stmt->execute()) {
                $subject_id = $conn->insert_id;
                if (!empty($class_ids)) {
                    $ins = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id, is_compulsory) VALUES (?, ?, 1)");
                    foreach ($class_ids as $cid) {
                        $cid = (int)$cid;
                        if ($cid > 0) {
                            $ins->bind_param("ii", $cid, $subject_id);
                            $ins->execute();
                        }
                    }
                }
                $success = "Subject created and assigned to " . count($class_ids) . " class(es)!";
                $subject_name = '';
                $subject_code = '';
                $class_ids = [];
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

$existing = $conn->query("SELECT COUNT(*) as cnt FROM subjects WHERE is_active = 1")->fetch_assoc()['cnt'];
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-book-open"></i> Create Subject</h1>
                <div class="breadcrumb">
                    <a href="index.php">Subjects</a> &rsaquo; New Subject
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
                            <input type="text" name="subject_name" required placeholder="e.g., Mathematics, English Language" value="<?php echo htmlspecialchars($subject_name ?? ''); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Subject Code</label>
                            <input type="text" name="subject_code" placeholder="e.g., MTH, ENG" value="<?php echo htmlspecialchars($subject_code ?? ''); ?>" class="form-control" maxlength="10">
                            <span class="form-hint"><i class="fas fa-info-circle"></i> Optional short code</span>
                        </div>
                        <div class="form-actions" style="margin-top:1.5rem;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Subject</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h2><i class="fas fa-school"></i> Assign to Classes</h2></div>
                    <div class="card-body">
                        <p style="font-size:0.85rem;color:var(--gray-500);margin-bottom:0.75rem;">Select which classes offer this subject:</p>
                        <?php if (empty($classes)): ?>
                            <p style="color:var(--gray-400);font-size:0.85rem;">No classes found. <a href="<?php echo BASE_URL; ?>pages/classes/create.php">Create a class first</a>.</p>
                        <?php else: ?>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:0.5rem;">
                                <?php foreach ($classes as $c): ?>
                                    <label style="display:flex;align-items:center;gap:0.5rem;padding:0.5rem;border:1px solid var(--gray-200);border-radius:6px;cursor:pointer;font-size:0.85rem;">
                                        <input type="checkbox" name="class_ids[]" value="<?php echo $c['id']; ?>"
                                            <?php echo (isset($class_ids) && in_array($c['id'], $class_ids)) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($c['class_name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div style="margin-top:0.75rem;font-size:0.8rem;color:var(--gray-500);">
                            <i class="fas fa-info-circle"></i> You can also assign later from the edit page.
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
