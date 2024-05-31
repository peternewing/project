<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['friend_username']) && isset($_SESSION['user_id'])) {
    $friend_username = $_POST['friend_username'];
    $user_id = $_SESSION['user_id'];

    if (send_friend_request($mysqli, $user_id, $friend_username)) {
        $response['success'] = true;
        $response['message'] = 'Friend request sent.';
    } else {
        $response['message'] = 'Failed to send friend request.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>
