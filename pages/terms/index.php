<?php include_once '../../includes/auth_check.php'; ?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<?php
// Check admin role
if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

// Ensure terms table exists
$conn->query("CREATE TABLE IF NOT EXISTS terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    term_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_term_per_session (session_id, term_name),
    INDEX (session_id),
    INDEX (is_active),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
)");

// Get all terms with associated sessions
$terms = $conn->query("SELECT t.*, s.session_name FROM terms t LEFT JOIN sessions s ON t.session_id = s.id ORDER BY s.id DESC, t.id DESC")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $term_id = (int)$_GET['delete'];
    
    // Check if term has associated records
    $check_fees = $conn->query("SELECT COUNT(*) as count FROM class_fees WHERE term_id = $term_id")->fetch_assoc();
    $check_results = $conn->query("SELECT COUNT(*) as count FROM results WHERE term_id = $term_id")->fetch_assoc();
    $check_student_fees = $conn->query("SELECT COUNT(*) as count FROM student_fees WHERE term_id = $term_id")->fetch_assoc();
    $check_student_results = $conn->query("SELECT COUNT(*) as count FROM student_results WHERE term_id = $term_id")->fetch_assoc();
    
    if ($check_fees['count'] > 0) {
        $error = "Cannot delete term with associated class fees. Remove term from fees first.";
    } elseif ($check_results['count'] > 0) {
        $error = "Cannot delete term with associated results. Remove results first.";
    } elseif ($check_student_fees['count'] > 0) {
        $error = "Cannot delete term with associated student fees. Remove fee records first.";
    } elseif ($check_student_results['count'] > 0) {
        $error = "Cannot delete term with associated student results. Remove results first.";
    } else {
        $delete_sql = "DELETE FROM terms WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $term_id);
        
        if ($stmt->execute()) {
            $success = "Term deleted successfully!";
            // Refresh terms list
            $terms = $conn->query("SELECT t.*, s.session_name FROM terms t LEFT JOIN sessions s ON t.session_id = s.id ORDER BY s.id DESC, t.id DESC")->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "Error deleting term: " . $conn->error;
        }
    }
}

// Handle set as active
if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $term_id = (int)$_GET['activate'];
    
    // First deactivate all terms for this session
    $term = $conn->query("SELECT session_id FROM terms WHERE id = $term_id")->fetch_assoc();
    if ($term) {
        $conn->query("UPDATE terms SET is_active = 0 WHERE session_id = " . $term['session_id']);
        
        // Then activate the selected one
        $update_sql = "UPDATE terms SET is_active = 1 WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $term_id);
        
        if ($stmt->execute()) {
            $success = "Term activated successfully!";
            // Refresh terms list
            $terms = $conn->query("SELECT t.*, s.session_name FROM terms t LEFT JOIN sessions s ON t.session_id = s.id ORDER BY s.id DESC, t.id DESC")->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "Error activating term: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Academic Terms - SPMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once '../../includes/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <a href="../dashboard.php" class="text-indigo-600 hover:text-indigo-700 flex items-center gap-2 mb-4">
                    <span>←</span> Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Academic Terms</h1>
            </div>
            <a href="create.php" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                + Create Term
            </a>
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

        <?php if (empty($terms)): ?>
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <p class="text-gray-600 mb-4">No terms have been created yet.</p>
                <a href="create.php" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 inline-block">
                    Create First Term
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Term Name</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Session</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Start Date</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">End Date</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terms as $term): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($term['term_name']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($term['session_name'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($term['start_date'])); ?></td>
                                <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($term['end_date'])); ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($term['is_active']): ?>
                                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">Active</span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="edit.php?id=<?php echo $term['id']; ?>" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                                        <?php if (!$term['is_active']): ?>
                                            <a href="?activate=<?php echo $term['id']; ?>" class="text-green-600 hover:text-green-800">Activate</a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $term['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-800">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once '../../includes/footer.php'; ?>
</body>
</html>
