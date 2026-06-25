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

// Get all classes, sessions, and terms
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name FROM sessions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Get filter parameters
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$session_filter = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$term_filter = isset($_GET['term']) ? (int)$_GET['term'] : 0;

// Get subjects
$subjects = $conn->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);

// Build query for results summary
$where = "1=1";
$params = [];
$types = "";

if ($class_filter > 0) {
    $where .= " AND sr.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

if ($session_filter > 0) {
    $where .= " AND sr.session_id = ?";
    $params[] = $session_filter;
    $types .= "i";
}

if ($term_filter > 0) {
    $where .= " AND sr.term_id = ?";
    $params[] = $term_filter;
    $types .= "i";
}

$sql = "SELECT 
    COUNT(DISTINCT sr.student_id) as total_students,
    COUNT(DISTINCT sr.subject_id) as total_subjects,
    sr.class_id, sr.session_id, sr.term_id,
    c.class_name, s.session_name, t.term_name
FROM student_results sr
LEFT JOIN classes c ON sr.class_id = c.id
LEFT JOIN sessions s ON sr.session_id = s.id
LEFT JOIN terms t ON sr.term_id = t.id
WHERE $where
GROUP BY sr.class_id, sr.session_id, sr.term_id, c.class_name, s.session_name, t.term_name
ORDER BY s.id DESC, t.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results_summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<style>
    /* ═══════════════════ RESULTS STYLES ═══════════════════ */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .page-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .page-title h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
    }
    
    .page-title .breadcrumb {
        font-size: 0.875rem;
        color: var(--gray-500);
    }
    
    .page-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    
    /* ═══════════════════ FILTER SECTION ═══════════════════ */
    .filter-section {
        background: var(--white);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        margin-bottom: 2rem;
    }
    
    .filter-section-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }
    
    .filter-section-header h2 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0;
    }
    
    .filter-section-body {
        padding: 1.5rem;
    }
    
    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        align-items: end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .filter-group label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-700);
    }
    
    .filter-group select {
        padding: 0.75rem;
        border: 1px solid var(--gray-300);
        border-radius: 6px;
        font-size: 0.95rem;
        background: var(--white);
        transition: border-color 0.2s ease;
    }
    
    .filter-group select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.1);
    }
    
    .filter-actions {
        display: flex;
        gap: 0.75rem;
    }
    
    /* ═══════════════════ RESULTS CARDS ═══════════════════ */
    .results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
    }
    
    .result-card {
        background: var(--white);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }
    
    .result-card:hover {
        box-shadow: var(--shadow-md);
    }
    
    .result-card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }
    
    .result-card-header h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0;
    }
    
    .result-card-body {
        padding: 1.5rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .stat-box {
        padding: 1rem;
        border-radius: 8px;
        background: var(--gray-50);
    }
    
    .stat-box p {
        font-size: 0.875rem;
        color: var(--gray-600);
        margin-bottom: 0.25rem;
    }
    
    .stat-box .value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-900);
    }
    
    .stat-box.blue .value {
        color: var(--primary-color);
    }
    
    .stat-box.green .value {
        color: var(--success-color);
    }
    
    /* ═══════════════════ BUTTONS ═══════════════════ */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: 'Segoe UI', sans-serif;
    }
    
    .btn-primary {
        background: var(--primary-color);
        color: var(--white);
    }
    
    .btn-primary:hover {
        background: var(--primary-dark);
        box-shadow: 0 2px 8px rgba(30, 64, 175, 0.3);
    }
    
    .btn-secondary {
        background: var(--gray-200);
        color: var(--gray-900);
    }
    
    .btn-secondary:hover {
        background: var(--gray-300);
    }
    
    .btn-success {
        background: var(--success-color);
        color: var(--white);
    }
    
    .btn-success:hover {
        background: #047857;
        box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
    }
    
    /* ═══════════════════ EMPTY STATE ═══════════════════ */
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: var(--gray-400);
    }
    
    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .empty-state p {
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
    }
    
    /* ═══════════════════ RESPONSIVE ═══════════════════ */
    @media (max-width: 768px) {
        .main-content {
            padding: 1rem;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .page-title h1 {
            font-size: 1.5rem;
        }
        
        .filter-form {
            grid-template-columns: 1fr;
        }
        
        .results-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Main Content Wrapper -->
<div class="main-wrapper">
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-chart-bar"></i> Results Management</h1>
                <span class="breadcrumb">Manage student results</span>
            </div>
            <div class="page-actions">
                <a href="upload.php" class="btn btn-primary">
                    <i class="fas fa-file-upload"></i>
                    Upload CSV
                </a>
                <a href="download-template.php" class="btn btn-success">
                    <i class="fas fa-file-download"></i>
                    Download Template
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-section-header">
                <h2><i class="fas fa-filter"></i> Filter Results</h2>
            </div>
            <div class="filter-section-body">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="class">Class</label>
                        <select name="class" id="class" class="w-full">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?php echo $cls['id']; ?>" <?php echo $class_filter == $cls['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cls['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="session">Session</label>
                        <select name="session" id="session" class="w-full">
                            <option value="">All Sessions</option>
                            <?php foreach ($sessions as $sess): ?>
                                <option value="<?php echo $sess['id']; ?>" <?php echo $session_filter == $sess['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sess['session_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="term">Term</label>
                        <select name="term" id="term" class="w-full">
                            <option value="">All Terms</option>
                            <?php foreach ($terms as $trm): ?>
                                <option value="<?php echo $trm['id']; ?>" <?php echo $term_filter == $trm['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($trm['term_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Filter
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Results Summary -->
        <?php if (empty($results_summary)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <p>No results found.</p>
                <a href="upload.php" class="btn btn-primary">
                    <i class="fas fa-file-upload"></i>
                    Upload Results
                </a>
            </div>
        <?php else: ?>
            <div class="results-grid">
                <?php foreach ($results_summary as $summary): ?>
                    <div class="result-card">
                        <div class="result-card-header">
                            <h3>
                                <i class="fas fa-school"></i>
                                <?php echo htmlspecialchars($summary['class_name'] ?? 'N/A'); ?>
                                <span class="text-gray-500">-</span>
                                <?php echo htmlspecialchars($summary['session_name'] ?? 'N/A'); ?>
                                <span class="text-gray-500">-</span>
                                <?php echo htmlspecialchars($summary['term_name'] ?? 'N/A'); ?>
                            </h3>
                        </div>
                        <div class="result-card-body">
                            <div class="stats-grid">
                                <div class="stat-box blue">
                                    <p>Total Students</p>
                                    <div class="value"><?php echo $summary['total_students']; ?></div>
                                </div>
                                <div class="stat-box green">
                                    <p>Subjects</p>
                                    <div class="value"><?php echo $summary['total_subjects']; ?></div>
                                </div>
                            </div>
                            <a href="view.php?class=<?php echo $summary['class_id']; ?>&session=<?php echo $summary['session_id']; ?>&term=<?php echo $summary['term_id']; ?>" 
                               class="btn btn-primary" style="width: 100%; justify-content: center;">
                                <i class="fas fa-eye"></i>
                                View & Edit Results
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Include Footer -->
<?php include_once '../../includes/footer.php'; ?>
