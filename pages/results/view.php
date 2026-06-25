<?php
include_once '../../includes/auth_check.php';
$class_id = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$session_id = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$term_id = isset($_GET['term']) ? (int)$_GET['term'] : 0;
$teacher_filter = isset($_GET['teacher']) ? (int)$_GET['teacher'] : 0;

if ($class_id <= 0 || $session_id <= 0 || $term_id <= 0) {
    header('Location: index.php');
    exit();
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

// Access control: admins or teachers assigned to this class/session
$hasAccess = false;
if ($user_role == ROLE_ADMIN) {
    $hasAccess = true;
} elseif ($user_role == ROLE_TEACHER) {
    $tq = $conn->prepare("SELECT t.id FROM teachers t JOIN teacher_assignments ta ON ta.teacher_id = t.id WHERE t.user_id = ? AND ta.class_id = ? AND ta.session_id = ? LIMIT 1");
    $tq->bind_param('iii', $user_id, $class_id, $session_id);
    $tq->execute();
    if ($tq->get_result()->num_rows > 0) $hasAccess = true;
}

if (!$hasAccess) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';
$success = '';

// Get class, session, and term info
$class = $conn->query("SELECT class_name FROM classes WHERE id = $class_id")->fetch_assoc();
$session = $conn->query("SELECT session_name FROM sessions WHERE id = $session_id")->fetch_assoc();
$term = $conn->query("SELECT term_name FROM terms WHERE id = $term_id")->fetch_assoc();

if (!$class || !$session || !$term) {
    header('Location: index.php');
    exit();
}

// Handle score updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_score'])) {
    $result_id = (int)$_POST['result_id'];
    $ca = isset($_POST['ca']) && trim($_POST['ca']) !== '' ? (float)$_POST['ca'] : null;
    $exam = isset($_POST['exam']) && trim($_POST['exam']) !== '' ? (float)$_POST['exam'] : null;

    if ($result_id > 0) {
        if ($ca === null && $exam === null) {
            $stmt = $conn->prepare("DELETE FROM student_results WHERE id = ? AND class_id = ? AND session_id = ? AND term_id = ?");
            $stmt->bind_param("iiii", $result_id, $class_id, $session_id, $term_id);
            if ($stmt->execute()) {
                $success = "Score removed successfully!";
            } else {
                $error = "Error removing score: " . $conn->error;
            }
        } else {
            $total = ($ca ?: 0) + ($exam ?: 0);
            if ($total < 0 || $total > 100) {
                $error = "Total score must be between 0 and 100.";
            } else {
                $stmt = $conn->prepare("UPDATE student_results SET score = ?, ca_score = ?, exam_score = ?, is_published = 0, published_by_admin_id = NULL, published_at = NULL WHERE id = ? AND class_id = ? AND session_id = ? AND term_id = ?");
                $stmt->bind_param("ddiiii", $total, $ca, $exam, $result_id, $class_id, $session_id, $term_id);
                if ($stmt->execute()) {
                    $success = "Score updated successfully!";
                } else {
                    $error = "Error updating score: " . $conn->error;
                }
            }
        }
    }
}

// Handle delete result row
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $result_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM student_results WHERE id = ? AND class_id = ? AND session_id = ? AND term_id = ?");
    $stmt->bind_param("iiii", $result_id, $class_id, $session_id, $term_id);
    if ($stmt->execute()) {
        $success = "Result deleted successfully!";
    } else {
        $error = "Error deleting result: " . $conn->error;
    }
}

