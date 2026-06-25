<?php
if (!isset($user_role)) {
    include_once __DIR__ . '/../includes/auth_check.php';
}
require_once __DIR__ . '/notifications_helper.php';
$unread_count = get_unread_notification_count($conn, $user_id, $user_role);
$recent_notifs = get_recent_notifications($conn, $user_id, $user_role, 5);
?>

<style>
    /* ═══════════════════ NAVBAR ═══════════════════ */
    .navbar {
        background: var(--white);
        border-bottom: 1px solid var(--gray-200);
        position: sticky;
        top: 0;
        z-index: 40;
        box-shadow: var(--shadow-sm);
    }
    
    .navbar-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 2rem;
        max-width: 1600px;
        margin: 0 auto;
    }
    
    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 700;
        font-size: 1.25rem;
        color: var(--primary-color);
    }
    
    .navbar-brand-icon {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-weight: 800;
        font-size: 1.1rem;
    }
    
    .navbar-menu {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .navbar-menu-item {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.95rem;
        font-weight: 500;
        color: var(--gray-700);
        transition: all 0.2s ease;
    }
    
    .navbar-menu-item:hover {
        background-color: var(--gray-100);
        color: var(--primary-color);
    }
    
    .navbar-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .user-email {
        font-size: 0.9rem;
        color: var(--gray-600);
        font-weight: 500;
    }

    /* ── Notification Bell ── */
    .notif-bell {
        position: relative;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--gray-600);
        font-size: 1.1rem;
        border: none;
    }
    .notif-bell:hover { background: var(--gray-200); color: var(--primary-color); }
    .notif-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--danger-color);
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
    }
    .notif-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: 360px;
        max-height: 400px;
        overflow-y: auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        border: 1px solid var(--gray-200);
        z-index: 100;
        display: none;
    }
    .notif-dropdown.show { display: block; }
    .notif-dropdown-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--gray-200);
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--gray-800);
    }
    .notif-dropdown-header a {
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--primary-color);
        cursor: pointer;
        background: none;
        border: none;
    }
    .notif-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--gray-100);
        transition: background 0.15s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
    }
    .notif-item:hover { background: var(--gray-50); }
    .notif-item.unread { background: #eff6ff; }
    .notif-item.unread:hover { background: #dbeafe; }
    .notif-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.8rem;
    }
    .notif-icon.fee { background: #d1fae5; color: #065f46; }
    .notif-icon.announcement { background: #dbeafe; color: #1e40af; }
    .notif-icon.general { background: #fef3c7; color: #92400e; }
    .notif-body { flex: 1; min-width: 0; }
    .notif-title { font-size: 0.85rem; font-weight: 600; color: var(--gray-900); }
    .notif-time { font-size: 0.75rem; color: var(--gray-400); margin-top: 0.15rem; }
    .notif-empty {
        padding: 2rem;
        text-align: center;
        color: var(--gray-400);
        font-size: 0.85rem;
    }

    .btn-logout {
        padding: 0.6rem 1.2rem;
        background: var(--danger-color);
        color: var(--white);
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'Segoe UI', sans-serif;
    }
    
    .btn-logout:hover {
        background: #b91c1c;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
    }
    
    .mobile-toggle {
        display: none;
        background: none;
        border: none;
        color: var(--gray-700);
        cursor: pointer;
        font-size: 1.5rem;
    }
    
    /* ═══════════════════ SIDEBAR ═══════════════════ */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        width: 280px;
        background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        color: var(--white);
        overflow-y: auto;
        padding-top: 0;
        z-index: 30;
        box-shadow: var(--shadow-lg);
    }
    
    .sidebar-header {
        padding: 1.5rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: sticky;
        top: 0;
        background: inherit;
        z-index: 10;
    }
    
    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0;
    }
    
    .sidebar-brand-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
    }
    
    .sidebar-brand-text h3 {
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
    }
    
    .sidebar-brand-text p {
        font-size: 0.75rem;
        opacity: 0.8;
        margin: 2px 0 0 0;
    }
    
    .sidebar-nav {
        padding: 1.5rem 0;
    }
    
    .sidebar-nav-section {
        margin-bottom: 1.5rem;
    }
    
    .sidebar-nav-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0 1.5rem 0.75rem 1.5rem;
        opacity: 0.7;
    }
    
    .sidebar-nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s ease;
        margin: 0 0.5rem;
        border-radius: 6px;
    }
    
    .sidebar-nav-item i {
        width: 20px;
        font-size: 0.95rem;
        text-align: center;
    }
    
    .sidebar-nav-item:hover {
        background: rgba(255, 255, 255, 0.15);
        color: var(--white);
    }
    
    .sidebar-nav-item.active {
        background: rgba(255, 255, 255, 0.25);
        color: var(--white);
        box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.2);
    }
    
    .sidebar-footer {
        margin-top: auto;
        padding: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .btn-logout-sidebar {
        width: 100%;
        padding: 0.75rem 1rem;
        background: rgba(220, 38, 38, 0.9);
        color: var(--white);
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-family: 'Segoe UI', sans-serif;
        font-size: 0.9rem;
    }
    
    .btn-logout-sidebar:hover {
        background: var(--danger-color);
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.4);
    }
    
    /* ═══════════════════ MAIN LAYOUT ═══════════════════ */
    .main-wrapper {
        margin-left: 280px;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    
    .main-content {
        flex: 1;
        padding: 2rem;
    }
    
    /* ═══════════════════ RESPONSIVE ═══════════════════ */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: 100%;
            left: -100%;
            transition: left 0.3s ease;
            z-index: 50;
        }
        
        .sidebar.active {
            left: 0;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        .main-wrapper {
            margin-left: 0;
        }
        
        .mobile-toggle {
            display: flex;
            align-items: center;
        }
        
        .navbar-menu {
            display: none;
        }
    }
