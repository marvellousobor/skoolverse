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

// Get all classes, sessions, and terms
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Get filter parameters
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$session_filter = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$term_filter = isset($_GET['term']) ? (int)$_GET['term'] : 0;

// Get subjects
$subjects = $conn->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);

// Build query for results summary
$where = "1=1";
$params = [];
$types = "";

if ($class_filter > 0) {
    $where .= " AND sr.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

if ($session_filter > 0) {
    $where .= " AND sr.session_id = ?";
    $params[] = $session_filter;
    $types .= "i";
}

if ($term_filter > 0) {
    $where .= " AND sr.term_id = ?";
    $params[] = $term_filter;
    $types .= "i";
}

$sql = "SELECT 
    COUNT(DISTINCT sr.student_id) as total_students,
    COUNT(DISTINCT sr.subject_id) as total_subjects,
    sr.class_id, sr.session_id, sr.term_id,
    c.class_name, s.session_name, t.term_name
FROM student_results sr
LEFT JOIN classes c ON sr.class_id = c.id
LEFT JOIN sessions s ON sr.session_id = s.id
LEFT JOIN terms t ON sr.term_id = t.id
WHERE $where
GROUP BY sr.class_id, sr.session_id, sr.term_id, c.class_name, s.session_name, t.term_name
ORDER BY s.id DESC, t.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results_summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Results Management</h1>
        <div class="flex gap-2">
            <a href="upload.php" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                📤 Upload CSV
            </a>
            <a href="download-template.php" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                📥 Download Template
            </a>
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

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Class</label>
                <select name="class" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?php echo $cls['id']; ?>" <?php echo $class_filter == $cls['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cls['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Session</label>
                <select name="session" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $sess): ?>
                        <option value="<?php echo $sess['id']; ?>" <?php echo $session_filter == $sess['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sess['session_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Term</label>
                <select name="term" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Terms</option>
                    <?php foreach ($terms as $trm): ?>
                        <option value="<?php echo $trm['id']; ?>" <?php echo $term_filter == $trm['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($trm['term_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                    Filter
                </button>
                <a href="index.php" class="bg-gray-400 text-white px-6 py-2 rounded hover:bg-gray-500">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Results Summary -->
    <?php if (empty($results_summary)): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded text-center">
            No results found. <a href="upload.php" class="font-bold underline">Upload results now</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($results_summary as $summary): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-indigo-50 px-6 py-4 border-b">
                        <h3 class="text-lg font-bold text-gray-900">
                            <?php echo htmlspecialchars($summary['class_name'] ?? 'N/A'); ?> - 
                            <?php echo htmlspecialchars($summary['session_name'] ?? 'N/A'); ?> - 
                            <?php echo htmlspecialchars($summary['term_name'] ?? 'N/A'); ?>
                        </h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-blue-50 p-4 rounded">
                                <p class="text-sm text-gray-600 mb-1">Total Students</p>
                                <p class="text-2xl font-bold text-blue-600"><?php echo $summary['total_students']; ?></p>
                            </div>
                            <div class="bg-green-50 p-4 rounded">
                                <p class="text-sm text-gray-600 mb-1">Subjects</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo $summary['total_subjects']; ?></p>
                            </div>
                        </div>
                        <a href="view.php?class=<?php echo $summary['class_id']; ?>&session=<?php echo $summary['session_id']; ?>&term=<?php echo $summary['term_id']; ?>" 
                           class="block text-center bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            View & Edit Results
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../../includes/footer.php'; ?>
