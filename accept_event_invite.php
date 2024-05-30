<?php
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];

    if (accept_event_invite($mysqli, $user_id, $event_id)) {
        // Fetch event details
        $event_details = get_event_by_id($mysqli, $event_id);

        echo json_encode(['success' => true, 'event' => $event_details]);
    } else {
        error_log("Failed to accept event invite for user_id: $user_id, event_id: $event_id");
        echo json_encode(['success' => false, 'message' => 'Failed to accept event invitation.']);
    }
} else {
    error_log("Invalid request or user not logged in.");
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
