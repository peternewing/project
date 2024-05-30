<?php
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $notification_id = $_POST['notification_id'];

    if (mark_notification_as_read($mysqli, $notification_id)) {
        echo "Notification marked as read";
    } else {
        http_response_code(500);
        echo "Failed to mark notification as read";
    }
} else {
    http_response_code(400);
    echo "Invalid request";
}
?>
