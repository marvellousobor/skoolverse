<?php include_once '../../includes/auth_check.php'; ?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<?php
// Check admin role
if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

// Get all sessions
$sessions = $conn->query("SELECT * FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $session_id = (int)$_GET['delete'];
    
    // Check if session has associated students
    $check = $conn->query("SELECT COUNT(*) as count FROM students WHERE session_id = $session_id");
    $result = $check->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error = "Cannot delete session with associated students. Remove students first.";
    } else {
        $delete_sql = "DELETE FROM sessions WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $session_id);
        
        if ($stmt->execute()) {
            $success = "Session deleted successfully!";
            // Refresh sessions list
            $sessions = $conn->query("SELECT * FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "Error deleting session: " . $conn->error;
        }
    }
}

// Handle set as active
if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $session_id = (int)$_GET['activate'];
    
    // First deactivate all sessions
    $conn->query("UPDATE sessions SET is_active = 0");
    
    // Then activate the selected one
    $activate_sql = "UPDATE sessions SET is_active = 1 WHERE id = ?";
    $stmt = $conn->prepare($activate_sql);
    $stmt->bind_param("i", $session_id);
    
    if ($stmt->execute()) {
        $success = "Session activated successfully!";
        // Refresh sessions list
        $sessions = $conn->query("SELECT * FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "Error activating session: " . $conn->error;
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Academic Sessions</h1>
        <a href="create.php" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
            + Create New Session
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

    <?php if (empty($sessions)): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
            No academic sessions found. <a href="create.php" class="font-bold underline">Create one now</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Session Name</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Start Date</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">End Date</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Created</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                <?php echo htmlspecialchars($session['session_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                <?php echo date('M d, Y', strtotime($session['start_date'])); ?>
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                <?php echo date('M d, Y', strtotime($session['end_date'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($session['is_active']): ?>
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-700 text-sm">
                                <?php echo date('M d, Y', strtotime($session['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <?php if (!$session['is_active']): ?>
                                        <a href="?activate=<?php echo $session['id']; ?>" 
                                           class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                            Activate
                                        </a>
                                    <?php endif; ?>
                                    <a href="edit.php?id=<?php echo $session['id']; ?>" 
                                       class="bg-yellow-500 text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">
                                        Edit
                                    </a>
                                    <a href="?delete=<?php echo $session['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this session?');"
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