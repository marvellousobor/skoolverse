<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

include_once '../../includes/results_schema.php';
ensure_results_schema($conn);

$error = '';
$success = '';

$teachers = $conn->query("SELECT id, full_name FROM teachers ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Build class -> subjects mapping for JS
$class_subjects_map = [];
$cs_result = $conn->query("SELECT class_id, subject_id FROM class_subjects")->fetch_all(MYSQLI_ASSOC);
foreach ($cs_result as $row) {
    $class_subjects_map[(int)$row['class_id']][] = (int)$row['subject_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);

    if ($teacher_id <= 0 || $class_id <= 0 || $subject_id <= 0 || $session_id <= 0) {
        $error = 'All fields are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, subject_id, class_id, session_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiii', $teacher_id, $subject_id, $class_id, $session_id);
        if ($stmt->execute()) {
            $success = 'Assignment created.';
        } else {
            $error = 'Error: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - SPMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        const classSubjects = <?php echo json_encode($class_subjects_map); ?>;
        const allSubjects = <?php echo json_encode($subjects); ?>;

        function filterSubjects() {
            const classSelect = document.getElementById('class_id');
            const subjectSelect = document.getElementById('subject_id');
            const classId = parseInt(classSelect.value);
            
            subjectSelect.innerHTML = '<option value="">-- Select --</option>';
            
            let allowedIds = classSubjects[classId] || [];
            let showAll = allowedIds.length === 0;
            
            allSubjects.forEach(function(s) {
                if (showAll || allowedIds.includes(parseInt(s.id))) {
                    var opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.subject_name;
                    subjectSelect.appendChild(opt);
                }
            });
        }
    </script>
</head>
<body class="bg-gray-50">
    <?php include_once '../../includes/navbar.php'; ?>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-4">Create Teacher Assignment</h1>

        <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <form method="POST" class="bg-white rounded shadow p-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold">Teacher</label>
                <select name="teacher_id" class="w-full border border-gray-300 rounded px-3 py-2" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($teachers as $t): ?><option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold">Class</label>
                <select name="class_id" id="class_id" class="w-full border border-gray-300 rounded px-3 py-2" required onchange="filterSubjects()">
                    <option value="">-- Select --</option>
                    <?php foreach ($classes as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold">Subject</label>
                <select name="subject_id" id="subject_id" class="w-full border border-gray-300 rounded px-3 py-2" required>
                    <option value="">-- Select class first --</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Only subjects assigned to the selected class are shown.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold">Session</label>
                <select name="session_id" class="w-full border border-gray-300 rounded px-3 py-2" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($sessions as $se): ?><option value="<?php echo $se['id']; ?>"><?php echo htmlspecialchars($se['session_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <button class="bg-indigo-600 text-white px-4 py-2 rounded">Create</button>
                <a href="index.php" class="ml-2 text-sm text-gray-600">Cancel</a>
            </div>
        </form>
    </div>
    <?php include_once '../../includes/footer.php'; ?>
</body>
</html>
