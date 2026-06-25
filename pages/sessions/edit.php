<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$session_id = (int)$_GET['id'];
$result = $conn->query("SELECT * FROM sessions WHERE id = $session_id");
if ($result->num_rows == 0) {
    header('Location: index.php');
    exit;
}

$session = $result->fetch_assoc();
$session_name = $session['session_name'];
$start_date = $session['start_date'];
$end_date = $session['end_date'];
$is_active = $session['is_active'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $session_name = htmlspecialchars($_POST['session_name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($session_name)) {
        $error = 'Session name is required';
    } elseif (empty($start_date) || empty($end_date)) {
        $error = 'Both start and end dates are required';
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $error = 'Start date must be before end date';
    } else {
        $check = $conn->prepare('SELECT id FROM sessions WHERE session_name = ? AND id != ?');
        $check->bind_param('si', $session_name, $session_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Session name already exists';
        } else {
            $sql = 'UPDATE sessions SET session_name = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssii', $session_name, $start_date, $end_date, $is_active, $session_id);

            if ($stmt->execute()) {
                $success = 'Academic session updated successfully!';
            } else {
                $error = 'Error updating session: ' . $conn->error;
            }
        }
    }
}
?>
<?php include_once '../../includes/header.php'; ?>
<?php include_once '../../includes/navbar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-pen-to-square"></i> Edit Session</h1>
                <div class="breadcrumb">Adjust the details for this academic session.</div>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Sessions</a>
            </div>
        </div>

        <div class="card" style="max-width:760px;">
            <div class="card-header">
                <h2><i class="fas fa-pen-to-square"></i> Session Details</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="card" style="margin-bottom:1rem;border-left:4px solid #dc2626;">
                        <div class="card-body" style="padding:1rem 1.25rem;color:#b91c1c;">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="card" style="margin-bottom:1rem;border-left:4px solid #059669;">
                        <div class="card-body" style="padding:1rem 1.25rem;color:#047857;">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" style="display:grid;gap:1.1rem;">
                    <div class="form-group">
                        <label>Session Name *</label>
                        <input type="text" name="session_name" required placeholder="e.g., 2024/2025" value="<?php echo htmlspecialchars($session_name); ?>" class="form-control" style="min-width:100%;">
                        <small style="color:var(--gray-500);">Format: YYYY/YYYY, for example 2024/2025</small>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                        <div class="form-group">
                            <label>Start Date *</label>
                            <input type="date" name="start_date" required value="<?php echo htmlspecialchars($start_date); ?>" class="form-control" style="min-width:100%;">
                        </div>
                        <div class="form-group">
                            <label>End Date *</label>
                            <input type="date" name="end_date" required value="<?php echo htmlspecialchars($end_date); ?>" class="form-control" style="min-width:100%;">
                        </div>
                    </div>

                    <label style="display:flex;align-items:center;gap:0.75rem;">
                        <input type="checkbox" name="is_active" <?php echo $is_active ? 'checked' : ''; ?> style="width:1rem;height:1rem;">
                        <span style="font-weight:600;color:var(--gray-800);">Set as Active Session</span>
                    </label>
                    <small style="color:var(--gray-500);">Only one session can be active at a time.</small>

                    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Session</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-xmark"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include_once '../../includes/footer.php'; ?>