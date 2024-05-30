<?php
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];
    $friend_username = $_POST['friend_username'];

    if (invite_friend_to_event($mysqli, $event_id, $friend_username)) {
        echo "Invitation sent!";
    } else {
        http_response_code(500);
        echo "Failed to send invitation.";
    }
} else {
    http_response_code(400);
    echo "Invalid request.";
}
?>
