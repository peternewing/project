<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../db.php';

// Register a new user
function register_user($mysqli, $username, $email, $password) {
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("sss", $username, $email, $password_hashed);
    return $stmt->execute();
}

// Log in an existing user
function login_user($mysqli, $username, $password) {
    $sql = "SELECT id, username, password, is_moderator FROM users WHERE username = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $password_hashed, $is_moderator);
        $stmt->fetch();
        if (password_verify($password, $password_hashed)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['is_moderator'] = $is_moderator;
            return true;
        }
    }
    return false;
}

// Create an event
function create_event($mysqli, $user_id, $title, $details, $location, $event_time, $visibility) {
    $sql = "INSERT INTO events (user_id, title, details, location, event_time, visibility) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("isssss", $user_id, $title, $details, $location, $event_time, $visibility);
    return $stmt->execute();
}

// Search users by query
function search_users($mysqli, $query) {
    $sql = "SELECT id, username, email FROM users WHERE username LIKE ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $search_query = "%" . $query . "%";
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    return $stmt->get_result();
}

// Send a friend request
function send_friend_request($mysqli, $user_id, $friend_username) {
    $friend = get_user_by_username($mysqli, $friend_username);
    if (!$friend) {
        error_log("User not found: $friend_username");
        return false;
    }
    $friend_id = $friend['id'];
    $username = get_user_by_id($mysqli, $user_id)['username'];

    // Check if the friend request already exists
    $sql_check = "SELECT * FROM friendships WHERE user_id = ? AND friend_id = ? AND status = 'pending'";
    $stmt_check = $mysqli->prepare($sql_check);
    if (!$stmt_check) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt_check->bind_param("ii", $user_id, $friend_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        return false;
    }

    $sql = "INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("ii", $user_id, $friend_id);
    if ($stmt->execute()) {
        $content = "You have a new friend request from $username";
        $sql = "INSERT INTO notifications (user_id, type, content, sender_id) VALUES (?, 'friend_request', ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
            return false;
        }
        $stmt->bind_param("isi", $friend_id, $content, $user_id);
        return $stmt->execute();
    }
    return false;
}

// Accept a friend request
function accept_friend_request($mysqli, $user_id, $friend_username) {
    $friend = get_user_by_username($mysqli, $friend_username);
    if (!$friend) {
        return false;
    }
    $friend_id = $friend['id'];

    $sql = "UPDATE friendships SET status = 'accepted' WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?) AND status = 'pending'";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("iiii", $friend_id, $user_id, $user_id, $friend_id);
    if ($stmt->execute()) {
        $user = get_user_by_id($mysqli, $user_id);
        $content = "{$user['username']} accepted your friend request";
        $sql = "INSERT INTO notifications (user_id, type, content, sender_id) VALUES (?, 'friend_accept', ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
            return false;
        }
        $stmt->bind_param("isi", $friend_id, $content, $user_id);
        return $stmt->execute();
    }
    return false;
}

// Invite a friend to an event
function invite_friend_to_event($mysqli, $event_id, $friend_username) {
    $friend = get_user_by_username($mysqli, $friend_username);
    if (!$friend) {
        return false;
    }
    $friend_id = $friend['id'];
    $title = get_event_by_id($mysqli, $event_id)['title'];
    $user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO invitations (event_id, user_id, inviter_id, status) VALUES (?, ?, ?, 'pending')";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("iii", $event_id, $friend_id, $user_id);
    if ($stmt->execute()) {
        $content = "You are invited to the event: $title";
        $sql = "INSERT INTO notifications (user_id, type, content, sender_id) VALUES (?, 'event_invite', ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
            return false;
        }
        $stmt->bind_param("isi", $friend_id, $content, $user_id);
        return $stmt->execute();
    }
    return false;
}

