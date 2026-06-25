<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_STUDENT) {
    header('Location: ../dashboard.php');
    exit();
}

$stmt = $conn->prepare("SELECT id, class_id FROM students WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$studentRow = $stmt->get_result()->fetch_assoc();

if (!$studentRow) {
    include_once '../../includes/header.php';
    include_once '../../includes/navbar.php';
    echo '<div class="main-wrapper"><main class="main-content"><div style="padding:2rem;"><div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:1rem 1.25rem;border-radius:10px;display:flex;align-items:center;gap:0.75rem;">
        <i class="fas fa-exclamation-circle"></i>
        Student profile not found. Please contact the administrator.
    </div></div></main></div>';
    include_once '../../includes/footer.php';
    exit();
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
include_once '../../includes/grading_helper.php';

$student_id = (int)$studentRow['id'];
$class_id = (int)$studentRow['class_id'];

$sessions = $conn->query("SELECT id, session_name, is_active FROM sessions ORDER BY is_active DESC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms    = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

$session_id = isset($_GET['session']) ? (int)$_GET['session'] : (isset($sessions[0]) ? (int)$sessions[0]['id'] : 0);
$term_id    = isset($_GET['term'])    ? (int)$_GET['term']    : 0;

if ($term_id <= 0 && $session_id > 0) {
    $tstmt = $conn->prepare("SELECT id FROM terms WHERE session_id = ? ORDER BY id DESC LIMIT 1");
    $tstmt->bind_param('i', $session_id);
    $tstmt->execute();
    $trow = $tstmt->get_result()->fetch_assoc();
    if ($trow) $term_id = (int)$trow['id'];
}

$results = [];
$total_score = 0;
if ($session_id > 0 && $term_id > 0) {
    $stmt = $conn->prepare("SELECT sr.subject_id, s.subject_name, sr.score, sr.ca_score, sr.exam_score
        FROM student_results sr
        JOIN subjects s ON sr.subject_id = s.id
        WHERE sr.student_id = ? AND sr.session_id = ? AND sr.term_id = ? AND sr.is_published = 1
        ORDER BY s.subject_name");
    $stmt->bind_param('iii', $student_id, $session_id, $term_id);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($results as $r) $total_score += $r['score'];
}

$avg_score = count($results) > 0 ? round($total_score / count($results), 1) : 0;
$grade = getGrade($avg_score);
$pos = getClassPosition($student_id, $class_id, $session_id, $term_id);
?>

<style>
    .table tfoot td {
        padding: 1rem 1.1rem;
        font-weight: 700;
        background: var(--gray-50);
        border-top: 2px solid var(--gray-200);
        font-size: 0.9rem;
        color: var(--gray-800);
    }
    .grade-cell {
        display:inline-flex;align-items:center;justify-content:center;
        width:32px;height:32px;border-radius:50%;
        font-weight:800;font-size:0.8rem;
    }
    @media (max-width: 640px) { .score-bar-wrap { display: none; } }
</style>

<div class="main-wrapper">
    <main class="main-content">

        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-chart-bar"></i> My Results</h1>
                <div class="breadcrumb">View your academic performance by term</div>
            </div>
            <?php if (!empty($results)): ?>
                <div class="page-actions">
                    <a href="download.php?session=<?php echo $session_id; ?>&term=<?php echo $term_id; ?>"
                       class="btn btn-secondary">
                        <i class="fas fa-download"></i> Download CSV
                    </a>
                    <a href="<?php echo BASE_URL; ?>pages/results/report-card.php?student_id=<?php echo $student_id; ?>&session=<?php echo $session_id; ?>&term=<?php echo $term_id; ?>"
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-file-pdf"></i> Report Card
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h2><i class="fas fa-sliders"></i> Select Period</h2>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-row" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
                    <div class="form-group">
                        <label>Session</label>
                        <select name="session" class="form-control">
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?php echo $s['id']; ?>"
                                    <?php echo $s['id'] == $session_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['session_name']); ?>
                                    <?php echo $s['is_active'] ? ' (Current)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Term</label>
                        <select name="term" class="form-control">
                            <option value="0">Select Term</option>
                            <?php foreach ($terms as $t): ?>
                                <?php if ($t['session_id'] == $session_id): ?>
                                    <option value="<?php echo $t['id']; ?>"
                                        <?php echo $t['id'] == $term_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($t['term_name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Results
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($session_id > 0 && $term_id > 0): ?>

            <?php if (empty($results)): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-file-circle-question"></i>
                        <p>No results have been published for this selection yet.</p>
                    </div>
                </div>
            <?php else: ?>

                <div class="stats-row">
                    <div class="stat-mini">
                        <div class="stat-mini-value"><?php echo count($results); ?></div>
                        <div class="stat-mini-label">Subjects</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-value"><?php echo $total_score; ?></div>
                        <div class="stat-mini-label">Total Score</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-value"><?php echo $avg_score; ?></div>
                        <div class="stat-mini-label">Average</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-value" style="<?php echo getGradeStyle($grade['grade']); ?>padding:0.25rem 0.5rem;border-radius:8px;display:inline-block;font-size:1.3rem;">
                            <?php echo $grade['grade']; ?>
                        </div>
                        <div class="stat-mini-label"><?php echo htmlspecialchars($grade['remark']); ?></div>
                    </div>
                    <?php if ($pos['position'] > 0): ?>
                    <div class="stat-mini">
                        <div class="stat-mini-value" style="font-size:1.3rem;"><?php echo ordinal($pos['position']); ?></div>
                        <div class="stat-mini-label">of <?php echo $pos['out_of']; ?> students</div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-table-list"></i> Subject Results</h2>
                        <span style="font-size:0.8rem;color:var(--gray-500);font-weight:600;">
                            <?php
                            foreach ($sessions as $s) { if ($s['id'] == $session_id) { echo htmlspecialchars($s['session_name']); break; } }
                            echo ' &mdash; ';
                            foreach ($terms as $t) { if ($t['id'] == $term_id) { echo htmlspecialchars($t['term_name']); break; } }
                            ?>
                        </span>
                    </div>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Subject</th>
                                    <th class="right">CA (40)</th>
                                    <th class="right">Exam (70)</th>
                                    <th class="right">Total</th>
                                    <th class="right">Grade</th>
                                    <th>Remark</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $i => $r): ?>
                                    <?php $subj_grade = getGrade($r['score']); ?>
                                    <tr>
                                        <td style="color:var(--gray-400);font-size:0.8rem;"><?php echo $i + 1; ?></td>
                                        <td style="font-weight:600;"><?php echo htmlspecialchars($r['subject_name']); ?></td>
                                        <td class="right"><?php echo $r['ca_score'] !== null ? htmlspecialchars($r['ca_score']) : '—'; ?></td>
                                        <td class="right"><?php echo $r['exam_score'] !== null ? htmlspecialchars($r['exam_score']) : '—'; ?></td>
                                        <td class="right"><span class="score-value"><?php echo htmlspecialchars($r['score']); ?></span></td>
                                        <td class="right">
                                            <span class="grade-cell" style="<?php echo getGradeStyle($subj_grade['grade']); ?>">
                                                <?php echo $subj_grade['grade']; ?>
                                            </span>
                                        </td>
                                        <td style="font-size:0.8rem;color:var(--gray-500);"><?php echo htmlspecialchars($subj_grade['remark']); ?></td>
                                        <td>
                                            <div class="score-bar-wrap">
                                                <div class="score-bar-fill" style="width:<?php echo $r['score']; ?>%;background:<?php echo getGradeStyle($subj_grade['grade']); ?>"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4">Total / Average</td>
                                    <td class="right"><?php echo $total_score; ?> / <?php echo $avg_score; ?></td>
                                    <td class="right">
                                        <span class="grade-cell" style="<?php echo getGradeStyle($grade['grade']); ?>"><?php echo $grade['grade']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($grade['remark']); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
