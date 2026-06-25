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

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$error = '';
$success = '';

$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);

if (!$class_id && !empty($classes)) {
    $class_id = (int)$classes[0]['id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $class_id > 0) {
    $subject_ids = isset($_POST['subject_ids']) ? (array)$_POST['subject_ids'] : [];
    $compulsory = isset($_POST['compulsory']) ? (array)$_POST['compulsory'] : [];

    $conn->query("DELETE FROM class_subjects WHERE class_id = $class_id");
    if (!empty($subject_ids)) {
        $ins = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id, is_compulsory) VALUES (?, ?, ?)");
        foreach ($subject_ids as $sid) {
            $sid = (int)$sid;
            if ($sid > 0) {
                $comp = in_array($sid, $compulsory) ? 1 : 0;
                $ins->bind_param("iii", $class_id, $sid, $comp);
                $ins->execute();
            }
        }
    }
    $success = "Subjects updated for this class!";
}

$class_name = '';
if ($class_id > 0) {
    $cn = $conn->query("SELECT class_name FROM classes WHERE id = $class_id")->fetch_assoc();
    $class_name = $cn ? $cn['class_name'] : '';
}

$all_subjects = $conn->query("SELECT id, subject_name, subject_code FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
$assigned = $conn->query("SELECT subject_id, is_compulsory FROM class_subjects WHERE class_id = $class_id")->fetch_all(MYSQLI_ASSOC);
$assigned_ids = [];
$compulsory_ids = [];
foreach ($assigned as $a) {
    $assigned_ids[] = (int)$a['subject_id'];
    if ($a['is_compulsory']) $compulsory_ids[] = (int)$a['subject_id'];
}
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-school"></i> Class Subjects</h1>
                <span class="breadcrumb">Manage which subjects each class offers</span>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Classes</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Select Class</h2>
            </div>
            <div class="card-body">
                <form method="GET" class="form-row">
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $class_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($class_id > 0): ?>
        <form method="POST">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-book-open"></i> Subjects for <?php echo htmlspecialchars($class_name); ?></h2>
                    <span style="font-size:0.8rem;color:var(--gray-500);"><?php echo count($all_subjects); ?> available subjects</span>
                </div>
                <div class="card-body">
                    <?php if (empty($all_subjects)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <p>No subjects created yet. <a href="<?php echo BASE_URL; ?>pages/subjects/create.php">Create subjects first</a>.</p>
                        </div>
                    <?php else: ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:0.5rem;">
                            <?php foreach ($all_subjects as $s): ?>
                                <?php $checked = in_array($s['id'], $assigned_ids); ?>
                                <label style="display:flex;align-items:center;gap:0.5rem;padding:0.6rem 0.75rem;border:1px solid <?php echo $checked ? '#93c5fd' : 'var(--gray-200)'; ?>;border-radius:8px;cursor:pointer;font-size:0.85rem;background:<?php echo $checked ? '#eff6ff' : 'transparent'; ?>;">
                                    <input type="checkbox" name="subject_ids[]" value="<?php echo $s['id']; ?>"
                                        onchange="this.parentElement.style.borderColor=this.checked?'#93c5fd':'var(--gray-200)';this.parentElement.style.background=this.checked?'#eff6ff':'transparent';"
                                        <?php echo $checked ? 'checked' : ''; ?>>
                                    <div style="flex:1;">
                                        <strong><?php echo htmlspecialchars($s['subject_name']); ?></strong>
                                        <?php if ($s['subject_code']): ?>
                                            <span style="font-size:0.75rem;color:var(--gray-400);">(<?php echo htmlspecialchars($s['subject_code']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <label style="font-size:0.75rem;color:var(--gray-500);display:flex;align-items:center;gap:0.25rem;cursor:pointer;">
                                        <input type="checkbox" name="compulsory[]" value="<?php echo $s['id']; ?>" <?php echo in_array($s['id'], $compulsory_ids) ? 'checked' : ''; ?>>
                                        Compulsory
                                    </label>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-actions" style="margin-top:1.5rem;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Assignments</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
