<?php
include_once '../includes/auth_check.php';

if ($user_role !== ROLE_ADMIN) {
    header('Location: ' . get_role_home_url($user_role));
    exit;
}

include_once '../includes/header.php';
include_once '../includes/navbar.php';


// Assuming this is included in your actual dashboard.php
// This is the HTML structure/styling portion
?>

<style>
    /* Admin dashboard uses shared styles from assets/css/app.css */
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
                <a href="results/entry.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    New Entry
                </a>
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
                        <a href="students/add.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-user-plus"></i>
                            Add Student
                        </a>
                        <a href="teachers/create.php" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-user-tie"></i>
                            Add Teacher
                        </a>
                        <a href="results/upload.php" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-file-upload"></i>
                            Upload Results
                        </a>
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