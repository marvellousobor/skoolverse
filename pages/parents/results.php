<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_PARENT) {
    header('Location: ../dashboard.php');
    exit();
}

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($student_id <= 0) {
    header('Location: children.php');
    exit();
}

$stmt = $conn->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();

if (!$parent) {
    header('Location: children.php');
    exit();
}

$stmt = $conn->prepare("SELECT 1 FROM students WHERE id = ? AND (parent_id = ? OR id IN (SELECT student_id FROM student_parent_links WHERE parent_id = ?))");
$stmt->bind_param('iii', $student_id, $parent['id'], $parent['id']);
$stmt->execute();
$has_permission = $stmt->get_result()->num_rows > 0;

if (!$has_permission) {
    include_once '../../includes/header.php';
    include_once '../../includes/navbar.php';
    echo '<div class="main-wrapper"><main class="main-content"><div class="card"><div class="card-body"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-lock"></i></div><p>You do not have permission to view results for this student.</p><a href="children.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Children</a></div></div></div></main></div>';
    include_once '../../includes/footer.php';
    exit();
}

include_once '../../includes/grading_helper.php';

// Get student class_id for position calculation
$student_info = $conn->query("SELECT class_id FROM students WHERE id = $student_id")->fetch_assoc();
$class_id = $student_info ? (int)$student_info['class_id'] : 0;

$sessions = $conn->query("SELECT id, session_name, is_active FROM sessions ORDER BY is_active DESC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

$session_id = isset($_GET['session']) ? (int)$_GET['session'] : (isset($sessions[0]) ? (int)$sessions[0]['id'] : 0);
$term_id = isset($_GET['term']) ? (int)$_GET['term'] : 0;

if ($term_id <= 0 && $session_id > 0) {
    $tstmt = $conn->prepare("SELECT id FROM terms WHERE session_id = ? ORDER BY id DESC LIMIT 1");
    $tstmt->bind_param('i', $session_id);
    $tstmt->execute();
    $trow = $tstmt->get_result()->fetch_assoc();
    if ($trow) {
        $term_id = (int)$trow['id'];
    }
}

$results = [];
if ($session_id > 0 && $term_id > 0) {
    $stmt = $conn->prepare("SELECT sr.subject_id, s.subject_name, sr.score, sr.ca_score, sr.exam_score FROM student_results sr JOIN subjects s ON sr.subject_id = s.id WHERE sr.student_id = ? AND sr.session_id = ? AND sr.term_id = ? AND sr.is_published = 1 ORDER BY s.subject_name");
    $stmt->bind_param('iii', $student_id, $session_id, $term_id);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$total = 0; $count = count($results);
foreach ($results as $r) { $total += (float)$r['score']; }
$avg = $count > 0 ? round($total / $count, 2) : 0;
$grade = getGrade($avg);
$pos = getClassPosition($student_id, $class_id, $session_id, $term_id);

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
?>

<style>
    .grade-cell {
        display:inline-flex;align-items:center;justify-content:center;
        width:32px;height:32px;border-radius:50%;
        font-weight:800;font-size:0.8rem;
    }
</style>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-chart-bar"></i> Results</h1>
                <span class="breadcrumb">My Children / Results</span>
            </div>
            <div class="page-actions">
                <a href="children.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                <?php if (!empty($results)): ?>
                    <a href="download.php?student_id=<?php echo $student_id; ?>&session=<?php echo $session_id; ?>&term=<?php echo $term_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Download CSV
                    </a>
                    <a href="<?php echo BASE_URL; ?>pages/results/report-card.php?student_id=<?php echo $student_id; ?>&session=<?php echo $session_id; ?>&term=<?php echo $term_id; ?>" class="btn btn-primary" target="_blank">
                        <i class="fas fa-file-pdf"></i> Report Card
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($results)): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Subjects</div>
                        <div class="stat-card-value"><?php echo $count; ?></div>
                    </div>
                    <div class="stat-card-icon primary"><i class="fas fa-book"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Total Score</div>
                        <div class="stat-card-value"><?php echo number_format($total, 2); ?></div>
                    </div>
                    <div class="stat-card-icon success"><i class="fas fa-calculator"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Average</div>
                        <div class="stat-card-value"><?php echo number_format($avg, 2); ?></div>
                    </div>
                    <div class="stat-card-icon warning"><i class="fas fa-percent"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Grade</div>
                        <div class="stat-card-value">
                            <span style="<?php echo getGradeStyle($grade['grade']); ?>padding:0.15rem 0.75rem;border-radius:8px;display:inline-block;font-size:1.5rem;">
                                <?php echo $grade['grade']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="stat-card-icon info"><i class="fas fa-layer-group"></i></div>
                </div>
                <div style="font-size:0.8rem;color:var(--gray-500);margin-top:0.25rem;"><?php echo htmlspecialchars($grade['remark']); ?></div>
            </div>
            <?php if ($pos['position'] > 0): ?>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Class Position</div>
                        <div class="stat-card-value" style="font-size:1.5rem;"><?php echo ordinal($pos['position']); ?></div>
                    </div>
                    <div class="stat-card-icon primary"><i class="fas fa-ranking-star"></i></div>
                </div>
                <div style="font-size:0.8rem;color:var(--gray-500);margin-top:0.25rem;">out of <?php echo $pos['out_of']; ?> students</div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Filter Results</h2>
            </div>
            <div class="card-body">
                <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;align-items:end;">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <div>
                        <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--gray-700);margin-bottom:0.5rem;">Session</label>
                        <select name="session" style="width:100%;padding:0.6rem;border:1px solid var(--gray-300);border-radius:8px;">
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $s['id']==$session_id?'selected':''; ?>><?php echo htmlspecialchars($s['session_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--gray-700);margin-bottom:0.5rem;">Term</label>
                        <select name="term" style="width:100%;padding:0.6rem;border:1px solid var(--gray-300);border-radius:8px;">
                            <option value="0">-- Select Term --</option>
                            <?php foreach ($terms as $t): ?>
                                <?php if ($t['session_id'] == $session_id): ?>
                                    <option value="<?php echo $t['id']; ?>" <?php echo $t['id']==$term_id?'selected':''; ?>><?php echo htmlspecialchars($t['term_name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> View Results</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top:1.5rem;">
            <div class="card-header">
                <h2><i class="fas fa-list-ol"></i> Subject Scores</h2>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if ($session_id > 0 && $term_id > 0): ?>
                    <?php if (empty($results)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
                            <p>No results uploaded for this selection.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th class="right">CA (40)</th>
                                        <th class="right">Exam (70)</th>
                                        <th class="right">Total</th>
                                        <th class="right">Grade</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $r): ?>
                                        <?php $subj_grade = getGrade($r['score']); ?>
                                        <tr>
                                            <td style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($r['subject_name']); ?></td>
                                            <td class="right"><?php echo $r['ca_score'] !== null ? htmlspecialchars($r['ca_score']) : '—'; ?></td>
                                            <td class="right"><?php echo $r['exam_score'] !== null ? htmlspecialchars($r['exam_score']) : '—'; ?></td>
                                            <td class="right"><span class="score-value"><?php echo htmlspecialchars($r['score']); ?></span></td>
                                            <td class="right">
                                                <span class="grade-cell" style="<?php echo getGradeStyle($subj_grade['grade']); ?>">
                                                    <?php echo $subj_grade['grade']; ?>
                                                </span>
                                            </td>
                                            <td style="font-size:0.8rem;color:var(--gray-500);"><?php echo htmlspecialchars($subj_grade['remark']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-hand-pointer"></i></div>
                        <p>Choose a session and term to view results.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
