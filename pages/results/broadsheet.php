<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN && $user_role != ROLE_TEACHER) {
    header('Location: ../dashboard.php');
    exit();
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
include_once '../../includes/grading_helper.php';

$class_id = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$session_id = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$term_id = isset($_GET['term']) ? (int)$_GET['term'] : 0;

$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

$students = [];
$subjects = [];
$results_map = [];
$rankings = [];

if ($class_id > 0 && $session_id > 0 && $term_id > 0) {
    $students = $conn->query("SELECT id, first_name, last_name, middle_name, admission_no FROM students WHERE class_id = $class_id AND is_active = 1 ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);
    // Prefer class_subjects, fallback to subjects with results
    $subjects = $conn->query("
        SELECT cs.subject_id, s.subject_name
        FROM class_subjects cs
        JOIN subjects s ON s.id = cs.subject_id
        WHERE cs.class_id = $class_id AND s.is_active = 1
        ORDER BY s.subject_name
    ")->fetch_all(MYSQLI_ASSOC);
    if (empty($subjects)) {
        $subjects = $conn->query("SELECT DISTINCT sr.subject_id, s.subject_name FROM student_results sr JOIN subjects s ON sr.subject_id = s.id WHERE sr.class_id = $class_id AND sr.session_id = $session_id AND sr.term_id = $term_id ORDER BY s.subject_name")->fetch_all(MYSQLI_ASSOC);
    }
    $results = $conn->query("SELECT sr.*, s.subject_name FROM student_results sr JOIN subjects s ON sr.subject_id = s.id WHERE sr.class_id = $class_id AND sr.session_id = $session_id AND sr.term_id = $term_id ORDER BY sr.student_id, s.subject_name")->fetch_all(MYSQLI_ASSOC);

    foreach ($results as $r) {
        $results_map[$r['student_id']][$r['subject_id']] = $r;
    }

    $rank_data = $conn->prepare("SELECT sr.student_id, SUM(sr.score) as total FROM student_results sr WHERE sr.class_id = ? AND sr.session_id = ? AND sr.term_id = ? AND sr.is_published = 1 GROUP BY sr.student_id ORDER BY total DESC");
    $rank_data->bind_param("iii", $class_id, $session_id, $term_id);
    $rank_data->execute();
    $rankings = $rank_data->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-table-cells-large"></i> Broadsheet</h1>
                <span class="breadcrumb">Full class performance overview</span>
            </div>
            <?php if (!empty($students) && $class_id > 0): ?>
            <div class="page-actions">
                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
            </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Select Class & Period</h2>
            </div>
            <div class="card-body">
                <form method="GET" class="form-row">
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $class_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Session</label>
                        <select name="session" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Select Session --</option>
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $s['id'] == $session_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['session_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Term</label>
                        <select name="term" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Select Term --</option>
                            <?php foreach ($terms as $t): ?>
                                <?php if ($t['session_id'] == $session_id || $session_id == 0): ?>
                                    <option value="<?php echo $t['id']; ?>" <?php echo $t['id'] == $term_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['term_name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($class_id > 0 && $session_id > 0 && $term_id > 0): ?>
            <?php if (empty($students) || empty($subjects)): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-file-circle-question"></i>
                        <p>No results found for this selection.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-ranking-star"></i> Class Performance</h2>
                        <span style="font-size:0.8rem;color:var(--gray-500);"><?php echo count($students); ?> students &middot; <?php echo count($subjects); ?> subjects</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="table" style="font-size:0.8rem;">
                            <thead>
                                <tr>
                                    <th style="position:sticky;left:0;background:var(--gray-50);z-index:2;">Student</th>
                                    <th style="text-align:center;">Adm No</th>
                                    <?php foreach ($subjects as $subj): ?>
                                        <th style="text-align:center;background:#f0f9ff;min-width:65px;">
                                            <span title="<?php echo htmlspecialchars($subj['subject_name']); ?>"><?php echo htmlspecialchars(substr($subj['subject_name'], 0, 8)); ?></span>
                                        </th>
                                    <?php endforeach; ?>
                                    <th style="text-align:center;background:var(--gray-100);">Total</th>
                                    <th style="text-align:center;background:var(--gray-100);">Avg</th>
                                    <th style="text-align:center;background:var(--gray-100);">Grade</th>
                                    <th style="text-align:center;background:var(--gray-100);">Pos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $student_totals = [];
                                foreach ($students as $student) {
                                    $sid = (int)$student['id'];
                                    $total = 0;
                                    $count = 0;
                                    foreach ($subjects as $subj) {
                                        if (isset($results_map[$sid][$subj['subject_id']])) {
                                            $total += (float)$results_map[$sid][$subj['subject_id']]['score'];
                                            $count++;
                                        }
                                    }
                                    $student_totals[$sid] = ['total' => $total, 'count' => $count, 'student' => $student];
                                }
                                arsort($student_totals);
                                $position = 1;
                                ?>
                                <?php foreach ($student_totals as $sid => $data): ?>
                                    <?php $s = $data['student']; ?>
                                    <?php $avg = $data['count'] > 0 ? round($data['total'] / $data['count'], 1) : 0; ?>
                                    <?php $grade = getGrade($avg); ?>
                                    <tr>
                                        <td style="position:sticky;left:0;background:var(--white);font-weight:600;z-index:1;">
                                            <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?>
                                        </td>
                                        <td style="text-align:center;font-size:0.7rem;font-family:monospace;"><?php echo htmlspecialchars($s['admission_no']); ?></td>
                                        <?php foreach ($subjects as $subj): ?>
                                            <td style="text-align:center;">
                                                <?php if (isset($results_map[$sid][$subj['subject_id']])): ?>
                                                    <?php $r = $results_map[$sid][$subj['subject_id']]; ?>
                                                    <span style="font-weight:600;"><?php echo $r['score']; ?></span>
                                                <?php else: ?>
                                                    <span style="color:var(--gray-300);">—</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td style="text-align:center;font-weight:700;background:var(--gray-50);"><?php echo $data['total']; ?></td>
                                        <td style="text-align:center;font-weight:600;background:var(--gray-50);"><?php echo $avg; ?></td>
                                        <td style="text-align:center;background:var(--gray-50);">
                                            <span class="grade-cell" style="<?php echo getGradeStyle($grade['grade']); ?>display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;font-weight:800;font-size:0.75rem;">
                                                <?php echo $grade['grade']; ?>
                                            </span>
                                        </td>
                                        <td style="text-align:center;font-weight:700;background:var(--gray-50);"><?php echo $position++; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<style>
    @media print {
        .sidebar, .navbar, .navbar-content, .sidebar-header, .sidebar-nav, .sidebar-footer,
        .btn, .page-actions, .card-header, .form-row, .form-group { display:none !important; }
        .main-wrapper, .main-content { margin:0 !important; padding:0 !important; }
        .card { border:none !important; box-shadow:none !important; }
        .table { font-size:0.7rem !important; }
        body { background:white !important; }
        .grade-cell { width:20px !important; height:20px !important; font-size:0.6rem !important; }
    }
    .grade-cell { display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;font-weight:800;font-size:0.75rem; }
</style>

<?php include_once '../../includes/footer.php'; ?>
