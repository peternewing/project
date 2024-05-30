<?php
session_start();
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

$isLoggedIn = isset($_SESSION['user_id']);
$error = $success = '';

// Handle event form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'], $_POST['details'], $_POST['location'], $_POST['event_time'], $_POST['visibility']) && $isLoggedIn) {
    $title = $_POST['title'];
    $details = $_POST['details'];
    $location = $_POST['location'];
    $event_time = $_POST['event_time'];
    $visibility = $_POST['visibility'];
    $user_id = $_SESSION['user_id'];

    if (create_event($mysqli, $user_id, $title, $details, $location, $event_time, $visibility)) {
        if (!$_SESSION['is_moderator']) {
            promote_to_moderator($mysqli, $user_id);
            $_SESSION['is_moderator'] = 1;
        }
        $success = "Event created successfully.";
    } else {
        $error = "Failed to create event.";
    }
}

// Fetch all events
$eventsQuery = $isLoggedIn ? "
    SELECT events.*, users.username 
    FROM events 
    JOIN users ON events.user_id = users.id 
    WHERE events.visibility = 'public' 
       OR events.user_id = {$_SESSION['user_id']} 
       OR EXISTS (SELECT 1 FROM invitations WHERE invitations.event_id = events.id AND invitations.user_id = {$_SESSION['user_id']} AND invitations.status = 'accepted')
    ORDER BY events.event_time DESC
" : "
    SELECT events.*, users.username 
    FROM events 
    JOIN users ON events.user_id = users.id 
    WHERE events.visibility = 'public' 
    ORDER BY events.event_time DESC
";

$events = $mysqli->query($eventsQuery);
if (!$events) {
    die('Error executing query: ' . $mysqli->error);
}

// Handle user search
$search_results = [];
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
    $search_results = search_users($mysqli, $search_query);
}

// Get notifications
$notifications = [];
if ($isLoggedIn) {
    $notifications = get_notifications($mysqli, $_SESSION['user_id']);
    $unread_count = 0;
    while ($notification = $notifications->fetch_assoc()) {
        if (!$notification['is_read']) {
            $unread_count++;
        }
    }
    // Reset result pointer to fetch again in the notifications page
    $notifications->data_seek(0);
}

// Fetch friends
$friends = [];
if ($isLoggedIn) {
    $friends = get_friends($mysqli, $_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Sharing</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <nav>
        <a href="index.php">Home</a>
        <?php if ($isLoggedIn): ?>
            <a href="notifications.php">Notifications (<?php echo $unread_count; ?>)</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>

    <?php if ($isLoggedIn): ?>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>

        <form id="event-form" action="index.php" method="post">
            <h2>Share an Event</h2>
            <?php if ($error) echo "<p class='error'>$error</p>"; ?>
            <?php if ($success) echo "<p class='success'>$success</p>"; ?>
            <label>Title:</label>
            <input type="text" name="title" required>
            <label>Details:</label>
            <textarea name="details" required></textarea>
            <label>Location:</label>
            <input type="text" name="location" required>
            <label>Event Time:</label>
            <input type="datetime-local" name="event_time" required>
            <label>Visibility:</label>
            <select name="visibility" required>
                <option value="public">Public</option>
                <option value="private">Private</option>
            </select>
            <button type="submit">Share</button>
        </form>

        <form id="search-form" action="index.php" method="get">
            <h2>Search Users</h2>
            <input type="text" name="search" placeholder="Search for users...">
            <button type="submit">Search</button>
        </form>

        <?php if (!empty($search_results)): ?>
            <h2>Search Results</h2>
            <ul>
                <?php while($user = $search_results->fetch_assoc()): ?>
                    <li>
                        <?php echo htmlspecialchars($user['username']); ?> 
                        <button class="add-friend-button" data-friend-username="<?php echo htmlspecialchars($user['username']); ?>">Add Friend</button>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

        <h2>Your Friends</h2>
        <ul id="friends-list">
            <?php if (!empty($friends)): ?>
                <?php while($friend = $friends->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($friend['username']); ?></li>
                <?php endwhile; ?>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <h2>Events</h2>
    <ul>
        <?php while($event = $events->fetch_assoc()): ?>
            <li>
                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                <p><?php echo htmlspecialchars($event['details']); ?></p>
                <p>Location: <?php echo htmlspecialchars($event['location']); ?></p>
                <p>Time: <?php echo htmlspecialchars($event['event_time']); ?></p>
                <p>Shared by: <?php echo htmlspecialchars($event['username']); ?></p>
                <?php if ($isLoggedIn && $event['visibility'] == 'private' && $event['user_id'] == $_SESSION['user_id']): ?>
                    <button class="invite-button" data-event-id="<?php echo htmlspecialchars($event['id']); ?>">Invite a Friend</button>
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
    </ul>
</body>
</html>

