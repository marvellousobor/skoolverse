<?php
/**
 * Notification helper functions.
 */

/**
 * Send a notification to a single user.
 */
function send_notification($conn, $type, $title, $message = null, $link = null, $user_id = null, $target_role = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, target_role, type, title, message, link, is_read) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("isssss", $user_id, $target_role, $type, $title, $message, $link);
    return $stmt->execute();
}

/**
 * Send a notification to all users with a given role.
 */
function send_notification_to_role($conn, $type, $title, $message = null, $link = null, $role = 'student') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, target_role, type, title, message, link, is_read) VALUES (NULL, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("sssss", $role, $type, $title, $message, $link);
    return $stmt->execute();
}

/**
 * Send a notification to all users in the system.
 */
function send_notification_to_all($conn, $type, $title, $message = null, $link = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, target_role, type, title, message, link, is_read) VALUES (NULL, NULL, ?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $type, $title, $message, $link);
    return $stmt->execute();
}

/**
 * Send a notification to everyone in a specific class (students + teachers + parents).
 */
function send_notification_to_class($conn, $type, $title, $message = null, $link = null, $class_id = null) {
    // One notification record with the class_id target
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, target_role, target_class_id, type, title, message, link, is_read) VALUES (NULL, 'student', ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("issss", $class_id, $type, $title, $message, $link);
    return $stmt->execute();
}

/**
 * Get unread notification count for a user.
 * For students, also match class-targeted notifications.
 */
function get_unread_notification_count($conn, $user_id, $role) {
    $student_class_id = null;
    if ($role === 'student') {
        $r = $conn->query("SELECT class_id FROM students WHERE user_id = $user_id")->fetch_assoc();
        $student_class_id = $r ? (int)$r['class_id'] : null;
    }

    $sql = "SELECT COUNT(*) as cnt FROM notifications WHERE is_read = 0 AND (user_id = ? OR (user_id IS NULL AND (target_role = ? OR target_role IS NULL)))";
    $params = [$user_id, $role];
    $types = "is";

    if ($student_class_id) {
        $sql .= " OR (user_id IS NULL AND target_role = 'student' AND target_class_id = ?)";
        $params[] = $student_class_id;
        $types .= "i";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['cnt'];
}

/**
 * Get recent notifications for a user.
 */
function get_recent_notifications($conn, $user_id, $role, $limit = 10) {
    $student_class_id = null;
    if ($role === 'student') {
        $r = $conn->query("SELECT class_id FROM students WHERE user_id = $user_id")->fetch_assoc();
        $student_class_id = $r ? (int)$r['class_id'] : null;
    }

    $sql = "SELECT id, type, title, message, link, is_read, created_at FROM notifications WHERE is_read = 0 AND (user_id = ? OR (user_id IS NULL AND (target_role = ? OR target_role IS NULL)))";
    $params = [$user_id, $role];
    $types = "is";

    if ($student_class_id) {
        $sql .= " OR (user_id IS NULL AND target_role = 'student' AND target_class_id = ?)";
        $params[] = $student_class_id;
        $types .= "i";
    }

    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Mark a single notification as read.
 */
function mark_notification_read($conn, $notification_id, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
    $stmt->bind_param("ii", $notification_id, $user_id);
    return $stmt->execute();
}

/**
 * Mark all notifications as read for a user.
 */
function mark_all_notifications_read($conn, $user_id, $role) {
    $student_class_id = null;
    if ($role === 'student') {
        $r = $conn->query("SELECT class_id FROM students WHERE user_id = $user_id")->fetch_assoc();
        $student_class_id = $r ? (int)$r['class_id'] : null;
    }

    $sql = "UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND (user_id = ? OR (user_id IS NULL AND (target_role = ? OR target_role IS NULL)))";
    $params = [$user_id, $role];
    $types = "is";

    if ($student_class_id) {
        $sql .= " OR (user_id IS NULL AND target_role = 'student' AND target_class_id = ?)";
        $params[] = $student_class_id;
        $types .= "i";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    return $stmt->execute();
}
