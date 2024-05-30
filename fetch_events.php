<?php
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];
$events = fetch_events($mysqli, $user_id);

$events_list = [];
if ($events && $events->num_rows > 0) {
    while ($event = $events->fetch_assoc()) {
        $events_list[] = $event;
    }
}

echo json_encode($events_list);
?>
