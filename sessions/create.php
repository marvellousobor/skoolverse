<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $session_name = htmlspecialchars($_POST['session_name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate inputs
    if (empty($session_name)) {
        $error = "Session name is required";
    } elseif (empty($start_date) || empty($end_date)) {
        $error = "Both start and end dates are required";
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $error = "Start date must be before end date";
    } else {
        // Check if session name already exists
        $check = $conn->query("SELECT id FROM sessions WHERE session_name = '$session_name'");
        if ($check->num_rows > 0) {
            $error = "Session name already exists";
        } else {
            // Insert session record
            $sql = "INSERT INTO sessions (session_name, start_date, end_date, is_active)
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $session_name, $start_date, $end_date, $is_active);
            
            if ($stmt->execute()) {
                $success = "Academic session created successfully!";
                // Clear form
                $session_name = '';
                $start_date = '';
                $end_date = '';
                $is_active = 0;
            } else {
                $error = "Error creating session: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Session - SPMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once '../../includes/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <a href="index.php" class="text-indigo-600 hover:text-indigo-700 flex items-center gap-2">
                <span>←</span> Back to Sessions
            </a>
        </div>

        <h1 class="text-3xl font-bold mb-8">Create New Academic Session</h1>

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
                    <label class="block font-bold mb-2">Session Name *</label>
                    <input type="text" name="session_name" required 
                           placeholder="e.g., 2024/2025" 
                           value="<?php echo isset($session_name) ? htmlspecialchars($session_name) : ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-sm text-gray-500 mt-1">Format: YYYY/YYYY (e.g., 2024/2025)</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-2">Start Date *</label>
                        <input type="date" name="start_date" required 
                               value="<?php echo isset($start_date) ? htmlspecialchars($start_date) : ''; ?>"
                               class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">End Date *</label>
                        <input type="date" name="end_date" required 
                               value="<?php echo isset($end_date) ? htmlspecialchars($end_date) : ''; ?>"
                               class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" 
                               <?php echo isset($is_active) && $is_active ? 'checked' : ''; ?>
                               class="w-4 h-4">
                        <span class="font-bold">Set as Active Session</span>
                    </label>
                    <p class="text-sm text-gray-500 mt-1">Only one session can be active at a time</p>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                        Create Session
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