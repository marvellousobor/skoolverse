<?php
if (!isset($user_role)) {
    include_once __DIR__ . '/../includes/auth_check.php';
}
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
                <li><a href="<?php echo BASE_URL; ?>pages/fees/" class="navbar-menu-item"><i class="fas fa-money-bill"></i> Fees</a></li>
            <?php endif; ?>
        </ul>
        
        <div class="navbar-actions">
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
                <a href="<?php echo BASE_URL; ?>pages/sessions/" class="sidebar-nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Sessions</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/results/" class="sidebar-nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Results</span>
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
            
        <?php elseif ($user_role == 'parent'): ?>
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Navigation</label>
                <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="sidebar-nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/parents/children.php" class="sidebar-nav-item">
                    <i class="fas fa-child"></i>
                    <span>My Children</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/parents/fees.php" class="sidebar-nav-item">
                    <i class="fas fa-receipt"></i>
                    <span>Fees</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/parents/results.php" class="sidebar-nav-item">
                    <i class="fas fa-file-pdf"></i>
                    <span>Results</span>
                </a>
            </div>
            
        <?php elseif ($user_role == 'student'): ?>
            <div class="sidebar-nav-section">
                <label class="sidebar-nav-label">Navigation</label>
                <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="sidebar-nav-item active">
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
</script>