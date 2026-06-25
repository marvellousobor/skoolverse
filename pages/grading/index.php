<?php
include_once '../../includes/auth_check.php';
if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}
include_once '../../includes/header.php';
include_once '../../includes/navbar.php';
include_once '../../includes/results_schema.php';
ensure_results_schema($conn);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_grade'])) {
        $grade = strtoupper(trim($_POST['grade']));
        $lower = (float)$_POST['lower_bound'];
        $upper = (float)$_POST['upper_bound'];
        $remark = trim($_POST['remark']);
        if (empty($grade) || empty($remark)) {
            $error = "Grade and Remark are required.";
        } elseif ($lower >= $upper) {
            $error = "Lower bound must be less than upper bound.";
        } else {
            $stmt = $conn->prepare("INSERT INTO grading_scales (grade, lower_bound, upper_bound, remark) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sddd", $grade, $lower, $upper, $remark);
            if ($stmt->execute()) {
                $success = "Grade added successfully!";
            } else {
                $error = "Error adding grade: " . $conn->error;
            }
        }
    }
    if (isset($_POST['edit_grade'])) {
        $id = (int)$_POST['id'];
        $grade = strtoupper(trim($_POST['grade']));
        $lower = (float)$_POST['lower_bound'];
        $upper = (float)$_POST['upper_bound'];
        $remark = trim($_POST['remark']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE grading_scales SET grade=?, lower_bound=?, upper_bound=?, remark=?, is_active=? WHERE id=?");
        $stmt->bind_param("sddsii", $grade, $lower, $upper, $remark, $is_active, $id);
        if ($stmt->execute()) {
            $success = "Grade updated successfully!";
        } else {
            $error = "Error updating grade: " . $conn->error;
        }
    }
    if (isset($_POST['delete_grade'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM grading_scales WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Grade deleted successfully!";
        } else {
            $error = "Error deleting grade: " . $conn->error;
        }
    }
}

$grades = $conn->query("SELECT * FROM grading_scales ORDER BY lower_bound DESC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-layer-group"></i> Grading Scale</h1>
                <span class="breadcrumb">Configure the WAEC grading system</span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Add Grade</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Grade</label>
                                <input type="text" name="grade" required placeholder="e.g., A1, B2, C6" class="form-control" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label>Lower Bound</label>
                                <input type="number" name="lower_bound" required step="0.01" min="0" max="100" placeholder="75" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Upper Bound</label>
                                <input type="number" name="upper_bound" required step="0.01" min="0" max="100" placeholder="100" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Remark</label>
                                <input type="text" name="remark" required placeholder="e.g., Excellent, Credit" class="form-control">
                            </div>
                            <div class="form-actions" style="padding-top:1.5rem;">
                                <button type="submit" name="add_grade" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-info-circle"></i> Grading Reference</h2>
                </div>
                <div class="card-body">
                    <div style="font-size:0.85rem;color:var(--gray-600);line-height:1.7;">
                        <p style="margin-bottom:0.75rem;">This grading scale follows the WAEC standard for secondary schools:</p>
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:2px solid var(--gray-200);">
                                    <th style="padding:0.35rem 0;text-align:left;font-size:0.75rem;">Grade</th>
                                    <th style="padding:0.35rem 0;text-align:left;font-size:0.75rem;">Range</th>
                                    <th style="padding:0.35rem 0;text-align:left;font-size:0.75rem;">Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $g): ?>
                                <tr style="border-bottom:1px solid var(--gray-100);">
                                    <td style="padding:0.3rem 0;"><strong><?php echo htmlspecialchars($g['grade']); ?></strong></td>
                                    <td style="padding:0.3rem 0;"><?php echo (int)$g['lower_bound']; ?> - <?php echo (int)$g['upper_bound']; ?></td>
                                    <td style="padding:0.3rem 0;"><?php echo htmlspecialchars($g['remark']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Grades</h2>
            </div>
            <?php if (empty($grades)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-layer-group"></i></div>
                    <p>No grades configured yet.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Grade</th>
                                <th>Lower Bound</th>
                                <th>Upper Bound</th>
                                <th>Remark</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $g): ?>
                            <tr>
                                <td style="font-weight:700;font-size:1.1rem;"><?php echo htmlspecialchars($g['grade']); ?></td>
                                <td><?php echo htmlspecialchars($g['lower_bound']); ?></td>
                                <td><?php echo htmlspecialchars($g['upper_bound']); ?></td>
                                <td><?php echo htmlspecialchars($g['remark']); ?></td>
                                <td>
                                    <span class="badge <?php echo $g['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $g['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                                            <input type="hidden" name="grade" value="<?php echo htmlspecialchars($g['grade']); ?>">
                                            <input type="hidden" name="lower_bound" value="<?php echo $g['lower_bound']; ?>">
                                            <input type="hidden" name="upper_bound" value="<?php echo $g['upper_bound']; ?>">
                                            <input type="hidden" name="remark" value="<?php echo htmlspecialchars($g['remark']); ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $g['is_active'] ? '0' : '1'; ?>">
                                            <input type="hidden" name="edit_grade" value="1">
                                            <button type="submit" class="btn btn-ghost btn-xs">
                                                <i class="fas fa-<?php echo $g['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                <?php echo $g['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this grade?');">
                                            <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                                            <input type="hidden" name="delete_grade" value="1">
                                            <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>
