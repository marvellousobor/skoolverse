<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_name = htmlspecialchars($_POST['class_name']);
    $level = htmlspecialchars($_POST['level']);
    
    // Validate inputs
    if (empty($class_name)) {
        $error = "Class name is required";
    } elseif (empty($level)) {
        $error = "Level is required";
    } else {
        // Check if class name already exists
        $check = $conn->query("SELECT id FROM classes WHERE class_name = '$class_name'");
        if ($check->num_rows > 0) {
            $error = "Class name already exists";
        } else {
            // Insert class record
            $sql = "INSERT INTO classes (class_name, level)
                    VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $class_name, $level);
            
            if ($stmt->execute()) {
                $success = "Class created successfully!";
                // Clear form
                $class_name = '';
                $level = '';
            } else {
                $error = "Error creating class: " . $conn->error;
            }
        }
    }
}
?>

<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<style>
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

    .page-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .form-layout {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 1.5rem;
        align-items: start;
    }

    .card {
        background: var(--white);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }

    .card-header h2 {
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .card-body { padding: 1.5rem; }

    .alert {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }

    .alert-success {
        background: #d1fae5;
        color: var(--success-color);
        border: 1px solid #6ee7b7;
    }

    .alert-danger {
        background: #fee2e2;
        color: var(--danger-color);
        border: 1px solid #fca5a5;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-700);
    }

    .form-label .required {
        color: var(--danger-color);
        margin-left: 0.2rem;
    }

    .form-control {
        width: 100%;
        padding: 0.65rem 0.9rem;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 0.9rem;
        font-family: 'Segoe UI', sans-serif;
        color: var(--gray-900);
        background: var(--white);
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.08);
    }

    .form-hint {
        font-size: 0.78rem;
        color: var(--gray-500);
        margin-top: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .info-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        padding: 1rem 1.1rem;
        font-size: 0.85rem;
        color: var(--primary-dark);
        display: flex;
        gap: 0.6rem;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .info-box i { margin-top: 0.1rem; flex-shrink: 0; }

    .form-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--gray-200);
    }

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

    .btn-primary { background: var(--primary-color); color: var(--white); }
    .btn-primary:hover { background: var(--primary-dark); box-shadow: 0 2px 8px rgba(30, 64, 175, 0.3); }
    .btn-secondary { background: var(--gray-200); color: var(--gray-800); }
    .btn-secondary:hover { background: var(--gray-300); }

    @media (max-width: 900px) {
        .form-layout { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .main-content { padding: 1rem; }
        .page-header { flex-direction: column; align-items: flex-start; }
        .form-actions { flex-direction: column; align-items: stretch; }
        .btn { justify-content: center; }
    }
</style>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-school"></i> Create Class</h1>
                <div class="breadcrumb">
                    <a href="index.php" style="color:var(--primary-color);text-decoration:none;">Classes</a>
                    &rsaquo; New Class
                </div>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Classes
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-circle-check"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-layout">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-pen-to-square"></i> Class Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Class Name <span class="required">*</span></label>
                            <input type="text" name="class_name" required placeholder="e.g., JSS 1A, SS 2B" value="<?php echo isset($class_name) ? htmlspecialchars($class_name) : ''; ?>" class="form-control">
                        </div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Level <span class="required">*</span></label>
                            <input type="text" name="level" required placeholder="e.g., Junior Secondary, Senior Secondary" value="<?php echo isset($level) ? htmlspecialchars($level) : ''; ?>" class="form-control">
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card" style="position: sticky; top: 1.5rem;">
                        <div class="card-header">
                            <h2><i class="fas fa-clipboard-check"></i> Summary</h2>
                        </div>
                        <div class="card-body">
                            <div class="info-box">
                                <i class="fas fa-circle-info"></i>
                                <span>Use a short, recognizable class name and keep the level consistent with the rest of the school structure.</span>
                            </div>

                            <div class="form-actions" style="margin-top:0;padding-top:0;border-top:none;flex-direction:column;gap:0.6rem;">
                                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                                    <i class="fas fa-school"></i> Create Class
                                </button>
                                <a href="index.php" class="btn btn-secondary" style="width:100%;justify-content:center;">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>