</style>

<!-- Navigation Bar -->
<nav class="navbar">
    <div class="navbar-content">
        <div class="flex items-center gap-2">
            <button class="mobile-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="navbar-brand">
                <div class="navbar-brand-icon">SP</div>
                <span>SPMS</span>
            </div>
        </div>
        
        <ul class="navbar-menu">
            <?php if ($user_role == 'admin'): ?>
                <li><a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="navbar-menu-item"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/students/" class="navbar-menu-item"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/teachers/" class="navbar-menu-item"><i class="fas fa-chalkboard-user"></i> Teachers</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/classes/" class="navbar-menu-item"><i class="fas fa-school"></i> Classes</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/subjects/" class="navbar-menu-item"><i class="fas fa-book-open"></i> Subjects</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/results/" class="navbar-menu-item"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/fees/" class="navbar-menu-item"><i class="fas fa-money-bill"></i> Fees</a></li>
            <?php elseif ($user_role == 'parent'): ?>
                <li><a href="<?php echo get_role_home_url($user_role); ?>" class="navbar-menu-item"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/parents/children.php" class="navbar-menu-item"><i class="fas fa-child"></i> Children</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/parents/fees.php?student_id=" class="navbar-menu-item"><i class="fas fa-receipt"></i> Fees</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/parents/results.php?student_id=" class="navbar-menu-item"><i class="fas fa-file-pdf"></i> Results</a></li>
            <?php elseif ($user_role == 'teacher'): ?>
                <li><a href="<?php echo get_role_home_url($user_role); ?>" class="navbar-menu-item"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/results/" class="navbar-menu-item"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/teacher_assignments/" class="navbar-menu-item"><i class="fas fa-diagram-project"></i> Assignments</a></li>
            <?php elseif ($user_role == 'student'): ?>
                <li><a href="<?php echo get_role_home_url($user_role); ?>" class="navbar-menu-item"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/students/results.php" class="navbar-menu-item"><i class="fas fa-certificate"></i> Results</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/students/fees.php" class="navbar-menu-item"><i class="fas fa-receipt"></i> Fees</a></li>
            <?php endif; ?>
        </ul>
        
        <div class="navbar-actions">
            <!-- Notification Bell -->
            <div style="position:relative;">
                <button class="notif-bell" onclick="toggleNotifDropdown()" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-badge"><?php echo $unread_count > 9 ? '9+' : $unread_count; ?></span>
                    <?php endif; ?>
                </button>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-dropdown-header">
                        <span>Notifications</span>
                        <?php if ($unread_count > 0): ?>
                            <a onclick="markAllRead()">Mark all as read</a>
                        <?php endif; ?>
                    </div>
                    <?php if (count($recent_notifs) > 0): ?>
                        <?php foreach ($recent_notifs as $n): ?>
                            <a href="<?php echo htmlspecialchars($n['link'] ?? '#'); ?>" class="notif-item <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                                <div class="notif-icon <?php echo $n['type']; ?>">
                                    <i class="fas <?php echo $n['type'] === 'fee' ? 'fa-money-bill' : ($n['type'] === 'announcement' ? 'fa-bullhorn' : 'fa-circle-info'); ?>"></i>
                                </div>
                                <div class="notif-body">
                                    <div class="notif-title"><?php echo htmlspecialchars($n['title']); ?></div>
                                    <?php if ($n['message']): ?>
                                        <div style="font-size:0.8rem;color:var(--gray-500);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            <?php echo htmlspecialchars($n['message']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="notif-time"><?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-empty">
                            <i class="fas fa-bell-slash" style="font-size:1.5rem;margin-bottom:0.5rem;display:block;"></i>
                            No new notifications
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <span class="user-email">
                <i class="fas fa-user-circle"></i>
                <?php echo htmlspecialchars($_SESSION['email'] ?? 'User'); ?>
            </span>
            <form action="<?php echo BASE_URL; ?>api/logout.php" method="POST" style="margin: 0;">
                <button type="submit" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">SP</div>
            <div class="sidebar-brand-text">
                <h3>SPMS</h3>
                <p>School Portal</p>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($user_role == 'admin'): ?>
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Main</label>
                <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="sidebar-nav-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">People</label>
                <a href="<?php echo BASE_URL; ?>pages/students/" class="sidebar-nav-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Students</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/teachers/" class="sidebar-nav-item">
                    <i class="fas fa-chalkboard-user"></i>
                    <span>Teachers</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/parents/" class="sidebar-nav-item">
                    <i class="fas fa-people-roof"></i>
                    <span>Parents</span>
                </a>
            </div>
            
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Academics</label>
                <a href="<?php echo BASE_URL; ?>pages/classes/" class="sidebar-nav-item">
                    <i class="fas fa-school"></i>
                    <span>Classes</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/classes/subjects.php" class="sidebar-nav-item" style="padding-left:2.5rem;font-size:0.85rem;">
                    <i class="fas fa-book-open"></i>
                    <span>Class Subjects</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/sessions/" class="sidebar-nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Sessions</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/subjects/" class="sidebar-nav-item">
                    <i class="fas fa-book-open"></i>
                    <span>Subjects</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/grading/" class="sidebar-nav-item">
                    <i class="fas fa-layer-group"></i>
                    <span>Grading</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/results/" class="sidebar-nav-item">
                    <i class="fas fa-check-double"></i>
                    <span>Review &amp; Publish</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/results/broadsheet.php" class="sidebar-nav-item" style="padding-left:2.5rem;font-size:0.85rem;">
                    <i class="fas fa-table-cells-large"></i>
                    <span>Broadsheet</span>
                </a>
            </div>
            
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Communication</label>
                <a href="<?php echo BASE_URL; ?>pages/announcements/" class="sidebar-nav-item">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </div>

            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Finance</label>
                <a href="<?php echo BASE_URL; ?>pages/fees/" class="sidebar-nav-item">
                    <i class="fas fa-money-bill"></i>
                    <span>Fees</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/payments/" class="sidebar-nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            </div>
            
        <?php elseif ($user_role == 'teacher'): ?>
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Main</label>
                <a href="<?php echo get_role_home_url($user_role); ?>" class="sidebar-nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Results</label>
                <a href="<?php echo BASE_URL; ?>pages/results/entry.php" class="sidebar-nav-item">
                    <i class="fas fa-pen-to-square"></i>
                    <span>Enter Scores</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/results/upload.php" class="sidebar-nav-item">
                    <i class="fas fa-upload"></i>
                    <span>Upload CSV</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/results/broadsheet.php" class="sidebar-nav-item">
                    <i class="fas fa-table-cells-large"></i>
                    <span>Broadsheet</span>
                </a>
            </div>
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Settings</label>
                <a href="<?php echo BASE_URL; ?>pages/teacher_assignments/" class="sidebar-nav-item">
                    <i class="fas fa-diagram-project"></i>
                    <span>My Assignments</span>
                </a>
            </div>
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Communication</label>
                <a href="<?php echo BASE_URL; ?>pages/announcements/" class="sidebar-nav-item">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </div>
        <?php elseif ($user_role == 'parent'): ?>
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Navigation</label>
                    <a href="<?php echo get_role_home_url($user_role); ?>" class="sidebar-nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/parents/children.php" class="sidebar-nav-item">
                    <i class="fas fa-child"></i>
                    <span>My Children</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/parents/fees.php?student_id=" class="sidebar-nav-item">
                    <i class="fas fa-receipt"></i>
                    <span>Fees</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/parents/results.php?student_id=" class="sidebar-nav-item">
                    <i class="fas fa-file-pdf"></i>
                    <span>Results</span>
                </a>
            </div>
            
        <?php elseif ($user_role == 'student'): ?>
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Navigation</label>
                    <a href="<?php echo get_role_home_url($user_role); ?>" class="sidebar-nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/students/results.php" class="sidebar-nav-item">
                    <i class="fas fa-certificate"></i>
                    <span>My Results</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/students/fees.php" class="sidebar-nav-item">
                    <i class="fas fa-receipt"></i>
                    <span>My Fees</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <form action="<?php echo BASE_URL; ?>api/logout.php" method="POST">
            <button type="submit" class="btn-logout-sidebar">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </button>
        </form>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
    
    sidebarToggle?.addEventListener('click', toggleSidebar);
    overlay?.addEventListener('click', toggleSidebar);
    
    // Close sidebar on link click
    document.querySelectorAll('.sidebar-nav-item').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                toggleSidebar();
            }
        });
    });

    // Notification dropdown toggle
    function toggleNotifDropdown() {
        const dd = document.getElementById('notifDropdown');
        dd.classList.toggle('show');
    }
    document.addEventListener('click', function(e) {
        const dd = document.getElementById('notifDropdown');
        const bell = document.querySelector('.notif-bell');
        if (!bell?.contains(e.target) && !dd?.contains(e.target)) {
            dd?.classList.remove('show');
        }
    });
    function markAllRead() {
        fetch('<?php echo BASE_URL; ?>api/mark_notifications_read.php')
            .then(r => r.json())
            .then(d => { if (d.success) location.reload(); });
    }
</script>