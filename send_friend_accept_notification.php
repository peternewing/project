<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $friend_username = $_POST['friend_username'];
    
    $friend = get_user_by_username($mysqli, $friend_username);
    if ($friend) {
        $friend_id = $friend['id'];
        $username = get_user_by_id($mysqli, $user_id)['username'];
        $content = "$username accepted your friend request";
        
        $sql = "INSERT INTO notifications (user_id, type, content, sender_id) VALUES (?, 'friend_accept', ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
            http_response_code(500);
            echo "Failed to send notification.";
        } else {
            $stmt->bind_param("isi", $friend_id, $content, $user_id);
            if ($stmt->execute()) {
                echo "Notification sent!";
            } else {
                http_response_code(500);
                echo "Failed to send notification.";
            }
        }
    } else {
        http_response_code(400);
        echo "Invalid request.";
    }
} else {
    http_response_code(400);
    echo "Invalid request.";
}
?>
