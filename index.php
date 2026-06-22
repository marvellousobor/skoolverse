<?php
session_start();
include_once 'config/db.php';

// If logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
} else {
    // Go to login
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}
?>