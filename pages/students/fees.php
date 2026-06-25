<?php include_once '../../includes/auth_check.php'; ?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<style>
    /* Coming soon is handled by shared styles in app.css */
</style>

<div class="main-wrapper">
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-receipt"></i> My Fees</h1>
                <div class="breadcrumb">Track your fee payments and balance</div>
            </div>
            <span class="badge-coming">
                <i class="fas fa-clock"></i> Coming Soon
            </span>
        </div>

        <!-- Coming Soon Card -->
        <div class="coming-soon-card">
            <div class="coming-soon-icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h2>Fee Management</h2>
            <p>
                The fees section is currently being built. Soon you will be able to view your
                outstanding balance, payment history, and download receipts — all from right here.
            </p>

            <div class="feature-list">
                <div class="feature-item">
                    <i class="fas fa-circle-check"></i>
                    View outstanding fee balance
                </div>
                <div class="feature-item">
                    <i class="fas fa-circle-check"></i>
                    Track payment history by term
                </div>
                <div class="feature-item">
                    <i class="fas fa-circle-check"></i>
                    Download payment receipts as PDF
                </div>
                <div class="feature-item">
                    <i class="fas fa-circle-check"></i>
                    Receive payment reminders
                </div>
            </div>

            <p style="font-size:0.8rem;color:var(--gray-400);margin:0;">
                Contact your school administrator for fee enquiries in the meantime.
            </p>
        </div>

    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>