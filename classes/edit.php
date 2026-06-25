<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

// Get class ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$class_id = (int)$_GET['id'];

// Fetch current class data
$result = $conn->query("SELECT * FROM classes WHERE id = $class_id");
if ($result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$class = $result->fetch_assoc();
$class_name = $class['class_name'];
$level = $class['level'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_name = htmlspecialchars($_POST['class_name']);
    $level = htmlspecialchars($_POST['level']);
    
    // Validate inputs
    if (empty($class_name)) {
        $error = "Class name is required";
    } elseif (empty($level)) {
        $error = "Level is required";
    } else {
        // Check if class name already exists (excluding current class)
        $check = $conn->query("SELECT id FROM classes WHERE class_name = '$class_name' AND id != $class_id");
        if ($check->num_rows > 0) {
            $error = "Class name already exists";
        } else {
            // Update class record
            $sql = "UPDATE classes SET class_name = ?, level = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $class_name, $level, $class_id);
            
            if ($stmt->execute()) {
                $success = "Class updated successfully!";
            } else {
                $error = "Error updating class: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Class - SPMS</title>
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

        <h1 class="text-3xl font-bold mb-8">Edit Class</h1>

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
                           value="<?php echo htmlspecialchars($class_name); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block font-bold mb-2">Level *</label>
                    <input type="text" name="level" required 
                           placeholder="e.g., Junior Secondary, Senior Secondary" 
                           value="<?php echo htmlspecialchars($level); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                        Update Class
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