<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && isset($_SESSION['user_id'])) {
    $event_id = intval($_POST['event_id']);
    $user_id = intval($_SESSION['user_id']);

    if (accept_event_invite($mysqli, $user_id, $event_id)) {
        $event = get_event_by_id($mysqli, $event_id);
        $response['success'] = true;
        $response['event'] = $event;
    } else {
        $response['message'] = 'Failed to accept event invitation.';
        error_log("Failed to accept event invitation: User ID - $user_id, Event ID - $event_id");
    }
} else {
    $response['message'] = 'Invalid request.';
    error_log("Invalid request: " . print_r($_POST, true));
}

echo json_encode($response);
?>
