<?php
include_once '../../includes/auth_check.php';

// Only admins can delete students
if ($user_role != ROLE_ADMIN) {
    header('Location: index.php');
    exit();
}

// Get student ID from POST
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;

if ($student_id <= 0) {
    $_SESSION['error'] = 'Invalid student ID';
    header('Location: index.php');
    exit();
}

// Check if student exists
$check_sql = "SELECT id FROM students WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $student_id);
$check_stmt->execute();
$student = $check_stmt->get_result()->fetch_assoc();

if (!$student) {
    $_SESSION['error'] = 'Student not found';
    header('Location: index.php');
    exit();
}

// Nullify parent_id for any students referencing this one as parent
$nullify_sql = "UPDATE students SET parent_id = NULL WHERE parent_id = ?";
$nullify_stmt = $conn->prepare($nullify_sql);
$nullify_stmt->bind_param("i", $student_id);
$nullify_stmt->execute();
$nullify_stmt->close();

// Delete the student
$delete_sql = "DELETE FROM students WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $student_id);

if ($delete_stmt->execute()) {
    $_SESSION['success'] = 'Student deleted successfully';
} else {
    $_SESSION['error'] = 'Failed to delete student: ' . $conn->error;
}

$delete_stmt->close();
header('Location: index.php');
exit();
?>