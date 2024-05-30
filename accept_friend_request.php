<?php
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $friend_username = $_POST['friend_username'];

    if (accept_friend_request($mysqli, $user_id, $friend_username)) {
        echo json_encode(['success' => true, 'friend_username' => $friend_username]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to accept friend request.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
