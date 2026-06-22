<?php include_once '../../includes/auth_check.php'; ?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<?php
// Only admins can view this page
if ($user_role != ROLE_ADMIN) {
    header('Location: ../dashboard.php');
    exit();
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$class_filter = isset($_GET['class']) ? trim($_GET['class']) : '';
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Build the where clause
$where = "1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND (students.first_name LIKE ? OR students.last_name LIKE ? OR students.admission_no LIKE ? OR students.phone LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

if (!empty($class_filter)) {
    $where .= " AND students.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

// Count total students for pagination
$count_sql = "SELECT COUNT(*) as count FROM students WHERE $where";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_students = $count_stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_students / $per_page);

// Fetch students with pagination
$sql = "SELECT 
    students.id,
    students.admission_no,
    students.first_name,
    students.last_name,
    students.middle_name,
    students.phone,
    students.date_of_birth,
    students.gender,
    students.class_id,
    students.is_active,
    students.created_at,
    classes.class_name as class_name
FROM students
LEFT JOIN classes ON students.class_id = classes.id
WHERE $where
ORDER BY students.first_name ASC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$students = $stmt->get_result();

// Get all classes for filter dropdown
$classes_result = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
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

    /* Filter card */
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

    /* Form controls */
    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-end;
    }

    .form-group { display: flex; flex-direction: column; gap: 0.4rem; flex: 1; min-width: 180px; }

    .form-group label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--gray-700);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .form-control {
        padding: 0.55rem 0.9rem;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 0.9rem;
        color: var(--gray-900);
        background: var(--white);
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .form-actions { display: flex; gap: 0.5rem; align-items: flex-end; }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.6rem 1.25rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: inherit;
        white-space: nowrap;
    }

    .btn-primary { background: var(--primary-color); color: var(--white); }
    .btn-primary:hover { background: var(--primary-dark); box-shadow: 0 2px 8px rgba(30, 64, 175, 0.3); }

    .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
    .btn-secondary:hover { background: var(--gray-300); }

    .btn-danger { background: #fee2e2; color: var(--danger-color); border: 1px solid #fecaca; }
    .btn-danger:hover { background: var(--danger-color); color: var(--white); }

    .btn-ghost { background: transparent; color: var(--primary-color); border: 1px solid var(--primary-color); }
    .btn-ghost:hover { background: var(--primary-color); color: var(--white); }

    .btn-sm { padding: 0.4rem 0.85rem; font-size: 0.8rem; }

    /* Summary row */
    .results-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        color: var(--gray-600);
        flex-wrap: wrap;
        gap: 0.5rem;
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
        padding: 0.85rem 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--gray-600);
        border-bottom: 2px solid var(--gray-200);
        white-space: nowrap;
    }

    .table td {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-800);
        vertical-align: middle;
    }

    .table tbody tr:hover { background: var(--gray-50); }
    .table tbody tr:last-child td { border-bottom: none; }

    /* Admission badge */
    .adm-badge {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        font-size: 0.8rem;
        color: var(--primary-color);
        background: var(--primary-light);
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        display: inline-block;
    }

    /* Status badge */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.7rem;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-danger  { background: #fee2e2; color: #991b1b; }

    .badge-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    /* Action links */
    .action-group { display: flex; gap: 0.4rem; justify-content: center; }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 4rem 1.5rem;
        color: var(--gray-400);
    }

    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; display: block; }
    .empty-state p { font-size: 0.95rem; margin-bottom: 1.5rem; }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    .pag-btn {
        padding: 0.5rem 1rem;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 0.85rem;
        color: var(--gray-700);
        background: var(--white);
        text-decoration: none;
        transition: all 0.2s;
        font-weight: 500;
    }

    .pag-btn:hover { background: var(--primary-color); color: var(--white); border-color: var(--primary-color); }
    .pag-label { font-size: 0.85rem; color: var(--gray-600); padding: 0 0.5rem; }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal-overlay.show { display: flex; }

    .modal-box {
        background: var(--white);
        border-radius: 12px;
        padding: 2rem;
        max-width: 420px;
        width: 100%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }

    .modal-icon {
        width: 52px; height: 52px;
        border-radius: 50%;
        background: #fee2e2;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.25rem;
    }

    .modal-icon i { font-size: 1.4rem; color: var(--danger-color); }

    .modal-box h2 {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 0.5rem 0;
    }

    .modal-box p { color: var(--gray-600); margin: 0 0 0.25rem 0; font-size: 0.9rem; }
    .modal-box p.modal-sub { font-size: 0.8rem; color: var(--gray-400); margin-bottom: 1.5rem; }

    .modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; }

    @media (max-width: 768px) {
        .main-content { padding: 1rem; }
        .page-header { flex-direction: column; align-items: flex-start; }
        .form-row { flex-direction: column; }
    }
