<?php include_once '../../includes/auth_check.php'; ?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<?php
// Check admin role
if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

// Get all classes
$classes = $conn->query("SELECT * FROM classes ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $class_id = (int)$_GET['delete'];
    
    // Check if class has associated students
    $check = $conn->query("SELECT COUNT(*) as count FROM students WHERE class_id = $class_id");
    $result = $check->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error = "Cannot delete class with associated students. Remove students first.";
    } else {
        $delete_sql = "DELETE FROM classes WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $class_id);
        
        if ($stmt->execute()) {
            $success = "Class deleted successfully!";
            // Refresh classes list
            $classes = $conn->query("SELECT * FROM classes ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "Error deleting class: " . $conn->error;
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Classes</h1>
        <a href="create.php" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
            + Create New Class
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

    <?php if (empty($classes)): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
            No classes found. <a href="create.php" class="font-bold underline">Create one now</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Class Name</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Level</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Students</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Created</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                        <?php
                        // Count students in this class
                        $student_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE class_id = " . $class['id']);
                        $count_result = $student_count->fetch_assoc();
                        $student_count = $count_result['count'];
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                <?php echo htmlspecialchars($class['level']); ?>
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                                    <?php echo $student_count; ?> student<?php echo $student_count != 1 ? 's' : ''; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-700 text-sm">
                                <?php echo date('M d, Y', strtotime($class['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <a href="edit.php?id=<?php echo $class['id']; ?>" 
                                       class="bg-yellow-500 text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">
                                        Edit
                                    </a>
                                    <a href="?delete=<?php echo $class['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this class?');"
                                       class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                                        Delete
                                    </a>
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