// Accept an event invite
function accept_event_invite($mysqli, $user_id, $event_id) {
    $mysqli->autocommit(false);

    try {
        // Update the invitations table
        $sql = "UPDATE invitations SET status = 'accepted' WHERE event_id = ? AND user_id = ?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed for update invitations: (" . $mysqli->errno . ") " . $mysqli->error);
        }
        $stmt->bind_param("ii", $event_id, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed for update invitations: (" . $stmt->errno . ") " . $stmt->error);
        }

        // Insert into event_attendees table
        $sql_insert = "INSERT INTO event_attendees (event_id, user_id) VALUES (?, ?)";
        $stmt_insert = $mysqli->prepare($sql_insert);
        if (!$stmt_insert) {
            throw new Exception("Prepare failed for insert event_attendees: (" . $mysqli->errno . ") " . $mysqli->error);
        }
        $stmt_insert->bind_param("ii", $event_id, $user_id);
        if (!$stmt_insert->execute()) {
            throw new Exception("Execute failed for insert event_attendees: (" . $stmt_insert->errno . ") " . $stmt_insert->error);
        }

        // Update attendees count
        $sql_update = "UPDATE events SET attendees_count = attendees_count + 1 WHERE id = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        if (!$stmt_update) {
            throw new Exception("Prepare failed for update attendees_count: (" . $mysqli->errno . ") " . $mysqli->error);
        }
        $stmt_update->bind_param("i", $event_id);
        if (!$stmt_update->execute()) {
            throw new Exception("Execute failed for update attendees_count: (" . $stmt_update->errno . ") " . $stmt_update->error);
        }

        $mysqli->commit();
        return true;
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log($e->getMessage());
        return false;
    } finally {
        $mysqli->autocommit(true);
    }
}

// Fetch event details by event ID
function get_event_by_id($mysqli, $event_id) {
    $sql = "SELECT events.*, users.username 
            FROM events 
            JOIN users ON events.user_id = users.id 
            WHERE events.id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed for get_event_by_id: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("i", $event_id);
    if (!$stmt->execute()) {
        error_log("Execute failed for get_event_by_id: (" . $stmt->errno . ") " . $stmt->error);
        return false;
    }
    return $stmt->get_result()->fetch_assoc();
}

// Fetch all events for a user
function fetch_events($mysqli, $user_id) {
    $sql = "
    SELECT events.*, users.username, 
           (SELECT COUNT(*) FROM event_attendees WHERE event_attendees.event_id = events.id) + 1 AS attendees_count
    FROM events 
    JOIN users ON events.user_id = users.id 
    WHERE events.visibility = 'public' 
       OR events.user_id = ? 
       OR EXISTS (SELECT 1 FROM event_attendees WHERE event_attendees.event_id = events.id AND event_attendees.user_id = ?)
    ORDER BY events.event_time DESC";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch public events
function fetch_public_events($mysqli) {
    $sql = "
    SELECT events.*, users.username 
    FROM events 
    JOIN users ON events.user_id = users.id 
    WHERE events.visibility = 'public' 
    ORDER BY events.event_time DESC";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Get notifications for a user
function get_notifications($mysqli, $user_id) {
    $sql = "SELECT notifications.*, users.username 
            FROM notifications 
            JOIN users ON notifications.sender_id = users.id 
            WHERE notifications.user_id = ? 
            ORDER BY notifications.created_at DESC";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Mark a notification as read
function mark_notification_as_read($mysqli, $notification_id) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

// Mark all notifications as read for a user
function mark_all_notifications_as_read($mysqli, $user_id) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

// Get friends of a user
function get_friends($mysqli, $user_id) {
    $sql = "
        SELECT users.id, users.username, users.email 
        FROM users 
        JOIN friendships ON users.id = friendships.friend_id 
        WHERE friendships.user_id = ? AND friendships.status = 'accepted'
        UNION
        SELECT users.id, users.username, users.email 
        FROM users 
        JOIN friendships ON users.id = friendships.user_id 
        WHERE friendships.friend_id = ? AND friendships.status = 'accepted'";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Get user information by user ID
function get_user_by_id($mysqli, $user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get user information by username
function get_user_by_username($mysqli, $username) {
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Promote a user to moderator
function promote_to_moderator($mysqli, $user_id) {
    $sql = "UPDATE users SET is_moderator = 1 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}
?>
