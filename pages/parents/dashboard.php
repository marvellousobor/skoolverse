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
$child_count = 0;
$unpaid_fees = 0;

if ($parent) {
    $sql = "SELECT s.id, s.first_name, s.last_name, s.admission_no, c.class_name
            FROM (
                SELECT id FROM students WHERE parent_id = ?
                UNION
                SELECT student_id FROM student_parent_links WHERE parent_id = ?
            ) AS linked
            INNER JOIN students s ON linked.id = s.id
            LEFT JOIN classes c ON s.class_id = c.id
            ORDER BY s.first_name, s.last_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $parent['id'], $parent['id']);
    $stmt->execute();
    $children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $child_count = count($children);

    if ($child_count > 0) {
        $child_ids = array_column($children, 'id');
        $ids_placeholder = implode(',', array_fill(0, count($child_ids), '?'));
        $types = str_repeat('i', count($child_ids));
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM student_fees WHERE student_id IN ($ids_placeholder) AND is_paid = 0");
        $stmt->bind_param($types, ...$child_ids);
        $stmt->execute();
        $unpaid_fees = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    }
}
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-home"></i> Parent Dashboard</h1>
                <span class="breadcrumb">Welcome back, <?php echo htmlspecialchars($current_user['email'] ?? ''); ?></span>
            </div>
            <div class="page-actions">
                <a href="children.php" class="btn btn-primary"><i class="fas fa-child"></i> View Children</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Linked Children</div>
                        <div class="stat-card-value"><?php echo $child_count; ?></div>
                        <div class="stat-card-change positive"><i class="fas fa-circle-check"></i> Active records</div>
                    </div>
                    <div class="stat-card-icon primary"><i class="fas fa-graduation-cap"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Unpaid Fees</div>
                        <div class="stat-card-value">₦<?php echo number_format($unpaid_fees, 2); ?></div>
                        <div class="stat-card-change negative"><i class="fas fa-clock"></i> Pending</div>
                    </div>
                    <div class="stat-card-icon warning"><i class="fas fa-money-bill"></i></div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-child"></i> My Children</h2>
                    <a href="children.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-right"></i> View All</a>
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
                                                <a href="fees.php?student_id=<?php echo $child['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-money-bill"></i> Fees</a>
                                                <a href="results.php?student_id=<?php echo $child['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-chart-bar"></i> Results</a>
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
                            <p style="font-size:0.85rem;color:var(--gray-500);">Contact the school administrator to link your children.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-lightning-bolt"></i> Quick Actions</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="children.php" class="btn btn-primary"><i class="fas fa-child"></i> View Children</a>
                        <a href="fees.php?student_id=<?php echo !empty($children) ? $children[0]['id'] : 0; ?>" class="btn btn-secondary <?php echo empty($children) ? 'disabled' : ''; ?>"><i class="fas fa-money-bill"></i> Pay Fees</a>
                        <a href="results.php?student_id=<?php echo !empty($children) ? $children[0]['id'] : 0; ?>" class="btn btn-secondary <?php echo empty($children) ? 'disabled' : ''; ?>"><i class="fas fa-chart-bar"></i> View Results</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
