<?php
include_once '../../includes/auth_check.php';
header('Content-Type: application/json');

if ($user_role != ROLE_PARENT) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['reference']) || empty($input['student_id']) || empty($input['fee_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$reference = $conn->real_escape_string($input['reference']);
$student_id = (int)$input['student_id'];
$fee_ids = array_map('intval', (array)$input['fee_ids']);

if (PAYSTACK_SECRET_KEY === '') {
    echo json_encode(['success' => false, 'message' => 'Paystack secret key not configured.']);
    exit();
}

// Verify with Paystack
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . urlencode($reference));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . PAYSTACK_SECRET_KEY]);
$res = curl_exec($ch);
curl_close($ch);

if (!$res) {
    echo json_encode(['success' => false, 'message' => 'No response from Paystack']);
    exit();
}

$data = json_decode($res, true);
if (empty($data) || !$data['status']) {
    echo json_encode(['success' => false, 'message' => 'Verification failed']);
    exit();
}

$result = $data['data'];
if ($result['status'] !== 'success') {
    echo json_encode(['success' => false, 'message' => 'Transaction not successful']);
    exit();
}

$amount_paid = $result['amount'] / 100.0;

$conn->begin_transaction();
try {
    foreach ($fee_ids as $fid) {
        // Get fee amount
        $stmt = $conn->prepare("SELECT amount, is_paid FROM student_fees WHERE id = ? AND student_id = ? LIMIT 1");
        $stmt->bind_param('ii', $fid, $student_id);
        $stmt->execute();
        $fee = $stmt->get_result()->fetch_assoc();
        if (!$fee) continue;

        // Insert payment record (matches the actual `payments` table schema:
        // id, student_id, bursar_id, amount_paid, payment_method, payment_reference, payment_date, receipt_number, notes, created_at)
        $stmt = $conn->prepare("INSERT INTO payments (student_id, amount_paid, payment_method, payment_reference) VALUES (?, ?, 'Online', ?)");
        $stmt->bind_param('ids', $student_id, $fee['amount'], $reference);
        $stmt->execute();

        // Mark student_fees as paid
        $stmt = $conn->prepare("UPDATE student_fees SET is_paid = 1 WHERE id = ?");
        $stmt->bind_param('i', $fid);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>