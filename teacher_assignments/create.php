<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

$teachers = $conn->query("SELECT id, full_name FROM teachers ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

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
                <select name="class_id" class="w-full border border-gray-300 rounded px-3 py-2" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($classes as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold">Subject</label>
                <select name="subject_id" class="w-full border border-gray-300 rounded px-3 py-2" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($subjects as $s): ?><option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_name']); ?></option><?php endforeach; ?>
                </select>
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
