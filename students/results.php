<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_STUDENT) {
    header('Location: ../dashboard.php');
    exit();
}

// Find student id for this user
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ? LIMIT 1");
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

$student_id = (int)$studentRow['id'];

$sessions = $conn->query("SELECT id, session_name, is_active FROM sessions ORDER BY is_active DESC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms    = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

$session_id = isset($_GET['session']) ? (int)$_GET['session'] : (isset($sessions[0]) ? (int)$sessions[0]['id'] : 0);
$term_id    = isset($_GET['term'])    ? (int)$_GET['term']    : 0;

// Default to latest term for selected session
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
    $stmt = $conn->prepare("SELECT sr.subject_id, s.subject_name, sr.score
        FROM student_results sr
        JOIN subjects s ON sr.subject_id = s.id
        WHERE sr.student_id = ? AND sr.session_id = ? AND sr.term_id = ?
        ORDER BY s.subject_name");
    $stmt->bind_param('iii', $student_id, $session_id, $term_id);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($results as $r) $total_score += $r['score'];
}

$avg_score = count($results) > 0 ? round($total_score / count($results), 1) : 0;

// Grade helper
function getGrade($score) {
    if ($score >= 70) return ['A', 'success'];
    if ($score >= 60) return ['B', 'primary'];
    if ($score >= 50) return ['C', 'warning'];
    if ($score >= 40) return ['D', 'danger'];
    return ['F', 'fail'];
}
?>

<style>
    .main-content { padding: 2rem; }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title .breadcrumb {
        font-size: 0.875rem;
        color: var(--gray-500);
        margin-top: 0.25rem;
    }

    /* Cards */
    .card {
        background: var(--white);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }

    .card-header h2 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin: 0;
    }

    .card-body { padding: 1.5rem; }

    /* Filter form */
    .filter-row {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .form-group { display: flex; flex-direction: column; gap: 0.4rem; }

    .form-group label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--gray-700);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .form-control {
        padding: 0.6rem 0.9rem;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 0.9rem;
        color: var(--gray-900);
        background: var(--white);
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        min-width: 160px;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    /* Summary stat cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-mini {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 10px;
        padding: 1.1rem 1.25rem;
        box-shadow: var(--shadow-sm);
        text-align: center;
    }

    .stat-mini-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--primary-color);
        line-height: 1;
    }

    .stat-mini-label {
        font-size: 0.75rem;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-top: 0.3rem;
        font-weight: 600;
    }

    /* Table */
    .table-wrapper { overflow-x: auto; }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .table thead { background: var(--gray-50); }

    .table th {
        padding: 0.85rem 1.1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--gray-600);
        border-bottom: 2px solid var(--gray-200);
    }

    .table th.right,
    .table td.right { text-align: right; }

    .table td {
        padding: 0.9rem 1.1rem;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-800);
        vertical-align: middle;
    }

    .table tbody tr:hover { background: var(--gray-50); }
    .table tbody tr:last-child td { border-bottom: none; }

    /* Score display */
    .score-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--gray-900);
    }

    /* Grade badges */
    .grade-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .grade-A { background: #d1fae5; color: #065f46; }
    .grade-B { background: #dbeafe; color: #1e40af; }
    .grade-C { background: #fef3c7; color: #92400e; }
    .grade-D { background: #ffedd5; color: #9a3412; }
    .grade-F { background: #fee2e2; color: #991b1b; }

    /* Score bar */
    .score-bar-wrap {
        width: 100%;
        max-width: 120px;
        height: 6px;
        background: var(--gray-200);
        border-radius: 3px;
        overflow: hidden;
        display: inline-block;
        vertical-align: middle;
        margin-left: 0.5rem;
    }

    .score-bar-fill {
        height: 100%;
        border-radius: 3px;
        background: var(--primary-color);
        transition: width 0.4s ease;
    }

    /* Total row */
    .table tfoot td {
        padding: 1rem 1.1rem;
        font-weight: 700;
        background: var(--gray-50);
        border-top: 2px solid var(--gray-200);
        font-size: 0.9rem;
        color: var(--gray-800);
    }

    /* Download btn */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.6rem 1.2rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: inherit;
    }

    .btn-primary { background: var(--primary-color); color: var(--white); }
    .btn-primary:hover { background: var(--primary-dark); }

    .btn-success { background: #059669; color: var(--white); }
    .btn-success:hover { background: #047857; }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3.5rem 1.5rem;
        color: var(--gray-400);
    }

    .empty-state i { font-size: 2.5rem; display: block; margin-bottom: 0.75rem; opacity: 0.4; }
    .empty-state p { font-size: 0.9rem; }

    @media (max-width: 640px) {
        .main-content { padding: 1rem; }
        .filter-row { flex-direction: column; }
        .form-control { min-width: 100%; }
        .score-bar-wrap { display: none; }
    }
</style>

<div class="main-wrapper">
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-chart-bar"></i> My Results</h1>
                <div class="breadcrumb">View your academic performance by term</div>
            </div>
            <?php if (!empty($results)): ?>
                <a href="download.php?session=<?php echo $session_id; ?>&term=<?php echo $term_id; ?>"
                   class="btn btn-success">
                    <i class="fas fa-download"></i> Download CSV
                </a>
            <?php endif; ?>
        </div>

        <!-- Filter Card -->
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h2><i class="fas fa-sliders"></i> Select Period</h2>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-row">
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
                    <div style="display:flex;align-items:flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Results
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Section -->
        <?php if ($session_id > 0 && $term_id > 0): ?>

            <?php if (empty($results)): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-file-circle-question"></i>
                        <p>No results have been uploaded for this selection yet.</p>
                    </div>
                </div>
            <?php else: ?>

                <!-- Summary stats -->
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
                        <?php list($grade) = getGrade($avg_score); ?>
                        <div class="stat-mini-value" style="color:<?php echo $grade==='A'?'#059669':($grade==='B'?'#1d4ed8':($grade==='C'?'#b45309':($grade==='D'?'#c2410c':'#991b1b'))); ?>">
                            <?php echo $grade; ?>
                        </div>
                        <div class="stat-mini-label">Overall Grade</div>
                    </div>
                </div>

                <!-- Results table -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-table-list"></i> Subject Results</h2>
                        <span style="font-size:0.8rem;color:var(--gray-500);font-weight:600;">
                            <?php
                            // Find session and term names
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
                                    <th class="right">Score (/100)</th>
                                    <th class="right">Grade</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $i => $r): ?>
                                    <?php list($grade, $type) = getGrade($r['score']); ?>
                                    <tr>
                                        <td style="color:var(--gray-400);font-size:0.8rem;"><?php echo $i + 1; ?></td>
                                        <td style="font-weight:600;"><?php echo htmlspecialchars($r['subject_name']); ?></td>
                                        <td class="right">
                                            <span class="score-value"><?php echo htmlspecialchars($r['score']); ?></span>
                                        </td>
                                        <td class="right">
                                            <span class="grade-badge grade-<?php echo $grade; ?>">
                                                <?php echo $grade; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="score-bar-wrap">
                                                <div class="score-bar-fill" style="width:<?php echo $r['score']; ?>%;background:<?php echo $grade==='A'?'#059669':($grade==='B'?'var(--primary-color)':($grade==='C'?'#d97706':($grade==='D'?'#ea580c':'#dc2626'))); ?>;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">Total / Average</td>
                                    <td class="right"><?php echo $total_score; ?> / <?php echo $avg_score; ?> avg</td>
                                    <td colspan="2"></td>
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