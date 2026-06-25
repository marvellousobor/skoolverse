<?php
function getGrade($score, mysqli $conn = null) {
    global $conn;
    if ($conn === null) $conn = $GLOBALS['conn'];
    static $cache = null;
    if ($cache === null) {
        $result = $conn->query("SELECT grade, lower_bound, upper_bound, remark FROM grading_scales WHERE is_active = 1 ORDER BY lower_bound DESC");
        $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    foreach ($cache as $g) {
        if ($score >= $g['lower_bound'] && $score <= $g['upper_bound']) {
            return $g;
        }
    }
    return ['grade' => 'F9', 'remark' => 'Fail'];
}

function getGradeStyle($grade) {
    $styles = [
        'A1' => '#d1fae5',
        'A' => '#d1fae5',
        'B2' => '#dbeafe',
        'B3' => '#dbeafe',
        'B' => '#dbeafe',
        'C4' => '#fef3c7',
        'C5' => '#fef3c7',
        'C6' => '#fef3c7',
        'C' => '#fef3c7',
        'D7' => '#ffedd5',
        'D' => '#ffedd5',
        'E8' => '#fce4ec',
        'E' => '#fce4ec',
        'F9' => '#fee2e2',
        'F' => '#fee2e2',
    ];
    $colors = [
        'A1' => '#065f46',
        'A' => '#065f46',
        'B2' => '#1e40af',
        'B3' => '#1e40af',
        'B' => '#1e40af',
        'C4' => '#92400e',
        'C5' => '#92400e',
        'C6' => '#92400e',
        'C' => '#92400e',
        'D7' => '#9a3412',
        'D' => '#9a3412',
        'E8' => '#9c27b0',
        'E' => '#9c27b0',
        'F9' => '#991b1b',
        'F' => '#991b1b',
    ];
    $bg = $styles[$grade] ?? '#f3f4f6';
    $color = $colors[$grade] ?? '#374151';
    return "background:{$bg};color:{$color};";
}

function getClassPosition($student_id, $class_id, $session_id, $term_id, mysqli $conn = null) {
    global $conn;
    if ($conn === null) $conn = $GLOBALS['conn'];
    $stmt = $conn->prepare("
        SELECT sr.student_id, SUM(sr.score) as total
        FROM student_results sr
        WHERE sr.class_id = ? AND sr.session_id = ? AND sr.term_id = ? AND sr.is_published = 1
        GROUP BY sr.student_id
        ORDER BY total DESC
    ");
    $stmt->bind_param("iii", $class_id, $session_id, $term_id);
    $stmt->execute();
    $rankings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $position = 0;
    $total = count($rankings);
    foreach ($rankings as $i => $r) {
        if ((int)$r['student_id'] === (int)$student_id) {
            $position = $i + 1;
            break;
        }
    }
    return ['position' => $position, 'out_of' => $total];
}

function ordinal($number) {
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if (($number % 100) >= 11 && ($number % 100) <= 13) return $number . 'th';
    return $number . $ends[$number % 10] ?? 'th';
}
