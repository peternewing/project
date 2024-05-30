<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_moderator']) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = $_POST['event_id'];
    if (verify_event($event_id)) {
        header("Location: index.php");
    } else {
        $error = "Failed to verify event.";
    }
}
?>
