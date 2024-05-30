<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];
$notifications = get_notifications($mysqli, $user_id);

$notifications_list = [];
if ($notifications && $notifications->num_rows > 0) {
    while ($notification = $notifications->fetch_assoc()) {
        $notification_data = [
            'content' => $notification['content'],
            'type' => $notification['type'],
            'username' => $notification['username']
        ];
        if ($notification['type'] === 'event_invite') {
            $notification_data['event_id'] = $notification['event_id'];
        }
        $notifications_list[] = $notification_data;
    }
}

echo json_encode($notifications_list);
?>
