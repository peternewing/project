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

// Handle friend invitation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['invite_friend'], $_POST['event_id'], $_POST['friend_username']) && $isLoggedIn) {
    $event_id = $_POST['event_id'];
    $friend_username = $_POST['friend_username'];
    if (invite_friend_to_event($mysqli, $event_id, $_SESSION['user_id'], $friend_username)) {
        $success = "Invitation sent to $friend_username.";
    } else {
        $error = "Failed to send invitation.";
    }
}

// Fetch all events
$eventsQuery = $isLoggedIn ? "
    SELECT events.*, users.username, 
           (SELECT COUNT(*) FROM event_attendees WHERE event_attendees.event_id = events.id) + 1 AS attendees_count
    FROM events 
    JOIN users ON events.user_id = users.id 
    WHERE events.visibility = 'public' 
       OR events.user_id = {$_SESSION['user_id']} 
       OR EXISTS (SELECT 1 FROM event_attendees WHERE event_attendees.event_id = events.id AND event_attendees.user_id = {$_SESSION['user_id']})
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
}

// Get friends
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
            <a href="logout.php">Logout</a>
            <div class="notifications">
                <span>Notifications</span>
                <ul>
                    <?php if ($notifications && $notifications->num_rows > 0): ?>
                        <?php while ($notification = $notifications->fetch_assoc()): ?>
                            <li class="notification-item">
                                <?php echo htmlspecialchars($notification['content']); ?>
                                <?php if ($notification['type'] == 'friend_request'): ?>
                                    <button class="accept-friend-request" data-friend-username="<?php echo htmlspecialchars($notification['username']); ?>">Accept</button>
                                <?php elseif ($notification['type'] == 'event_invite'): ?>
                                    <button class="accept-event-invite" data-event-id="<?php echo htmlspecialchars($notification['event_id']); ?>">Accept</button>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>No notifications</li>
                    <?php endif; ?>
                </ul>
            </div>
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
            <?php if ($friends && $friends->num_rows > 0): ?>
                <?php while ($friend = $friends->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($friend['username']); ?></li>
                <?php endwhile; ?>
            <?php else: ?>
                <li>No friends yet.</li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <h2>Events</h2>
    <ul id="events-list">
        <?php while($event = $events->fetch_assoc()): ?>
            <li>
                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                <p><?php echo htmlspecialchars($event['details']); ?></p>
                <p>Location: <?php echo htmlspecialchars($event['location']); ?></p>
                <p>Time: <?php echo htmlspecialchars($event['event_time']); ?></p>
                <p>Shared by: <?php echo htmlspecialchars($event['username']); ?></p>
                <p>Attending: <?php echo htmlspecialchars($event['attendees_count']); ?></p>
                <?php if ($event['visibility'] == 'private' && $event['user_id'] == $_SESSION['user_id']): ?>
                    <form action="index.php" method="post" class="invite-form">
                        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['id']); ?>">
                        <input type="text" name="friend_username" placeholder="Friend's username" required>
                        <button type="submit" name="invite_friend">Invite</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
    </ul>
</body>
</html>
