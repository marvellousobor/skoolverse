<?php include_once '../../includes/auth_check.php'; ?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<?php
// Only admins can view this page
if ($user_role != ROLE_ADMIN) {
    header('Location: ../dashboard.php');
    exit();
}

include_once '../../includes/results_schema.php';
ensure_results_schema($conn);

$error = '';
$success = '';

// Get all classes, sessions, and terms
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
$term_id = isset($_POST['term_id']) ? (int)$_POST['term_id'] : 0;
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;

$students = [];
$subjects = [];

// Get students if class is selected
if ($class_id > 0) {
    $students = $conn->query("SELECT id, first_name, last_name, middle_name FROM students WHERE class_id = $class_id AND is_active = 1 ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);
}

// Get subjects
$subjects = $conn->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);

// Handle score submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_score'])) {
    $score = isset($_POST['score']) && trim($_POST['score']) !== '' ? (float)$_POST['score'] : null;

    if ($student_id <= 0 || $class_id <= 0 || $session_id <= 0 || $term_id <= 0) {
        $error = "Invalid selection. Please select all fields.";
    } elseif ($score !== null && ($score < 0 || $score > 100)) {
        $error = "Score must be between 0 and 100.";
    } else {
        if ($score === null) {
            // Delete result if score is blank
            $stmt = $conn->prepare("DELETE FROM student_results WHERE student_id = ? AND class_id = ? AND session_id = ? AND term_id = ? AND subject_id IN (SELECT id FROM subjects WHERE is_active = 1)");
            $stmt->bind_param("iiii", $student_id, $class_id, $session_id, $term_id);
            $stmt->execute();
        } else {
            // Insert or update result
            $subject_ids = array_column($subjects, 'id');
            foreach ($subject_ids as $subject_id) {
                $subject_score = isset($_POST["score_$subject_id"]) && trim($_POST["score_$subject_id"]) !== '' ? (float)$_POST["score_$subject_id"] : null;
                
                if ($subject_score !== null && $subject_score >= 0 && $subject_score <= 100) {
                    $stmt = $conn->prepare("INSERT INTO student_results (student_id, class_id, session_id, term_id, subject_id, score) 
                                          VALUES (?, ?, ?, ?, ?, ?)
                                          ON DUPLICATE KEY UPDATE score = ?");
                    $stmt->bind_param("iiiiidod", $student_id, $class_id, $session_id, $term_id, $subject_id, $subject_score, $subject_score);
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
    $result = $conn->query("
        SELECT sr.id, sr.subject_id, sr.score
        FROM student_results sr
        WHERE sr.student_id = $student_id AND sr.class_id = $class_id AND sr.session_id = $session_id AND sr.term_id = $term_id
    ")->fetch_all(MYSQLI_ASSOC);
    
    foreach ($result as $row) {
        $student_results[$row['subject_id']] = $row['score'];
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex items-center gap-4 mb-8">
        <a href="index.php" class="text-indigo-600 hover:text-indigo-700 text-xl">← Back</a>
        <h1 class="text-3xl font-bold">Enter Student Scores</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-8">
                <form method="POST" id="scoreForm">
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Class <span class="text-red-500">*</span></label>
                        <select name="class_id" required onchange="document.getElementById('scoreForm').submit();" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?php echo $cls['id']; ?>" <?php echo $class_id == $cls['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cls['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($class_id > 0): ?>
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Session <span class="text-red-500">*</span></label>
                            <select name="session_id" required onchange="document.getElementById('scoreForm').submit();" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">-- Select Session --</option>
                                <?php foreach ($sessions as $sess): ?>
                                    <option value="<?php echo $sess['id']; ?>" <?php echo $session_id == $sess['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sess['session_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Term <span class="text-red-500">*</span></label>
                            <select name="term_id" required onchange="document.getElementById('scoreForm').submit();" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">-- Select Term --</option>
                                <?php foreach ($terms as $trm): ?>
                                    <option value="<?php echo $trm['id']; ?>" <?php echo $term_id == $trm['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($trm['term_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Student <span class="text-red-500">*</span></label>
                            <select name="student_id" required onchange="document.getElementById('scoreForm').submit();" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
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

                        <?php if ($student_id > 0 && !empty($subjects)): ?>
                            <div class="bg-gray-50 p-6 rounded mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Enter Scores for <?php echo count($subjects); ?> Subjects</h3>
                                <div class="space-y-4">
                                    <?php foreach ($subjects as $subject): ?>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                            </label>
                                            <input type="number" 
                                                   name="score_<?php echo $subject['id']; ?>" 
                                                   value="<?php echo isset($student_results[$subject['id']]) ? $student_results[$subject['id']] : ''; ?>" 
                                                   min="0" 
                                                   max="100" 
                                                   step="0.01"
                                                   placeholder="0-100"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button type="submit" name="save_score" value="1" class="w-full bg-indigo-600 text-white px-6 py-3 rounded font-semibold hover:bg-indigo-700">
                                Save Scores
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-900 mb-4">💡 Tips</h3>
                
                <div class="space-y-4 text-sm text-blue-800">
                    <div>
                        <h4 class="font-semibold text-blue-900 mb-1">Method 1: Manual Entry</h4>
                        <p>Enter scores one at a time for each student.</p>
                    </div>

                    <div>
                        <h4 class="font-semibold text-blue-900 mb-1">Method 2: Bulk Upload</h4>
                        <p><a href="upload.php" class="underline font-semibold">Upload CSV file</a> for faster data entry.</p>
                    </div>

                    <div>
                        <h4 class="font-semibold text-blue-900 mb-1">Valid Scores</h4>
                        <p>Decimal numbers between 0 and 100</p>
                    </div>

                    <div>
                        <h4 class="font-semibold text-blue-900 mb-1">Max Subjects</h4>
                        <p>Maximum 15 subjects per class</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
