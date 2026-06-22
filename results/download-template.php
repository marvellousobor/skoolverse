<?php include_once '../../includes/auth_check.php'; ?>

<?php
// Only admins can download templates
if ($user_role != ROLE_ADMIN) {
    header('Location: ../dashboard.php');
    exit();
}

include_once '../../includes/results_schema.php';
ensure_results_schema($conn);

// Get parameters
$class_id = isset($_GET['class']) ? (int)$_GET['class'] : 0;

// If no class selected, show selection page
if ($class_id <= 0) {
    include_once '../../includes/header.php';
    include_once '../../includes/navbar.php';
    ?>
    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center gap-4 mb-8">
            <a href="index.php" class="text-indigo-600 hover:text-indigo-700 text-xl">← Back</a>
            <h1 class="text-3xl font-bold">Download Template CSV</h1>
        </div>

        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-8">
            <p class="text-gray-700 mb-6">Select a class to download the template with all students in that class.</p>
            
            <form method="GET" class="space-y-4">
                <?php
                $classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
                ?>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Class</label>
                    <select name="class" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?php echo $cls['id']; ?>">
                                <?php echo htmlspecialchars($cls['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Subjects (optional - leave blank for default template)
                    </label>
                    <p class="text-sm text-gray-600 mb-3">Select subjects to include in template (max 15):</p>
                    
                    <?php
                    $subjects = $conn->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
                    ?>

                    <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-300 p-4 rounded">
                        <?php if (empty($subjects)): ?>
                            <p class="text-sm text-gray-600">No subjects created yet. Create subjects via uploads first.</p>
                        <?php else: ?>
                            <?php foreach ($subjects as $subject): ?>
                                <label class="flex items-center">
                                    <input type="checkbox" name="subjects[]" value="<?php echo $subject['id']; ?>" class="mr-2">
                                    <span class="text-gray-700"><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="w-full bg-green-600 text-white px-6 py-3 rounded font-semibold hover:bg-green-700">
                    📥 Download Template
                </button>
            </form>
        </div>
    </div>

    <?php
    include_once '../../includes/footer.php';
} else {
    // Download template CSV
    
    // Get class name
    $class = $conn->query("SELECT class_name FROM classes WHERE id = $class_id")->fetch_assoc();
    if (!$class) {
        header('Location: index.php');
        exit();
    }

    // Get selected subjects or use default
    $selected_subjects = isset($_GET['subjects']) ? (array)$_GET['subjects'] : [];
    $subject_list = [];

    if (!empty($selected_subjects)) {
        // Use selected subjects
        $placeholders = implode(',', array_map('intval', $selected_subjects));
        $result = $conn->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 AND id IN ($placeholders) ORDER BY subject_name");
        while ($row = $result->fetch_assoc()) {
            $subject_list[] = $row;
        }
    } else {
        // Use default: get first 15 subjects
        $result = $conn->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name LIMIT 15");
        while ($row = $result->fetch_assoc()) {
            $subject_list[] = $row;
        }
    }

    // Get students in this class
    $students = $conn->query("SELECT id, first_name, last_name, middle_name, admission_no FROM students WHERE class_id = $class_id ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);

    // Create CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="results_' . preg_replace('/[^a-z0-9]/i', '_', $class['class_name']) . '_template.csv"');

    $output = fopen('php://output', 'w');

    // Write header
    $headers = ['Student Name'];
    foreach ($subject_list as $subject) {
        $headers[] = $subject['subject_name'];
    }
    fputcsv($output, $headers);

    // Write student rows
    foreach ($students as $student) {
        $name = trim($student['first_name'] . ' ' . $student['last_name']);
        if (!empty($student['middle_name'])) {
            $name = trim($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']);
        }
        
        $row = [$name];
        // Add empty cells for scores
        foreach ($subject_list as $subject) {
            $row[] = '';
        }
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}
?>
