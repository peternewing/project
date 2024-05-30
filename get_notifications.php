<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];
$notifications = get_notifications($mysqli, $user_id);

$notifications_list = [];
if ($notifications && $notifications->num_rows > 0) {
    while ($notification = $notifications->fetch_assoc()) {
        $notifications_list[] = [
            'content' => $notification['content'],
            'type' => $notification['type'],
            'username' => $notification['username']
        ];
    }
}

echo json_encode($notifications_list);
?>
