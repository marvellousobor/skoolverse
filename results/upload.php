<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN && $user_role != ROLE_TEACHER) {
    header('Location: ../dashboard.php');
    exit();
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
include_once '../../includes/results_schema.php';
ensure_results_schema($conn);

$error = '';
$success = '';
$warnings = [];

// Get classes/sessions/terms. If teacher, limit to assignments
$classes = [];
$sessions = [];
$terms = [];
if ($user_role == ROLE_ADMIN) {
    $classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
    $sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
    $terms = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);
} else {
    // teacher: find teacher id and assignments
    $tst = $conn->prepare("SELECT id FROM teachers WHERE user_id = ? LIMIT 1");
    $tst->bind_param('i', $user_id);
    $tst->execute();
    $trow = $tst->get_result()->fetch_assoc();
    $teacher_id = $trow ? (int)$trow['id'] : 0;

    if ($teacher_id > 0) {
        $assigns = $conn->query("SELECT DISTINCT class_id, session_id, subject_id FROM teacher_assignments WHERE teacher_id = " . $teacher_id)->fetch_all(MYSQLI_ASSOC);
        $classIds = array_values(array_unique(array_map(function($a){return (int)$a['class_id'];}, $assigns)));
        $sessionIds = array_values(array_unique(array_map(function($a){return (int)$a['session_id'];}, $assigns)));

        if (!empty($classIds)) {
            $in = implode(',', $classIds);
            $classes = $conn->query("SELECT id, class_name FROM classes WHERE id IN ($in) ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
        }
        if (!empty($sessionIds)) {
            $in2 = implode(',', $sessionIds);
            $sessions = $conn->query("SELECT id, session_name FROM sessions WHERE id IN ($in2) ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
            $terms = $conn->query("SELECT id, term_name, session_id FROM terms WHERE session_id IN ($in2) ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// prefill selections from GET or POST
$prefill_class = isset($_REQUEST['class']) ? (int)$_REQUEST['class'] : (isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0);
$prefill_session = isset($_REQUEST['session']) ? (int)$_REQUEST['session'] : (isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0);
$prefill_term = isset($_REQUEST['term']) ? (int)$_REQUEST['term'] : (isset($_POST['term_id']) ? (int)$_POST['term_id'] : 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $class_id = (int)($_POST['class_id'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $term_id = (int)($_POST['term_id'] ?? 0);

    // Validation
    if ($class_id <= 0 || $session_id <= 0 || $term_id <= 0) {
        $error = "Please select class, session, and term.";
    } elseif ($_FILES['csv_file']['error'] != UPLOAD_ERR_OK) {
        $error = "Error uploading file. Please try again.";
    } else {
        $file_tmp = $_FILES['csv_file']['tmp_name'];
        $file_name = $_FILES['csv_file']['name'];

        // Check file type
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($file_ext != 'csv') {
            $error = "Only CSV files are allowed.";
        } else {
            // Process CSV file
            $conn->begin_transaction();
            try {
                $handle = fopen($file_tmp, 'r');
                if (!$handle) {
                    throw new Exception("Unable to open CSV file.");
                }

                // Read header row
                $headers = fgetcsv($handle, 1000, ',');
                if (!$headers || count($headers) < 2) {
                    throw new Exception("Invalid CSV format. First column should be 'Student Name' and remaining columns should be subject names.");
                }

                // First header should be "Student Name" or similar
                $student_col = strtolower(trim($headers[0]));
                if (strpos($student_col, 'student') === false && strpos($student_col, 'name') === false) {
                    throw new Exception("First column must be 'Student Name' or similar.");
                }

                // Get or create subjects from header
                $subject_ids = [];
                for ($i = 1; $i < count($headers); $i++) {
                    $subject_name = trim($headers[$i]);
                    if (empty($subject_name)) {
                        continue;
                    }

                    if (strlen($subject_name) > 15) {
                        throw new Exception("Subject name too long: " . htmlspecialchars($subject_name) . " (max 100 characters)");
                    }

                    // Check if max subjects reached
                    $subject_count = $conn->query("SELECT COUNT(*) as count FROM subjects WHERE is_active = 1")->fetch_assoc()['count'];
                    if ($subject_count >= 15) {
                        throw new Exception("Maximum 15 subjects allowed. Subject '" . htmlspecialchars($subject_name) . "' cannot be added.");
                    }

                    // Get or create subject
                    $stmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(subject_name) = LOWER(?)");
                    $stmt->bind_param("s", $subject_name);
                    $stmt->execute();
                    $subject = $stmt->get_result()->fetch_assoc();

                    if ($subject) {
                        $subject_ids[$i] = $subject['id'];
                    } else {
                        $stmt = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
                        $stmt->bind_param("s", $subject_name);
                        $stmt->execute();
                        $subject_ids[$i] = $conn->insert_id;
                    }
                }

                if (empty($subject_ids)) {
                    throw new Exception("No valid subjects found in CSV header.");
                }

                // Process data rows
                $row_num = 1;
                $total_results = 0;

                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $row_num++;

                    // Skip empty rows
                    if (empty(array_filter($data))) {
                        continue;
                    }

                    $student_name = trim($data[0]);
                    if (empty($student_name)) {
                        $warnings[] = "Row $row_num: Student name is empty. Skipped.";
                        continue;
                    }

                    // Find student by name in the selected class
                    $stmt = $conn->prepare("SELECT id FROM students WHERE class_id = ? AND (CONCAT(first_name, ' ', last_name) LIKE ? OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?)");
                    $search_term = "%$student_name%";
                    $stmt->bind_param("iss", $class_id, $search_term, $search_term);
                    $stmt->execute();
                    $student = $stmt->get_result()->fetch_assoc();

                    if (!$student) {
                        $warnings[] = "Row $row_num: Student '$student_name' not found in selected class. Skipped.";
                        continue;
                    }

                    $student_id = $student['id'];

                    // Process scores for each subject
                    foreach ($subject_ids as $col_index => $subject_id) {
                        $score = isset($data[$col_index]) ? trim($data[$col_index]) : '';

                        if ($score === '' || $score === 'N/A' || strtolower($score) === 'absent') {
                            // Skip empty or absent scores
                            continue;
                        }

                        // Validate score
                        if (!is_numeric($score)) {
                            $warnings[] = "Row $row_num: Invalid score '$score' for subject. Skipped.";
                            continue;
                        }

                        $score = (float)$score;
                        if ($score < 0 || $score > 100) {
                            $warnings[] = "Row $row_num: Score must be between 0 and 100. Got $score. Adjusted to max 100.";
                            $score = min(100, max(0, $score));
                        }

                        // Insert or update result and record uploader when available
                        if (isset($teacher_id) && $teacher_id > 0) {
                            $stmt = $conn->prepare("INSERT INTO student_results (student_id, class_id, session_id, term_id, subject_id, score, uploaded_by_teacher_id) 
                                                  VALUES (?, ?, ?, ?, ?, ?, ?)
                                                  ON DUPLICATE KEY UPDATE score = ?, uploaded_by_teacher_id = ?");
                            $stmt->bind_param("iiiiidiii", $student_id, $class_id, $session_id, $term_id, $subject_id, $score, $teacher_id, $score, $teacher_id);
                        } else {
                            $stmt = $conn->prepare("INSERT INTO student_results (student_id, class_id, session_id, term_id, subject_id, score) 
                                                  VALUES (?, ?, ?, ?, ?, ?)
                                                  ON DUPLICATE KEY UPDATE score = ?");
                            $stmt->bind_param("iiiiidd", $student_id, $class_id, $session_id, $term_id, $subject_id, $score, $score);
                        }
                        $stmt->execute();
                        $total_results++;
                    }
                }

                fclose($handle);

                if ($total_results == 0) {
                    throw new Exception("No valid results found in CSV file.");
                }

                $conn->commit();
                $success = "Successfully uploaded $total_results student results!";

                // Log the upload
                if (count($warnings) > 0) {
                    $success .= " (" . count($warnings) . " warnings)";
                }

            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex items-center gap-4 mb-8">
        <a href="index.php" class="text-indigo-600 hover:text-indigo-700 text-xl">← Back</a>
        <h1 class="text-3xl font-bold">Upload Results CSV</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <strong>Error:</strong> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <strong>Success:</strong> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($warnings)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <strong>Warnings (<?php echo count($warnings); ?>):</strong>
            <ul class="mt-2 text-sm">
                <?php foreach ($warnings as $warning): ?>
                    <li>• <?php echo htmlspecialchars($warning); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Upload Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-8">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Class <span class="text-red-500">*</span></label>
                        <select name="class_id" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?php echo $cls['id']; ?>" <?php echo $prefill_class == $cls['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cls['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Session <span class="text-red-500">*</span></label>
                        <select name="session_id" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select Session --</option>
                            <?php foreach ($sessions as $sess): ?>
                                <option value="<?php echo $sess['id']; ?>" <?php echo $prefill_session == $sess['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sess['session_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Term <span class="text-red-500">*</span></label>
                        <select name="term_id" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select Term --</option>
                            <?php foreach ($terms as $trm): ?>
                                <option value="<?php echo $trm['id']; ?>" <?php echo $prefill_term == $trm['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($trm['term_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">CSV File <span class="text-red-500">*</span></label>
                        <div class="border-2 border-dashed border-gray-300 rounded p-8 text-center hover:bg-gray-50">
                            <input type="file" name="csv_file" accept=".csv" required id="csv_file" class="hidden" />
                            <label for="csv_file" class="cursor-pointer block">
                                <div class="text-4xl mb-2">📁</div>
                                <p class="text-gray-700 font-semibold mb-1">Click to upload CSV file</p>
                                <p class="text-sm text-gray-500">or drag and drop</p>
                                <p class="text-xs text-gray-400 mt-2">Maximum file size: 5MB</p>
                                <p class="text-xs text-gray-600 mt-4" id="file_name"></p>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded font-semibold hover:bg-indigo-700">
                        Upload Results
                    </button>
                </form>
            </div>
        </div>

        <!-- Help Section -->
        <div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-900 mb-4">📋 CSV Format Guide</h3>
                
                <div class="mb-6">
                    <h4 class="font-semibold text-blue-900 mb-2">Header Row:</h4>
                    <p class="text-sm text-blue-800 mb-2">First column: <code class="bg-white px-2 py-1 rounded">Student Name</code></p>
                    <p class="text-sm text-blue-800">Remaining columns: Subject names (max 15)</p>
                </div>

                <div class="mb-6">
                    <h4 class="font-semibold text-blue-900 mb-2">Data Rows:</h4>
                    <div class="bg-white p-3 rounded text-xs text-gray-700 font-mono mb-2">
Student Name,Math,English,Science<br/>
John Doe,85,92,88<br/>
Jane Smith,90,88,95
                    </div>
                </div>

                <div class="mb-4 p-3 bg-white rounded text-sm text-gray-700">
                    <strong class="text-gray-900">Rules:</strong>
                    <ul class="mt-2 space-y-1 text-xs">
                        <li>✓ Student names must match exactly</li>
                        <li>✓ Scores: 0-100 decimal numbers</li>
                        <li>✓ Max 15 subjects per upload</li>
                        <li>✓ Empty cells are skipped</li>
                        <li>✓ 'N/A' or 'Absent' treated as no score</li>
                    </ul>
                </div>

                <a href="download-template.php" class="block text-center bg-green-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-green-700">
                    📥 Download Template
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Drag and drop file upload
const csvInput = document.getElementById('csv_file');
const fileName = document.getElementById('file_name');

csvInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        fileName.textContent = 'Selected: ' + this.files[0].name;
    }
});

// Drag and drop support
const dropZone = document.querySelector('[for="csv_file"]').parentElement;
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('bg-gray-100');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('bg-gray-100');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('bg-gray-100');
    csvInput.files = e.dataTransfer.files;
    if (csvInput.files.length > 0) {
        fileName.textContent = 'Selected: ' + csvInput.files[0].name;
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>
