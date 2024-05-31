<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'], $_POST['friend_username']) && isset($_SESSION['user_id'])) {
    $event_id = intval($_POST['event_id']);
    $friend_username = $_POST['friend_username'];
    $user_id = $_SESSION['user_id'];

    if (invite_friend_to_event($mysqli, $event_id, $friend_username)) {
        $response['success'] = true;
        $response['message'] = 'Invitation sent.';
    } else {
        $response['message'] = 'Failed to send invitation.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>
