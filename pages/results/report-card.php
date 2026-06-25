<?php
include_once '../../includes/auth_check.php';
include_once '../../includes/grading_helper.php';

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$session_id = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$term_id = isset($_GET['term']) ? (int)$_GET['term'] : 0;

if ($student_id <= 0 || $session_id <= 0 || $term_id <= 0) {
    die("Invalid parameters.");
}

// Access control
$has_access = false;
if ($user_role == ROLE_ADMIN) {
    $has_access = true;
} elseif ($user_role == ROLE_STUDENT) {
    $st = $conn->prepare("SELECT id FROM students WHERE user_id = ? AND id = ?");
    $st->bind_param("ii", $user_id, $student_id);
    $st->execute();
    if ($st->get_result()->num_rows > 0) $has_access = true;
} elseif ($user_role == ROLE_PARENT) {
    $pt = $conn->prepare("SELECT id FROM parents WHERE user_id = ?");
    $pt->bind_param("i", $user_id);
    $pt->execute();
    $p = $pt->get_result()->fetch_assoc();
    if ($p) {
        $link = $conn->prepare("SELECT 1 FROM student_parent_links WHERE parent_id = ? AND student_id = ?");
        $link->bind_param("ii", $p['id'], $student_id);
        $link->execute();
        if ($link->get_result()->num_rows > 0) $has_access = true;
    }
} elseif ($user_role == ROLE_TEACHER) {
    $th = $conn->prepare("SELECT t.id FROM teachers t JOIN teacher_assignments ta ON ta.teacher_id = t.id WHERE t.user_id = ? AND ta.class_id = (SELECT class_id FROM students WHERE id = ?)");
    $th->bind_param("ii", $user_id, $student_id);
    $th->execute();
    if ($th->get_result()->num_rows > 0) $has_access = true;
}

if (!$has_access) {
    die("Access denied.");
}

