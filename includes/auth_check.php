<?php
session_start();
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../config/constants.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

// Check session timeout
if (isset($_SESSION['login_time'])) {
    $elapsed = time() - $_SESSION['login_time'];
    if ($elapsed > (SESSION_TIMEOUT * 60)) {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "pages/login.php?timeout=1");
        exit;
    }
}

// Get user details
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Fetch user data from database
$sql = "SELECT * FROM users WHERE id = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

$current_user = $result->fetch_assoc();
?>