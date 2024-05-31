<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$friends = get_friends($mysqli, $user_id);

header('Content-Type: application/json');
echo json_encode($friends->fetch_all(MYSQLI_ASSOC));
?>
