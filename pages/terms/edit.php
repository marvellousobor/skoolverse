<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

// Get term ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$term_id = (int)$_GET['id'];

// Fetch current term data
$result = $conn->query("SELECT * FROM terms WHERE id = $term_id");
if ($result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$term = $result->fetch_assoc();
$term_name = $term['term_name'];
$session_id = $term['session_id'];
$start_date = $term['start_date'];
$end_date = $term['end_date'];
$is_active = $term['is_active'];

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
        // Check if term name already exists (excluding current term)
        $check = $conn->query("SELECT id FROM terms WHERE term_name = '$term_name' AND session_id = $session_id AND id != $term_id");
        if ($check->num_rows > 0) {
            $error = "Term '" . htmlspecialchars($term_name) . "' already exists for this session";
        } else {
            // Update term record
            $sql = "UPDATE terms SET term_name = ?, session_id = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissii", $term_name, $session_id, $start_date, $end_date, $is_active, $term_id);
            
            if ($stmt->execute()) {
                $success = "Academic term updated successfully!";
            } else {
                $error = "Error updating term: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Academic Term - SPMS</title>
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

        <h1 class="text-3xl font-bold mb-8">Edit Academic Term</h1>

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
                    <label class="block font-bold mb-2">Academic Session *</label>
                    <select name="session_id" required class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">-- Select Session --</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?php echo $session['id']; ?>" 
                                    <?php echo $session_id == $session['id'] ? 'selected' : ''; ?>>
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
                           value="<?php echo htmlspecialchars($term_name); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-sm text-gray-500 mt-1">Examples: First Term, Second Term, Third Term</p>
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
                        <span class="font-bold">Set as Active Term</span>
                    </label>
                    <p class="text-sm text-gray-500 mt-1">Only one term per session can be active at a time</p>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                        Update Term
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
