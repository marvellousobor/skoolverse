<?php
include_once __DIR__ . '/config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO users (username, email, password_hash, role, status) 
            VALUES (?, ?, ?, 'admin', 'active')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password_hash);
    
    if ($stmt->execute()) {
        $message = '<div style="color:green; padding:10px; background:#d4edda; border:1px solid #c3e6cb; border-radius:5px;">
                    ✓ Admin created! Email: ' . $email . ' | Password: ' . $password . '
                    </div>';
    } else {
        $message = '<div style="color:red; padding:10px; background:#f8d7da; border:1px solid #f5c6cb; border-radius:5px;">
                    ✗ Error: ' . $conn->error . '
                    </div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Admin - SPMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h1 class="text-2xl font-bold mb-6 text-center">Create Admin Account</h1>
        
        <?php echo $message; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block font-bold mb-2">Username</label>
                <input type="text" name="username" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-bold mb-2">Email</label>
                <input type="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-bold mb-2">Password</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 font-bold">
                Create Admin
            </button>
        </form>
        
        <p class="text-sm text-gray-600 mt-4 text-center">
            After creating admin, delete this file for security!
        </p>
    </div>
</body>
</html>