// Get all students in this class
$students = $conn->query("SELECT id, first_name, last_name, middle_name FROM students WHERE class_id = $class_id ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);

// Get subjects assigned to this class (or fallback to those with results)
$subjects = $conn->query("
    SELECT s.id as subject_id, s.subject_name, s.subject_code
    FROM subjects s
    JOIN class_subjects cs ON cs.subject_id = s.id AND cs.class_id = $class_id
    WHERE s.is_active = 1
    ORDER BY s.subject_name
")->fetch_all(MYSQLI_ASSOC);
if (empty($subjects)) {
    // Fallback: show subjects with results
    $subjects = $conn->query("
        SELECT DISTINCT sr.subject_id, s.subject_name, s.subject_code
        FROM student_results sr
        JOIN subjects s ON sr.subject_id = s.id
        WHERE sr.class_id = $class_id AND sr.session_id = $session_id AND sr.term_id = $term_id
        ORDER BY s.subject_name
    ")->fetch_all(MYSQLI_ASSOC);
}

// Get all results (optionally filtered by teacher)
$teacher_where = '';
if ($teacher_filter > 0) {
    $teacher_where = " AND sr.uploaded_by_teacher_id = $teacher_filter";
}
$results = $conn->query("
    SELECT sr.id, sr.student_id, sr.subject_id, sr.score, sr.ca_score, sr.exam_score, sr.is_published, st.subject_name
    FROM student_results sr
    JOIN subjects st ON sr.subject_id = st.id
    WHERE sr.class_id = $class_id AND sr.session_id = $session_id AND sr.term_id = $term_id $teacher_where
    ORDER BY sr.student_id, st.subject_name
")->fetch_all(MYSQLI_ASSOC);

// Build results map for easy access
$results_map = [];
foreach ($results as $result) {
    $results_map[$result['student_id']][$result['subject_id']] = $result;
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex items-center gap-4 mb-8">
        <a href="<?php echo ($user_role == ROLE_ADMIN) ? 'index.php' : (BASE_URL . 'pages/teachers/dashboard.php'); ?>" class="text-indigo-600 hover:text-indigo-700 text-xl">← Back</a>
        <div>
            <h1 class="text-3xl font-bold">View & Edit Results</h1>
            <p class="text-gray-600 mt-2">
                <strong><?php echo htmlspecialchars($class['class_name']); ?></strong> - 
                <?php echo htmlspecialchars($session['session_name']); ?> - 
                <?php echo htmlspecialchars($term['term_name']); ?>
            </p>
        </div>
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

    <?php if (empty($subjects) || empty($students)): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
            No results found for this selection. <a href="upload.php" class="font-bold underline">Upload results now</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 sticky left-0 bg-gray-100 z-10">Student Name</th>
                        <?php foreach ($subjects as $subject): ?>
                            <th class="px-2 py-3 text-center text-sm font-semibold text-gray-900 bg-indigo-50" colspan="3">
                                <div class="truncate" title="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                    <?php echo htmlspecialchars(substr($subject['subject_name'], 0, 10)); ?>
                                </div>
                                <div class="text-xs text-gray-600 flex justify-center gap-1" style="font-size:0.6rem;">
                                    <span>CA</span><span>Ex</span><span>Tot</span>
                                </div>
                            </th>
                        <?php endforeach; ?>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900">Average</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php
                        $name = trim($student['first_name'] . ' ' . $student['last_name']);
                        if (!empty($student['middle_name'])) {
                            $name = trim($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']);
                        }
                        
                        // Calculate average
                        $scores = [];
                        if (isset($results_map[$student['id']])) {
                            foreach ($results_map[$student['id']] as $result) {
                                $scores[] = $result['score'];
                            }
                        }
                        $average = !empty($scores) ? array_sum($scores) / count($scores) : 0;
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-900 sticky left-0 bg-white hover:bg-gray-50">
                                <?php echo htmlspecialchars($name); ?>
                            </td>
                            <?php foreach ($subjects as $subject): ?>
                                <?php if (isset($results_map[$student['id']][$subject['subject_id']])): ?>
                                    <?php $result = $results_map[$student['id']][$subject['subject_id']]; ?>
                                    <td class="px-1 py-3 text-center" style="padding:0.25rem;">
                                        <form method="POST" class="inline-flex items-center gap-0_5" style="display:flex;align-items:center;gap:2px;justify-content:center;">
                                            <input type="hidden" name="result_id" value="<?php echo $result['id']; ?>">
                                            <input type="hidden" name="update_score" value="1">
                                            <input type="number" name="ca" value="<?php echo $result['ca_score'] !== null ? $result['ca_score'] : ''; ?>"
                                                   min="0" max="40" step="0.01" placeholder="CA"
                                                   style="width:40px;padding:2px 3px;border:1px solid var(--gray-300);border-radius:4px;text-align:center;font-size:0.75rem;" />
                                            <input type="number" name="exam" value="<?php echo $result['exam_score'] !== null ? $result['exam_score'] : ''; ?>"
                                                   min="0" max="70" step="0.01" placeholder="Ex"
                                                   style="width:40px;padding:2px 3px;border:1px solid var(--gray-300);border-radius:4px;text-align:center;font-size:0.75rem;" />
                                            <span style="font-weight:600;font-size:0.8rem;width:28px;text-align:center;"><?php echo $result['score']; ?></span>
                                            <button type="submit" title="Save" style="background:none;border:none;color:var(--success-color);cursor:pointer;font-size:0.8rem;padding:2px;">✓</button>
                                        </form>
                                    </td>
                                <?php else: ?>
                                    <td class="px-1 py-3 text-center" style="padding:0.25rem;">
                                        <span class="text-gray-400" style="font-size:0.8rem;">—</span>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <td class="px-4 py-3 text-center font-semibold text-indigo-600">
                                <?php echo $average > 0 ? number_format($average, 2) : '—'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">📋 Summary</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• <strong><?php echo count($students); ?></strong> students in class</li>
                    <li>• <strong><?php echo count($subjects); ?></strong> subjects</li>
                    <li>• <strong><?php echo count($results); ?></strong> total scores entered</li>
                </ul>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="font-semibold text-green-900 mb-2">✓ Tips</h3>
                <ul class="text-sm text-green-800 space-y-1">
                    <li>• Click on score to edit</li>
                    <li>• Leave blank and save to delete</li>
                    <li>• Average calculated automatically</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../../includes/footer.php'; ?>