</style>

<div class="main-wrapper">
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-graduation-cap"></i> Students</h1>
                <div class="breadcrumb">Manage all enrolled students</div>
            </div>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New Student
            </a>
        </div>

        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success" style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:0.85rem 1.25rem;border-radius:10px;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.75rem;font-size:0.9rem;font-weight:500;">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-error" style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:0.85rem 1.25rem;border-radius:10px;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.75rem;font-size:0.9rem;font-weight:500;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Search & Filter -->
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Filter Students</h2>
            </div>
            <div class="card-body">
                <form method="GET" class="form-row">
                    <div class="form-group" style="flex:2;min-width:220px;">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search"
                            placeholder="Name, admission no, phone..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="form-control" />
                    </div>
                    <div class="form-group" style="min-width:180px;max-width:220px;">
                        <label for="class">Class</label>
                        <select id="class" name="class" class="form-control">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?php echo $cls['id']; ?>"
                                    <?php echo $class_filter == $cls['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cls['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <span>
                Showing
                <strong><?php echo $total_students > 0 ? (($page-1)*$per_page)+1 : 0; ?></strong>–<strong><?php echo min($page*$per_page,$total_students); ?></strong>
                of <strong><?php echo $total_students; ?></strong> student<?php echo $total_students != 1 ? 's' : ''; ?>
            </span>
            <?php if (!empty($search) || !empty($class_filter)): ?>
                <span style="color:var(--primary-color);font-weight:600;">
                    <i class="fas fa-filter" style="font-size:0.75rem;"></i> Filtered results
                </span>
            <?php endif; ?>
        </div>

        <!-- Students Table -->
        <div class="card">
            <?php if ($total_students > 0): ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Admission No</th>
                                <th>Full Name</th>
                                <th>Class</th>
                                <th>Gender</th>
                                <th>Date of Birth</th>
                                <th>Phone</th>
                                <th>Enrolled</th>
                                <th>Status</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="adm-badge"><?php echo htmlspecialchars($student['admission_no']); ?></span>
                                    </td>
                                    <td style="font-weight:600;color:var(--gray-900);">
                                        <?php
                                        $full = $student['first_name'];
                                        if (!empty($student['middle_name'])) $full .= ' ' . $student['middle_name'];
                                        $full .= ' ' . $student['last_name'];
                                        echo htmlspecialchars($full);
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['class_name'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($student['gender'] ?? '—'); ?></td>
                                    <td><?php echo $student['date_of_birth'] ? date('d M Y', strtotime($student['date_of_birth'])) : '—'; ?></td>
                                    <td><?php echo htmlspecialchars($student['phone'] ?? '—'); ?></td>
                                    <td><?php echo $student['created_at'] ? date('d M Y', strtotime($student['created_at'])) : '—'; ?></td>
                                    <td>
                                        <?php if ($student['is_active']): ?>
                                            <span class="badge badge-success">
                                                <span class="badge-dot"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">
                                                <span class="badge-dot"></span> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-ghost btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button type="button"
                                                onclick="openDeleteModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'].' '.$student['last_name'], ENT_QUOTES); ?>')"
                                                class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap"></i>
                    <p>No students found<?php echo !empty($search) || !empty($class_filter) ? ' matching your filters' : ''; ?>.</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add the first student
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <?php
            $qs = '';
            if (!empty($search))        $qs .= '&search=' . urlencode($search);
            if (!empty($class_filter))  $qs .= '&class=' . $class_filter;
            ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo $qs; ?>" class="pag-btn"><i class="fas fa-angles-left"></i> First</a>
                    <a href="?page=<?php echo $page-1; ?><?php echo $qs; ?>" class="pag-btn"><i class="fas fa-angle-left"></i> Prev</a>
                <?php endif; ?>

                <span class="pag-label">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?><?php echo $qs; ?>" class="pag-btn">Next <i class="fas fa-angle-right"></i></a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo $qs; ?>" class="pag-btn">Last <i class="fas fa-angles-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon">
            <i class="fas fa-trash-can"></i>
        </div>
        <h2>Delete Student?</h2>
        <p>You are about to delete <strong id="studentName"></strong>.</p>
        <p class="modal-sub">This action is permanent and cannot be undone.</p>
        <div class="modal-actions">
            <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">
                Cancel
            </button>
            <form id="deleteForm" method="POST" action="delete.php" style="display:inline;">
                <input type="hidden" id="studentId" name="student_id" />
                <button type="submit" class="btn btn-primary" style="background:var(--danger-color);">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id, name) {
    document.getElementById('studentId').value = id;
    document.getElementById('studentName').textContent = name;
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

<?php include_once '../../includes/footer.php'; ?>