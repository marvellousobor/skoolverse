<?php
include_once '../includes/auth_check.php';
include_once '../includes/header.php';
include_once '../includes/navbar.php';


// Assuming this is included in your actual dashboard.php
// This is the HTML structure/styling portion
?>

<style>
    /* ═══════════════════ DASHBOARD STYLES ═══════════════════ */
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
    
    /* ═══════════════════ STAT CARDS ═══════════════════ */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: var(--white);
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid var(--gray-200);
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    
    .stat-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
    
    .stat-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .stat-card-icon.primary {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: var(--white);
    }
    
    .stat-card-icon.success {
        background: linear-gradient(135deg, #10b981, var(--success-color));
        color: var(--white);
    }
    
    .stat-card-icon.warning {
        background: linear-gradient(135deg, #f59e0b, var(--warning-color));
        color: var(--white);
    }
    
    .stat-card-icon.danger {
        background: linear-gradient(135deg, #ef4444, var(--danger-color));
        color: var(--white);
    }
    
    .stat-card-label {
        font-size: 0.875rem;
        color: var(--gray-600);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    
    .stat-card-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0.5rem 0 0 0;
    }
    
    .stat-card-change {
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 0.75rem;
    }
    
    .stat-card-change.positive {
        color: var(--success-color);
    }
    
    .stat-card-change.negative {
        color: var(--danger-color);
    }
    
    /* ═══════════════════ SECTION CARDS ═══════════════════ */
    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .card {
        background: var(--white);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: var(--shadow-md);
    }
    
    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }
    
    .card-header h2 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--gray-900);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .card-body:empty::after {
        content: "No data available";
        color: var(--gray-400);
        font-size: 0.9rem;
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
    
    .btn-outline {
        border: 2px solid var(--primary-color);
        background: transparent;
        color: var(--primary-color);
    }
    
    .btn-outline:hover {
        background: var(--primary-color);
        color: var(--white);
    }
    
    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }
    
    /* ═══════════════════ TABLES ═══════════════════ */
    .table-wrapper {
        overflow-x: auto;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    
    .table thead {
        background: var(--gray-50);
    }
    
    .table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: var(--gray-700);
        border-bottom: 2px solid var(--gray-200);
    }
    
    .table td {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .table tbody tr:hover {
        background: var(--gray-50);
    }
    
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* ═══════════════════ BADGES ═══════════════════ */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-primary {
        background: var(--primary-light);
        color: var(--white);
    }
    
    .badge-success {
        background: #d1fae5;
        color: var(--success-color);
    }
    
    .badge-warning {
        background: #fef3c7;
        color: #b45309;
    }
    
    .badge-danger {
        background: #fee2e2;
        color: var(--danger-color);
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
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .content-grid {
            grid-template-columns: 1fr;
        }
        
        .page-title h1 {
            font-size: 1.5rem;
        }
    }
</style>

<!-- Main Content Wrapper -->
<div class="main-wrapper">
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                <span class="breadcrumb">Welcome back to SPMS</span>
            </div>
            <div class="page-actions">
                <button class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    New Entry
                </button>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Total Students -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Total Students</div>
                        <div class="stat-card-value">5</div>
                        <div class="stat-card-change positive">
                            <i class="fas fa-arrow-up"></i> +2 this month
                        </div>
                    </div>
                    <div class="stat-card-icon primary">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Teachers -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Total Teachers</div>
                        <div class="stat-card-value">3</div>
                        <div class="stat-card-change positive">
                            <i class="fas fa-arrow-up"></i> All active
                        </div>
                    </div>
                    <div class="stat-card-icon success">
                        <i class="fas fa-chalkboard-user"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Parents -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Total Parents</div>
                        <div class="stat-card-value">3</div>
                        <div class="stat-card-change positive">
                            <i class="fas fa-arrow-up"></i> Registered
                        </div>
                    </div>
                    <div class="stat-card-icon warning">
                        <i class="fas fa-people-roof"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Revenue -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Total Revenue</div>
                        <div class="stat-card-value">₦10,000,000</div>
                        <div class="stat-card-change positive">
                            <i class="fas fa-arrow-up"></i> +15% YoY
                        </div>
                    </div>
                    <div class="stat-card-icon primary">
                        <i class="fas fa-money-bill"></i>
                    </div>
                </div>
            </div>
            
            <!-- Outstanding Fees -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Outstanding Fees</div>
                        <div class="stat-card-value">₦30,000,000</div>
                        <div class="stat-card-change negative">
                            <i class="fas fa-arrow-down"></i> Pending
                        </div>
                    </div>
                    <div class="stat-card-icon danger">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Quick Actions Card -->
            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-lightning-bolt"></i>
                        Quick Actions
                    </h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: 0.75rem;">
                        <button class="btn btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-user-plus"></i>
                            Add Student
                        </button>
                        <button class="btn btn-secondary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-user-tie"></i>
                            Add Teacher
                        </button>
                        <button class="btn btn-secondary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-file-upload"></i>
                            Upload Results
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Card -->
            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-history"></i>
                        Recent Activity
                    </h2>
                </div>
                <div class="card-body">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; gap: 0.75rem; padding-bottom: 1rem; border-bottom: 1px solid var(--gray-200);">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="margin: 0; font-weight: 600; color: var(--gray-900);">New Student Added</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: var(--gray-500);">2 hours ago</p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.75rem; padding-bottom: 1rem; border-bottom: 1px solid var(--gray-200);">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: #10b981; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i class="fas fa-money-bill"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="margin: 0; font-weight: 600; color: var(--gray-900);">Payment Received</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: var(--gray-500);">5 hours ago</p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.75rem;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: #f59e0b; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i class="fas fa-file-upload"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="margin: 0; font-weight: 600; color: var(--gray-900);">Results Uploaded</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: var(--gray-500);">1 day ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Include Footer -->
<?php include_once '../includes/footer.php'; ?>