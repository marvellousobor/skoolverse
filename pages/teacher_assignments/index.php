<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$assignments = $conn->query("SELECT ta.id, ta.teacher_id, ta.subject_id, ta.class_id, ta.session_id, ta.created_at, t.full_name as teacher_name, s.subject_name, c.class_name, se.session_name FROM teacher_assignments ta LEFT JOIN teachers t ON ta.teacher_id = t.id LEFT JOIN subjects s ON ta.subject_id = s.id LEFT JOIN classes c ON ta.class_id = c.id LEFT JOIN sessions se ON ta.session_id = se.id ORDER BY ta.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Assignments - SPMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    </head>
<body class="bg-gray-50">
    <?php include_once '../../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Teacher Assignments</h1>
            <a href="create.php" class="bg-indigo-600 text-white px-4 py-2 rounded">New Assignment</a>
        </div>

        <div class="bg-white rounded shadow p-4">
            <?php if (empty($assignments)): ?>
                <div class="text-gray-600">No assignments found.</div>
            <?php else: ?>
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left">Teacher</th>
                            <th class="px-3 py-2">Class</th>
                            <th class="px-3 py-2">Subject</th>
                            <th class="px-3 py-2">Session</th>
                            <th class="px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr class="border-t">
                                <td class="px-3 py-2"><?php echo htmlspecialchars($a['teacher_name'] ?? '-'); ?></td>
                                <td class="px-3 py-2"><?php echo htmlspecialchars($a['class_name'] ?? '-'); ?></td>
                                <td class="px-3 py-2"><?php echo htmlspecialchars($a['subject_name'] ?? '-'); ?></td>
                                <td class="px-3 py-2"><?php echo htmlspecialchars($a['session_name'] ?? '-'); ?></td>
                                <td class="px-3 py-2"><a href="edit.php?id=<?php echo $a['id']; ?>" class="text-indigo-600">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once '../../includes/footer.php'; ?>
</body>
</html>
