<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN) {
    header('Location: ../dashboard.php');
    exit();
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
?>

<?php
include_once '../../includes/results_schema.php';
ensure_results_schema($conn);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_action'])) {
    $action = $_POST['publish_action'];
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    $term_id = isset($_POST['term_id']) ? (int)$_POST['term_id'] : 0;
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;

    if ($class_id <= 0 || $session_id <= 0 || $term_id <= 0) {
        $error = 'Please select a valid class, session, and term.';
    } elseif ($action === 'publish') {
        $sql = "UPDATE student_results SET is_published = 1, published_by_admin_id = ?, published_at = NOW() WHERE class_id = ? AND session_id = ? AND term_id = ?";
        $types = 'iiii';
        $params = [$user_id, $class_id, $session_id, $term_id];
        if ($teacher_id > 0) {
            $sql .= " AND uploaded_by_teacher_id = ?";
            $types .= 'i';
            $params[] = $teacher_id;
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $success = 'Selected results published successfully.';
        } else {
            $error = 'Error publishing results: ' . $conn->error;
        }
    } elseif ($action === 'unpublish') {
        $stmt = $conn->prepare("UPDATE student_results SET is_published = 0, published_by_admin_id = NULL, published_at = NULL WHERE class_id = ? AND session_id = ? AND term_id = ?");
        $stmt->bind_param('iii', $class_id, $session_id, $term_id);
        if ($stmt->execute()) {
            $success = 'Result set moved back to draft.';
        } else {
            $error = 'Error updating publication status: ' . $conn->error;
        }
    }
}

$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$session_filter = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$term_filter = isset($_GET['term']) ? (int)$_GET['term'] : 0;

$where = '1=1';
$params = [];
$types = '';

if ($class_filter > 0) {
    $where .= ' AND sr.class_id = ?';
    $params[] = $class_filter;
    $types .= 'i';
}

if ($session_filter > 0) {
    $where .= ' AND sr.session_id = ?';
    $params[] = $session_filter;
    $types .= 'i';
}

if ($term_filter > 0) {
    $where .= ' AND sr.term_id = ?';
    $params[] = $term_filter;
    $types .= 'i';
}

// Query with teacher info
$sql = "SELECT 
    sr.class_id,
    sr.session_id,
    sr.term_id,
    sr.uploaded_by_teacher_id,
    COUNT(*) AS total_results,
    COUNT(DISTINCT sr.student_id) AS total_students,
    COUNT(DISTINCT sr.subject_id) AS total_subjects,
    SUM(CASE WHEN sr.is_published = 1 THEN 1 ELSE 0 END) AS published_results,
    c.class_name,
    s.session_name,
    t.term_name,
    COALESCE(tch.full_name, 'Admin') AS submitted_by_name
FROM student_results sr
LEFT JOIN classes c ON sr.class_id = c.id
LEFT JOIN sessions s ON sr.session_id = s.id
LEFT JOIN terms t ON sr.term_id = t.id
LEFT JOIN teachers tch ON sr.uploaded_by_teacher_id = tch.id
WHERE $where
GROUP BY sr.class_id, sr.session_id, sr.term_id, sr.uploaded_by_teacher_id, c.class_name, s.session_name, t.term_name, tch.full_name
ORDER BY s.id DESC, t.id DESC, submitted_by_name";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results_summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-chart-line"></i> Results Management</h1>
                <div class="breadcrumb">Review teacher submissions, cross-check scores, then publish for parents and students.</div>
            </div>
            <div class="page-actions" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <a href="broadsheet.php" class="btn btn-outline"><i class="fas fa-table-cells-large"></i> Broadsheet</a>
                <a href="download-template.php" class="btn btn-success"><i class="fas fa-download"></i> Template</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="card" style="margin-bottom:1.25rem;border-left:4px solid #dc2626;">
                <div class="card-body" style="padding:1rem 1.25rem;color:#b91c1c;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="card" style="margin-bottom:1.25rem;border-left:4px solid #059669;">
                <div class="card-body" style="padding:1rem 1.25rem;color:#047857;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Filter Submissions</h2>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-row">
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class" class="form-control">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?php echo $cls['id']; ?>" <?php echo $class_filter == $cls['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cls['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Session</label>
                        <select name="session" class="form-control">
                            <option value="">All Sessions</option>
                            <?php foreach ($sessions as $sess): ?>
                                <option value="<?php echo $sess['id']; ?>" <?php echo $session_filter == $sess['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sess['session_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Term</label>
                        <select name="term" class="form-control">
                            <option value="">All Terms</option>
                            <?php foreach ($terms as $trm): ?>
                                <option value="<?php echo $trm['id']; ?>" <?php echo $term_filter == $trm['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($trm['term_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:0.75rem;align-items:flex-end;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($results_summary)): ?>
            <div class="card">
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>No results have been submitted yet.</p>
                    <p style="margin-top:0.5rem;font-size:0.9rem;color:var(--gray-500);">Teachers can upload scores via CSV or enter them manually. Once submitted, you can review and publish them here.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrapper card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Session</th>
                            <th>Term</th>
                            <th>Submitted By</th>
                            <th class="right">Students</th>
                            <th class="right">Subjects</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results_summary as $summary): ?>
                            <?php
                            $totalResults = (int)$summary['total_results'];
                            $publishedResults = (int)$summary['published_results'];
                            $statusLabel = 'Draft';
                            $statusClass = 'badge-warning';

                            if ($totalResults > 0 && $publishedResults === $totalResults) {
                                $statusLabel = 'Published';
                                $statusClass = 'badge-success';
                            } elseif ($publishedResults > 0) {
                                $statusLabel = 'Partially Published';
                                $statusClass = 'badge-warning';
                            }

                            $teacher_id_param = (int)$summary['uploaded_by_teacher_id'];
                            $view_url = "view.php?class={$summary['class_id']}&session={$summary['session_id']}&term={$summary['term_id']}" . ($teacher_id_param > 0 ? "&teacher=$teacher_id_param" : '');
                            ?>
                            <tr>
                                <td style="font-weight:600;color:var(--gray-900);">
                                    <?php echo htmlspecialchars($summary['class_name'] ?? 'N/A'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($summary['session_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($summary['term_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-primary"><?php echo htmlspecialchars($summary['submitted_by_name']); ?></span>
                                </td>
                                <td class="right"><?php echo (int)$summary['total_students']; ?></td>
                                <td class="right"><?php echo (int)$summary['total_subjects']; ?></td>
                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                                <td>
                                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                        <a href="<?php echo $view_url; ?>" class="btn btn-secondary"><i class="fas fa-eye"></i> Review</a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="class_id" value="<?php echo (int)$summary['class_id']; ?>">
                                            <input type="hidden" name="session_id" value="<?php echo (int)$summary['session_id']; ?>">
                                            <input type="hidden" name="term_id" value="<?php echo (int)$summary['term_id']; ?>">
                                            <input type="hidden" name="teacher_id" value="<?php echo $teacher_id_param; ?>">
                                            <?php if ($statusLabel === 'Published'): ?>
                                                <input type="hidden" name="publish_action" value="unpublish">
                                                <button type="submit" class="btn btn-secondary" onclick="return confirm('Move this result set back to draft?');"><i class="fas fa-undo"></i> Unpublish</button>
                                            <?php else: ?>
                                                <input type="hidden" name="publish_action" value="publish">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Publish this submission? Students and parents will see it.');"><i class="fas fa-check"></i> Publish</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