// Fetch data
$student = $conn->query("
    SELECT s.*, c.class_name 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE s.id = $student_id
")->fetch_assoc();

$session = $conn->query("SELECT session_name FROM sessions WHERE id = $session_id")->fetch_assoc();
$term = $conn->query("SELECT term_name FROM terms WHERE id = $term_id")->fetch_assoc();

if (!$student || !$session || !$term) {
    die("Data not found.");
}

$results = $conn->prepare("
    SELECT sr.subject_id, s.subject_name, s.subject_code, sr.score, sr.ca_score, sr.exam_score
    FROM student_results sr
    JOIN subjects s ON sr.subject_id = s.id
    WHERE sr.student_id = ? AND sr.session_id = ? AND sr.term_id = ? AND sr.is_published = 1
    ORDER BY s.subject_name
");
$results->bind_param("iii", $student_id, $session_id, $term_id);
$results->execute();
$results = $results->get_result()->fetch_all(MYSQLI_ASSOC);

$total = 0;
foreach ($results as $r) { $total += (float)$r['score']; }
$count = count($results);
$avg = $count > 0 ? round($total / $count, 1) : 0;
$grade = getGrade($avg);
$pos = getClassPosition($student_id, (int)$student['class_id'], $session_id, $term_id);

$is_print = isset($_GET['print']);

if (!$is_print):
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-file-pdf"></i> Report Card</h1>
                <span class="breadcrumb"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
            </div>
            <div class="page-actions">
                <a href="?student_id=<?php echo $student_id; ?>&session=<?php echo $session_id; ?>&term=<?php echo $term_id; ?>&print=1" class="btn btn-primary" target="_blank">
                    <i class="fas fa-print"></i> Print / PDF
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body" style="padding:2rem;">
<?php endif; ?>

<div class="report-card" style="max-width:794px;margin:0 auto;background:white;font-family:'Segoe UI',sans-serif;padding:<?php echo $is_print ? '0' : '0'; ?>;">
    <style>
        @media print {
            body { background:white !important; margin:0; padding:0; }
            .sidebar, .navbar, .navbar-content, .sidebar-header, .sidebar-nav, .sidebar-footer,
            .main-wrapper, .main-content, .card, .page-header, .page-actions, .btn,
            .no-print { display:none !important; }
            .report-card { padding:0 !important; max-width:100% !important; }
            @page { margin: 0.5in; size: A4 portrait; }
            .rc-footer { position: fixed; bottom: 0; width: 100%; }
            .rc-break { page-break-inside: avoid; }
        }
        .report-card table { border-collapse: collapse; width: 100%; }
        .report-card th, .report-card td { border: 1px solid #333; padding: 6px 10px; text-align: center; font-size: 12px; }
        .report-card th { background: #e5e7eb; font-weight: 700; }
        .report-card .left { text-align: left; }
        .report-card .right { text-align: right; }
    </style>

    <!-- School Header -->
    <div style="text-align:center;padding:20px 0 10px 0;border-bottom:3px double #1e40af;margin-bottom:15px;">
        <h1 style="font-size:22px;font-weight:800;color:#1e40af;margin:0;letter-spacing:1px;">SPMS SCHOOL</h1>
        <p style="font-size:11px;color:#4b5563;margin:4px 0 0 0;">123 Education Avenue, Learning City &middot; Tel: 01-2345678 &middot; Email: info@spms.edu.ng</p>
        <h2 style="font-size:16px;font-weight:700;color:#374151;margin:8px 0 0 0;text-transform:uppercase;">Report Sheet</h2>
    </div>

    <!-- Student Info -->
    <div class="rc-break" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:15px;font-size:12px;">
        <div>
            <p style="margin:2px 0;"><strong>Student Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></p>
            <p style="margin:2px 0;"><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></p>
            <p style="margin:2px 0;"><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name']); ?></p>
        </div>
        <div>
            <p style="margin:2px 0;"><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
            <p style="margin:2px 0;"><strong>Session:</strong> <?php echo htmlspecialchars($session['session_name']); ?></p>
            <p style="margin:2px 0;"><strong>Term:</strong> <?php echo htmlspecialchars($term['term_name']); ?></p>
        </div>
    </div>

    <!-- Scores Table -->
    <table class="rc-break">
        <thead>
            <tr>
                <th style="width:40px;">S/N</th>
                <th style="text-align:left;">Subject</th>
                <th style="width:70px;">CA (40)</th>
                <th style="width:70px;">Exam (70)</th>
                <th style="width:60px;">Total</th>
                <th style="width:50px;">Grade</th>
                <th style="text-align:left;">Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $i => $r): ?>
                <?php $sg = getGrade($r['score']); ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td style="text-align:left;font-weight:600;"><?php echo htmlspecialchars($r['subject_name']); ?></td>
                    <td><?php echo $r['ca_score'] !== null ? htmlspecialchars($r['ca_score']) : '—'; ?></td>
                    <td><?php echo $r['exam_score'] !== null ? htmlspecialchars($r['exam_score']) : '—'; ?></td>
                    <td style="font-weight:700;"><?php echo $r['score']; ?></td>
                    <td><strong><?php echo $sg['grade']; ?></strong></td>
                    <td style="text-align:left;font-size:11px;"><?php echo htmlspecialchars($sg['remark']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="rc-break" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:15px 0;">
        <div style="border:1px solid #333;padding:10px;border-radius:4px;">
            <p style="margin:3px 0;font-size:12px;"><strong>Total Score:</strong> <?php echo $total; ?> / <?php echo $count * 100; ?></p>
            <p style="margin:3px 0;font-size:12px;"><strong>Average:</strong> <?php echo $avg; ?>%</p>
            <p style="margin:3px 0;font-size:12px;"><strong>Overall Grade:</strong> <?php echo $grade['grade']; ?> (<?php echo htmlspecialchars($grade['remark']); ?>)</p>
            <p style="margin:3px 0;font-size:12px;"><strong>Class Position:</strong> <?php echo $pos['position'] > 0 ? ordinal($pos['position']) . ' out of ' . $pos['out_of'] : '—'; ?></p>
        </div>
        <div style="border:1px solid #333;padding:10px;border-radius:4px;">
            <p style="margin:3px 0;font-size:12px;"><strong>Subjects Taken:</strong> <?php echo $count; ?></p>
            <p style="margin:3px 0;font-size:12px;"><strong>Promotion:</strong> <span style="font-weight:700;color:<?php echo $avg >= 40 ? '#059669' : '#dc2626'; ?>;"><?php echo $avg >= 40 ? 'PROMOTED' : 'REPEAT'; ?></span></p>
        </div>
    </div>

    <!-- Comments -->
    <div class="rc-break" style="margin:15px 0;">
        <div style="border:1px solid #333;padding:10px;border-radius:4px;margin-bottom:8px;">
            <p style="font-size:12px;font-weight:600;margin:0 0 4px 0;">Teacher's Comment:</p>
            <div style="border-bottom:1px solid #d1d5db;height:24px;margin-bottom:4px;"></div>
            <p style="font-size:10px;color:#6b7280;margin:0;">Signature: ____________________ Date: ________</p>
        </div>
        <div style="border:1px solid #333;padding:10px;border-radius:4px;">
            <p style="font-size:12px;font-weight:600;margin:0 0 4px 0;">Principal's Comment:</p>
            <div style="border-bottom:1px solid #d1d5db;height:24px;margin-bottom:4px;"></div>
            <p style="font-size:10px;color:#6b7280;margin:0;">Signature: ____________________ Date: ________</p>
        </div>
    </div>

    <!-- Grading Key -->
    <div class="rc-break" style="margin-top:15px;padding-top:10px;border-top:1px solid #d1d5db;">
        <p style="font-size:11px;font-weight:600;margin:0 0 4px 0;">Grading Key (WAEC Standard):</p>
        <table style="font-size:10px;">
            <thead>
                <tr><th style="padding:2px 6px;">Grade</th><th style="padding:2px 6px;">Range</th><th style="padding:2px 6px;">Remark</th></tr>
            </thead>
            <tbody>
                <tr><td>A1</td><td>75 - 100</td><td>Excellent</td></tr>
                <tr><td>B2</td><td>70 - 74</td><td>Very Good</td></tr>
                <tr><td>B3</td><td>65 - 69</td><td>Good</td></tr>
                <tr><td>C4</td><td>60 - 64</td><td>Credit</td></tr>
                <tr><td>C5</td><td>55 - 59</td><td>Credit</td></tr>
                <tr><td>C6</td><td>50 - 54</td><td>Credit</td></tr>
                <tr><td>D7</td><td>45 - 49</td><td>Pass</td></tr>
                <tr><td>E8</td><td>40 - 44</td><td>Pass</td></tr>
                <tr><td>F9</td><td>0 - 39</td><td>Fail</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$is_print): ?>
            </div>
        </div>
    </main>
</div>

<script>
window.onload = function() {
    // Auto-open print dialog if ?print=1 was in URL (handled by target=_blank)
};
</script>

<?php
include_once '../../includes/footer.php';
endif; // end !is_print
?>
