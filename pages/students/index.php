<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN) {
    header('Location: ../dashboard.php');
    exit();
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
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
    students.department,
    students.is_active,
    students.created_at,
    classes.class_name as class_name,
    classes.level as class_level
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
    /* Students page uses shared styles from assets/css/app.css */
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
                                <th>Department</th>
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
                                    <td>
                                        <?php if ($student['class_level'] === 'Senior Secondary School' && !empty($student['department'])): ?>
                                            <span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($student['department'])); ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--gray-400);font-size:0.8rem;">—</span>
                                        <?php endif; ?>
                                    </td>
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