<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_name = htmlspecialchars($_POST['class_name']);
    $level = htmlspecialchars($_POST['level']);
    
    // Validate inputs
    if (empty($class_name)) {
        $error = "Class name is required";
    } elseif (empty($level)) {
        $error = "Level is required";
    } else {
        // Check if class name already exists
        $check = $conn->query("SELECT id FROM classes WHERE class_name = '$class_name'");
        if ($check->num_rows > 0) {
            $error = "Class name already exists";
        } else {
            // Insert class record
            $sql = "INSERT INTO classes (class_name, level)
                    VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $class_name, $level);
            
            if ($stmt->execute()) {
                $success = "Class created successfully!";
                // Clear form
                $class_name = '';
                $level = '';
            } else {
                $error = "Error creating class: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Class - SPMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once '../../includes/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <a href="index.php" class="text-indigo-600 hover:text-indigo-700 flex items-center gap-2">
                <span>←</span> Back to Classes
            </a>
        </div>

        <h1 class="text-3xl font-bold mb-8">Create New Class</h1>

        <div class="max-w-2xl bg-white rounded-lg shadow p-8">
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

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block font-bold mb-2">Class Name *</label>
                    <input type="text" name="class_name" required 
                           placeholder="e.g., JSS 1A, SS 2B" 
                           value="<?php echo isset($class_name) ? htmlspecialchars($class_name) : ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block font-bold mb-2">Level *</label>
                    <input type="text" name="level" required 
                           placeholder="e.g., Junior Secondary, Senior Secondary" 
                           value="<?php echo isset($level) ? htmlspecialchars($level) : ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                        Create Class
                    </button>
                    <a href="index.php" class="border border-gray-300 px-6 py-2 rounded hover:bg-gray-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>