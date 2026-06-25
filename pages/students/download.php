<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_STUDENT) {
    header('Location: ../dashboard.php');
    exit();
}

$session_id = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$term_id = isset($_GET['term']) ? (int)$_GET['term'] : 0;

// Find student id for this user
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$studentRow = $stmt->get_result()->fetch_assoc();
if (!$studentRow) {
    header('Location: results.php');
    exit();
}
$student_id = (int)$studentRow['id'];

if ($student_id <= 0 || $session_id <= 0 || $term_id <= 0) {
    header('Location: results.php');
    exit();
}

$stmt = $conn->prepare("SELECT sr.subject_id, s.subject_name, sr.score FROM student_results sr JOIN subjects s ON sr.subject_id = s.id WHERE sr.student_id = ? AND sr.session_id = ? AND sr.term_id = ? ORDER BY s.subject_name");
$stmt->bind_param('iii', $student_id, $session_id, $term_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($results)) {
    header('Location: results.php?session=' . $session_id . '&term=' . $term_id);
    exit();
}

$filename = 'results_student_' . $student_id . '_s' . $session_id . '_t' . $term_id . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Subject', 'Score']);
foreach ($results as $r) {
    fputcsv($out, [$r['subject_name'], $r['score']]);
}
fclose($out);
exit();

?>