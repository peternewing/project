<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$notifications = get_notifications($mysqli, $user_id);

header('Content-Type: application/json');
echo json_encode($notifications->fetch_all(MYSQLI_ASSOC));
?>
