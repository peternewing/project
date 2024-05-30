<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notifications = get_notifications($mysqli, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <nav>
        <a href="index.php">Home</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="notifications-page">
        <h2>Notifications</h2>
        <?php if ($notifications && $notifications->num_rows > 0): ?>
            <?php while ($notification = $notifications->fetch_assoc()): ?>
                <div class="notification-item">
                    <p><?php echo htmlspecialchars($notification['content']); ?></p>
                    <?php if ($notification['type'] == 'friend_request' && !$notification['is_read']): ?>
                        <button class="accept-friend-request" data-friend-username="<?php echo htmlspecialchars($notification['username']); ?>">Accept</button>
                    <?php endif; ?>
                    <button class="mark-as-read" data-notification-id="<?php echo $notification['id']; ?>">Mark as Read</button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No notifications</p>
        <?php endif; ?>
    </div>
</body>
</html>
