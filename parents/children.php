<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_PARENT) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

$stmt = $conn->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();
$children = [];

if ($parent) {
    $sql = "SELECT students.id AS student_id, students.admission_no, students.first_name, students.last_name, classes.class_name
            FROM (
                SELECT id AS student_id FROM students WHERE parent_id = ?
                UNION
                SELECT student_id FROM student_parent_links WHERE parent_id = ?
            ) AS linked_students
            INNER JOIN students ON linked_students.student_id = students.id
            LEFT JOIN classes ON students.class_id = classes.id
            ORDER BY students.first_name, students.last_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $parent['id'], $parent['id']);
    $stmt->execute();
    $children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-children"></i> My Children</h1>
                <span class="breadcrumb">Dashboard / My Children</span>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Linked Children</div>
                        <div class="stat-card-value"><?php echo count($children); ?></div>
                        <div class="stat-card-change positive">
                            <i class="fas fa-circle-check"></i> Active records
                        </div>
                    </div>
                    <div class="stat-card-icon primary">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Children Registry</h2>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (!empty($children)): ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Admission No</th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($children as $child): ?>
                                    <tr>
                                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($child['admission_no']); ?></span></td>
                                        <td style="font-weight:600;color:var(--gray-900);"><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($child['class_name'] ?? '—'); ?></td>
                                        <td style="text-align:right;">
                                            <a href="fees.php?student_id=<?php echo urlencode($child['student_id']); ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-money-bill"></i> Pay Fees
                                            </a>
                                            <a href="results.php?student_id=<?php echo urlencode($child['student_id']); ?>" class="btn btn-outline btn-sm">
                                                <i class="fas fa-chart-bar"></i> Results
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-user-slash"></i></div>
                        <p>No children linked to this account yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
