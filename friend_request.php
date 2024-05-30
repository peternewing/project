<?php
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $friend_username = $_POST['friend_username'];

    if (send_friend_request($mysqli, $user_id, $friend_username)) {
        echo "Friend request sent!";
    } else {
        http_response_code(500);
        echo "Failed to send friend request.";
    }
} else {
    http_response_code(400);
    echo "Invalid request.";
}
?>
