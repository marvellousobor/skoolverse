<?php include_once '../../includes/auth_check.php'; ?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

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

    /* Coming soon card */
    .coming-soon-card {
        background: var(--white);
        border-radius: 16px;
        border: 1px solid var(--gray-200);
        box-shadow: var(--shadow-sm);
        padding: 4rem 2rem;
        text-align: center;
        max-width: 520px;
        margin: 0 auto;
    }

    .coming-soon-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem auto;
        box-shadow: 0 4px 16px rgba(30, 64, 175, 0.25);
    }

    .coming-soon-icon i {
        font-size: 2rem;
        color: var(--white);
    }

    .coming-soon-card h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 0.75rem 0;
    }

    .coming-soon-card p {
        font-size: 0.9rem;
        color: var(--gray-500);
        line-height: 1.6;
        margin: 0 0 2rem 0;
    }

    .feature-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        text-align: left;
        margin-bottom: 2rem;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: var(--gray-50);
        border-radius: 8px;
        font-size: 0.875rem;
        color: var(--gray-700);
        font-weight: 500;
    }

    .feature-item i {
        color: var(--primary-color);
        width: 16px;
        text-align: center;
    }

    .badge-coming {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.85rem;
        background: #fef3c7;
        color: #92400e;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    @media (max-width: 640px) {
        .main-content { padding: 1rem; }
        .coming-soon-card { padding: 2.5rem 1.25rem; }
    }
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