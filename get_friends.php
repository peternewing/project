<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];
$friends = get_friends($user_id);

$friends_list = [];
if ($friends && $friends->num_rows > 0) {
    while ($friend = $friends->fetch_assoc()) {
        $friends_list[] = ['username' => $friend['username']];
    }
}

echo json_encode($friends_list);
?>
