<?php
session_start();
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../config/constants.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

// Check session timeout (idle timeout — resets on each request)
if (isset($_SESSION['login_time'])) {
    $elapsed = time() - $_SESSION['login_time'];
    if ($elapsed > (SESSION_TIMEOUT * 60)) {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "pages/login.php?timeout=1");
        exit;
    }
}
// Refresh login_time on every request — true idle timeout
$_SESSION['login_time'] = time();

// Get user details
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$is_super_admin = ($user_role == ROLE_SUPER_ADMIN);

// Super-admin inherits all admin privileges transparently
if ($is_super_admin) {
    $user_role = ROLE_ADMIN;
}

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

if (!function_exists('get_role_home_url')) {
    function get_role_home_url($role)
    {
        switch ($role) {
            case ROLE_SUPER_ADMIN:
                return BASE_URL . 'pages/super_admin/dashboard.php';
            case ROLE_PARENT:
                return BASE_URL . 'pages/parents/dashboard.php';
            case ROLE_TEACHER:
                return BASE_URL . 'pages/teachers/dashboard.php';
            case ROLE_STUDENT:
                return BASE_URL . 'pages/students/dashboard.php';
            case ROLE_ADMIN:
            default:
                return BASE_URL . 'pages/dashboard.php';
        }
    }
}
?>