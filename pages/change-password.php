<?php
include_once '../includes/auth_check.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password == '' || strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } elseif (!password_verify($current_password, $current_user['password_hash'])) {
        $error = "Current password is incorrect.";
    } else {
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $user_id);

        if ($stmt->execute()) {
            $success = "Password changed successfully.";
        } else {
            $error = "Unable to change password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - SPMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Change Password</h1>

        <div class="max-w-md bg-white rounded-lg shadow p-8">
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

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block font-bold mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block font-bold mb-2">New Password</label>
                    <input type="password" name="new_password" required class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block font-bold mb-2">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                    Change Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>
