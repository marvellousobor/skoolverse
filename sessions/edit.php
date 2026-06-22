<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

// Get session ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$session_id = (int)$_GET['id'];

// Fetch current session data
$result = $conn->query("SELECT * FROM sessions WHERE id = $session_id");
if ($result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$session = $result->fetch_assoc();
$session_name = $session['session_name'];
$start_date = $session['start_date'];
$end_date = $session['end_date'];
$is_active = $session['is_active'];

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
        // Check if session name already exists (excluding current session)
        $check = $conn->query("SELECT id FROM sessions WHERE session_name = '$session_name' AND id != $session_id");
        if ($check->num_rows > 0) {
            $error = "Session name already exists";
        } else {
            // Update session record
            $sql = "UPDATE sessions SET session_name = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssii", $session_name, $start_date, $end_date, $is_active, $session_id);
            
            if ($stmt->execute()) {
                $success = "Academic session updated successfully!";
            } else {
                $error = "Error updating session: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Session - SPMS</title>
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

        <h1 class="text-3xl font-bold mb-8">Edit Academic Session</h1>

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
                           value="<?php echo htmlspecialchars($session_name); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-sm text-gray-500 mt-1">Format: YYYY/YYYY (e.g., 2024/2025)</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-2">Start Date *</label>
                        <input type="date" name="start_date" required 
                               value="<?php echo htmlspecialchars($start_date); ?>"
                               class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">End Date *</label>
                        <input type="date" name="end_date" required 
                               value="<?php echo htmlspecialchars($end_date); ?>"
                               class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" 
                               <?php echo $is_active ? 'checked' : ''; ?>
                               class="w-4 h-4">
                        <span class="font-bold">Set as Active Session</span>
                    </label>
                    <p class="text-sm text-gray-500 mt-1">Only one session can be active at a time</p>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                        Update Session
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