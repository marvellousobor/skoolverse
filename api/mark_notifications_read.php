<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/notifications_helper.php';

$success = mark_all_notifications_read($conn, $user_id, $user_role);
echo json_encode(['success' => $success]);
