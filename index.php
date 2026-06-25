<?php
session_start();
include_once __DIR__ . '/config/db.php';

// If logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    $homeUrl = get_role_home_url($_SESSION['role'] ?? ROLE_ADMIN);
    header("Location: " . $homeUrl);
    exit;
} else {
    // Go to login
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}
?>