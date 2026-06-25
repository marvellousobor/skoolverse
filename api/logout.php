<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page
touch('logout_success.txt');
header("Location: ../login.php");
exit();
?>