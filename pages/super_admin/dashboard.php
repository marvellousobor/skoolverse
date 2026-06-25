<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN || !$is_super_admin) {
    header('Location: ../dashboard.php');
    exit();
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';

// Restore real role for view
$real_role = $_SESSION['role'];

// Analytics queries
$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$total_teachers = $conn->query("SELECT COUNT(*) as c FROM teachers")->fetch_assoc()['c'];
$total_parents = $conn->query("SELECT COUNT(*) as c FROM parents")->fetch_assoc()['c'];
$total_admins = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'admin'")->fetch_assoc()['c'];
$active_sessions = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE is_active = 1")->fetch_assoc()['c'];
$total_classes = $conn->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'];
$total_subjects = $conn->query("SELECT COUNT(*) as c FROM subjects WHERE is_active = 1")->fetch_assoc()['c'];
$published_results = $conn->query("SELECT COUNT(DISTINCT student_id) as c FROM student_results WHERE is_published = 1")->fetch_assoc()['c'];
$total_fees_collected = $conn->query("SELECT COALESCE(SUM(amount_paid),0) as c FROM payments")->fetch_assoc()['c'];
$outstanding_fees = $conn->query("SELECT COALESCE(SUM(amount),0) as c FROM student_fees WHERE is_paid = 0")->fetch_assoc()['c'];
$users_by_role = $conn->query("SELECT role, COUNT(*) as c FROM users GROUP BY role")->fetch_all(MYSQLI_ASSOC);

// Recent admin activity
$recent_admins = $conn->query("SELECT id, username, email, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Users per day (last 30 days)
$users_chart = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY date")->fetch_all(MYSQLI_ASSOC);
?>
<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-crown"></i> Super Admin Dashboard</h1>
                <div class="breadcrumb">System-wide analytics and admin management</div>
            </div>
            <div class="page-actions">
                <a href="admins.php" class="btn btn-primary">
                    <i class="fas fa-user-shield"></i> Manage Admins
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Students</div>
                        <div class="stat-card-value"><?php echo $total_students; ?></div>
                    </div>
                    <div class="stat-card-icon primary"><i class="fas fa-graduation-cap"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Teachers</div>
                        <div class="stat-card-value"><?php echo $total_teachers; ?></div>
                    </div>
                    <div class="stat-card-icon success"><i class="fas fa-chalkboard-user"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Parents</div>
                        <div class="stat-card-value"><?php echo $total_parents; ?></div>
                    </div>
                    <div class="stat-card-icon warning"><i class="fas fa-people-roof"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Admins</div>
                        <div class="stat-card-value"><?php echo $total_admins; ?></div>
                    </div>
                    <div class="stat-card-icon danger"><i class="fas fa-user-shield"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Classes</div>
                        <div class="stat-card-value"><?php echo $total_classes; ?></div>
                    </div>
                    <div class="stat-card-icon primary"><i class="fas fa-school"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Subjects</div>
                        <div class="stat-card-value"><?php echo $total_subjects; ?></div>
                    </div>
                    <div class="stat-card-icon success"><i class="fas fa-book"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Active Sessions</div>
                        <div class="stat-card-value"><?php echo $active_sessions; ?></div>
                    </div>
                    <div class="stat-card-icon warning"><i class="fas fa-calendar"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Results Published</div>
                        <div class="stat-card-value"><?php echo $published_results; ?></div>
                    </div>
                    <div class="stat-card-icon success"><i class="fas fa-certificate"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Fees Collected</div>
                        <div class="stat-card-value">₦<?php echo number_format($total_fees_collected); ?></div>
                    </div>
                    <div class="stat-card-icon success"><i class="fas fa-money-bill"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Outstanding</div>
                        <div class="stat-card-value">₦<?php echo number_format($outstanding_fees); ?></div>
                    </div>
                    <div class="stat-card-icon danger"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>

        <!-- Users by Role -->
        <div class="content-grid" style="grid-template-columns:1fr 1fr;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> Users by Role</h2>
                </div>
                <div class="card-body">
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        <?php foreach ($users_by_role as $ur): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--gray-100);">
                                <span style="font-weight:500;color:var(--gray-700);text-transform:capitalize;">
                                    <?php echo htmlspecialchars($ur['role']); ?>
                                </span>
                                <span class="badge badge-info"><?php echo $ur['c']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Admins -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-shield-halved"></i> Admin Accounts</h2>
                    <a href="admins.php" class="btn btn-ghost btn-sm">
                        <i class="fas fa-arrow-right"></i> Manage
                    </a>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (count($recent_admins) > 0): ?>
                        <div style="display:flex;flex-direction:column;">
                            <?php foreach ($recent_admins as $a): ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 1rem;border-bottom:1px solid var(--gray-100);">
                                    <div>
                                        <div style="font-weight:600;color:var(--gray-900);font-size:0.9rem;">
                                            <?php echo htmlspecialchars($a['username']); ?>
                                        </div>
                                        <div style="font-size:0.8rem;color:var(--gray-400);">
                                            <?php echo htmlspecialchars($a['email']); ?>
                                        </div>
                                    </div>
                                    <span style="font-size:0.78rem;color:var(--gray-400);">
                                        <?php echo date('d M Y', strtotime($a['created_at'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding:2rem;">
                            <i class="fas fa-user-shield"></i>
                            <p>No admin accounts yet.</p>
                            <a href="create_admin.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Admin
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include_once '../../includes/footer.php'; ?>
