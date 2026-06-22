<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

$sessions = $conn->query("SELECT id, session_name, is_active FROM sessions ORDER BY is_active DESC, created_at DESC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $term_name = htmlspecialchars($_POST['term_name'] ?? '');
    $session_id = (int)($_POST['session_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate inputs
    if (empty($term_name)) {
        $error = "Term name is required";
    } elseif ($session_id <= 0) {
        $error = "Session is required";
    } elseif (empty($start_date) || empty($end_date)) {
        $error = "Both start and end dates are required";
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $error = "Start date must be before end date";
    } else {
        // Check if term name already exists for this session using prepared statement
        $check_sql = "SELECT id FROM terms WHERE term_name = ? AND session_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if (!$check_stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $check_stmt->bind_param("si", $term_name, $session_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "Term '" . htmlspecialchars($term_name) . "' already exists for this session";
            } else {
                // Insert term record
                $sql = "INSERT INTO terms (term_name, session_id, start_date, end_date, is_active)
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param("sissi", $term_name, $session_id, $start_date, $end_date, $is_active);
                    
                    if ($stmt->execute()) {
                        $success = "Academic term created successfully!";
                        // Clear form
                        $term_name = '';
                        $session_id = 0;
                        $start_date = '';
                        $end_date = '';
                        $is_active = 0;
                    } else {
                        // Handle specific database errors
                        if (strpos($stmt->error, 'Duplicate') !== false) {
                            $error = "A term with this name already exists for the selected session";
                        } else {
                            $error = "Error creating term: " . $stmt->error;
                        }
                    }
                    $stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Academic Term - SPMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once '../../includes/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <a href="index.php" class="text-indigo-600 hover:text-indigo-700 flex items-center gap-2">
                <span>←</span> Back to Terms
            </a>
        </div>

        <h1 class="text-3xl font-bold mb-8">Create New Academic Term</h1>

        <div class="max-w-2xl bg-white rounded-lg shadow p-8">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block font-bold mb-2">Academic Session *</label>
                    <select name="session_id" required class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">-- Select Session --</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?php echo htmlspecialchars($session['id']); ?>" 
                                    <?php echo isset($session_id) && $session_id == $session['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($session['session_name']); ?>
                                <?php echo $session['is_active'] ? '(Active)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-bold mb-2">Term Name *</label>
                    <input type="text" name="term_name" required 
                           placeholder="e.g., First Term" 
                           value="<?php echo isset($term_name) ? htmlspecialchars($term_name) : ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-sm text-gray-500 mt-1">Examples: First Term, Second Term, Third Term</p>
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
                        <span class="font-bold">Set as Active Term</span>
                    </label>
                    <p class="text-sm text-gray-500 mt-1">Only one term per session can be active at a time</p>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                        Create Term
                    </button>
                    <a href="index.php" class="border border-gray-300 px-6 py-2 rounded hover:bg-gray-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include_once '../../includes/footer.php'; ?>
</body>
</html>