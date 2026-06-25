<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN && $user_role != 'teacher') {
    header('Location: ../dashboard.php');
    exit();
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

$error = '';
$success = '';
$is_admin = ($user_role == ROLE_ADMIN);
$is_teacher = ($user_role == 'teacher');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}
$id = (int)$_GET['id'];

$announcement = $conn->query("SELECT * FROM announcements WHERE id = $id")->fetch_assoc();
if (!$announcement) {
    header('Location: index.php');
    exit();
}

// Teachers can only edit their own announcements
if ($is_teacher && $announcement['posted_by_user_id'] != $user_id) {
    header('Location: index.php');
    exit();
}

// Get teacher info if teacher
$teacher_info = null;
$teacher_classes = [];
if ($is_teacher) {
    $t = $conn->query("SELECT id, full_name FROM teachers WHERE user_id = $user_id")->fetch_assoc();
    if ($t) {
        $teacher_info = $t;
        $tcls = $conn->query("SELECT DISTINCT c.id, c.class_name FROM teacher_assignments ta JOIN classes c ON c.id = ta.class_id WHERE ta.teacher_id = {$t['id']} AND ta.session_id = (SELECT id FROM sessions ORDER BY created_at DESC LIMIT 1)")->fetch_all(MYSQLI_ASSOC);
        foreach ($tcls as $tc) {
            $teacher_classes[$tc['id']] = $tc['class_name'];
        }
    }
}

$poster_roles = [
    'admin'    => 'Admin',
    'principal'    => 'Principal',
    'vice_principal' => 'Vice Principal',
    'teacher'  => 'Teacher',
    'bursar'   => 'Bursar',
    'other'    => 'Other',
];

$all_classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $content       = trim($_POST['content'] ?? '');
    $target_type   = $_POST['target_type'] ?? 'all';
    $target_class_id = !empty($_POST['target_class_id']) ? (int)$_POST['target_class_id'] : null;

    if ($is_admin) {
        $poster_role = trim($_POST['poster_role'] ?? 'admin');
        $custom_name = trim($_POST['custom_name'] ?? '');
    }

    if (empty($title)) {
        $error = 'Title is required.';
    } elseif ($is_admin && $poster_role === 'other' && empty($custom_name)) {
        $error = 'Please enter the custom name.';
    } elseif ($target_type === 'class' && !$target_class_id) {
        $error = 'Please select a target class.';
    } else {
        if ($is_admin) {
            $role_labels = [
                'admin'    => 'Admin',
                'principal'    => 'Principal',
                'vice_principal' => 'Vice Principal',
                'teacher'  => 'Teacher',
                'bursar'   => 'Bursar',
            ];
            $posted_by_name = $poster_role === 'other' ? $custom_name : ($role_labels[$poster_role] ?? 'Admin');
        } else {
            $posted_by_name = $teacher_info ? $teacher_info['full_name'] : 'Teacher';
            $poster_role = 'teacher';
        }

        $final_target = $target_type === 'class' ? $target_class_id : null;

        if ($target_type === 'class' && $final_target && $is_teacher && !isset($teacher_classes[$final_target])) {
            $error = 'You can only post to your assigned classes.';
        }

        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, posted_by_name = ?, posted_by_role = ?, target_class_id = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $title, $content, $posted_by_name, $poster_role, $final_target, $id);
            if ($stmt->execute()) {
                $success = 'Announcement updated.';
                $announcement['title'] = $title;
                $announcement['content'] = $content;
                $announcement['posted_by_name'] = $posted_by_name;
                $announcement['posted_by_role'] = $poster_role;
                $announcement['target_class_id'] = $final_target;
            } else {
                $error = 'Failed to update.';
            }
        }
    }
}
?>
<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-edit"></i> Edit Announcement</h1>
                <div class="breadcrumb">
                    <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Announcements</a>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-pen"></i> Announcement Details</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Title <span class="req">*</span></label>
                        <input type="text" name="title" required class="form-control" value="<?php echo htmlspecialchars($announcement['title']); ?>" />
                    </div>

                    <div class="form-group">
                        <label>Content <span class="req">*</span></label>
                        <textarea name="content" required class="form-control" rows="8" style="resize:vertical;"><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                    </div>

                    <?php if ($is_admin): ?>
                        <div class="form-section">
                            <div class="form-section-title">Posted By</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Role <span class="req">*</span></label>
                                    <select name="poster_role" id="poster_role" class="form-control" onchange="toggleCustomNameField()">
                                        <?php foreach ($poster_roles as $val => $label): ?>
                                            <option value="<?php echo $val; ?>" <?php echo $announcement['posted_by_role'] === $val ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" id="custom_name_group" style="display:none;">
                                    <label>Custom Name <span class="req">*</span></label>
                                    <input type="text" name="custom_name" class="form-control" value="<?php echo htmlspecialchars($announcement['posted_by_role'] === 'other' ? $announcement['posted_by_name'] : ''); ?>" />
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-section">
                            <div class="form-section-title">Posted By</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($announcement['posted_by_name']); ?>" readonly disabled style="color:var(--gray-700);" />
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <input type="text" class="form-control" value="Teacher" readonly disabled style="color:var(--gray-700);" />
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-section">
                        <div class="form-section-title">Target Audience</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Audience <span class="req">*</span></label>
                                <select name="target_type" id="target_type" class="form-control" onchange="toggleTargetClass()">
                                    <option value="all" <?php echo !$announcement['target_class_id'] ? 'selected' : ''; ?>>Everyone</option>
                                    <option value="class" <?php echo $announcement['target_class_id'] ? 'selected' : ''; ?>>Specific Class Only</option>
                                </select>
                            </div>
                            <div class="form-group" id="target_class_group" style="display:none;">
                                <label>Class <span class="req">*</span></label>
                                <select name="target_class_id" class="form-control">
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($all_classes as $cls):
                                        if ($is_teacher && !isset($teacher_classes[$cls['id']])) continue;
                                    ?>
                                        <option value="<?php echo $cls['id']; ?>" <?php echo $announcement['target_class_id'] == $cls['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cls['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Announcement
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function toggleCustomNameField() {
    const sel = document.getElementById('poster_role');
    const group = document.getElementById('custom_name_group');
    const input = group ? group.querySelector('input') : null;
    if (sel && group) {
        group.style.display = sel.value === 'other' ? 'block' : 'none';
        if (input) input.required = sel.value === 'other';
    }
}

function toggleTargetClass() {
    const sel = document.getElementById('target_type');
    const group = document.getElementById('target_class_group');
    const select = group ? group.querySelector('select') : null;
    if (group) {
        group.style.display = sel.value === 'class' ? 'block' : 'none';
        if (select) select.required = sel.value === 'class';
    }
}

toggleCustomNameField();
toggleTargetClass();
</script>

<?php include_once '../../includes/footer.php'; ?>
