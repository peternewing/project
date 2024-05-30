document.addEventListener("DOMContentLoaded", function() {
    // Attach event listeners to mark notifications as read
    const readButtons = document.querySelectorAll(".mark-as-read");
    readButtons.forEach(button => {
        button.addEventListener("click", markAsRead);
    });

    // Attach event listeners to accept friend request buttons
    const acceptButtons = document.querySelectorAll(".accept-friend-request");
    acceptButtons.forEach(button => {
        button.addEventListener("click", acceptFriendRequest);
    });

    // Attach event listener to event form
    const eventForm = document.querySelector("#event-form");
    if (eventForm) {
        eventForm.addEventListener("submit", validateEventForm);
    }

    // Attach event listener to search form
    const searchForm = document.querySelector("#search-form");
    if (searchForm) {
        searchForm.addEventListener("submit", validateSearchForm);
    }

    // Attach event listeners to friend request buttons
    const addFriendButtons = document.querySelectorAll(".add-friend-button");
    addFriendButtons.forEach(button => {
        button.addEventListener("click", function() {
            sendFriendRequest(button.dataset.friendUsername);
        });
    });

    // Load friends list on page load
    updateFriendsList();

    // Load notifications on page load
    loadNotifications();
});

function markAsRead(event) {
    const notificationId = event.target.dataset.notificationId;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "mark_notification_as_read.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        if (xhr.status === 200) {
            alert("Notification marked as read");
            location.reload();
        } else {
            alert(xhr.responseText);
        }
    };
    xhr.send("notification_id=" + notificationId);
}

function acceptFriendRequest(event) {
    event.preventDefault();
    const button = event.target;
    const friendUsername = button.dataset.friendUsername;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "accept_friend_request.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                alert("Friend request accepted!");
                button.closest("li").remove();
                updateFriendsList();
            } else {
                alert(response.message);
            }
        } else {
            alert("Failed to accept friend request.");
        }
    };
    xhr.send("friend_username=" + encodeURIComponent(friendUsername));
}

function sendFriendRequest(friendUsername) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "friend_request.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        if (xhr.status === 200) {
            alert("Friend request sent!");
            updateFriendsList();
        } else {
            alert("Failed to send friend request: " + xhr.responseText);
        }
    };
    xhr.send("friend_username=" + encodeURIComponent(friendUsername));
}

function updateFriendsList() {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "get_friends.php", true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const friends = JSON.parse(xhr.responseText);
            const friendsList = document.querySelector("#friends-list");
            friendsList.innerHTML = ''; // Clear current list
            friends.forEach(friend => {
                const listItem = document.createElement("li");
                listItem.textContent = friend.username; // Use friend's username
                friendsList.appendChild(listItem);
            });
        } else {
            alert("Failed to load friends list.");
        }
    };
    xhr.send();
}

function loadNotifications() {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "get_notifications.php", true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const notifications = JSON.parse(xhr.responseText);
            const notificationsList = document.querySelector(".notifications ul");
            notificationsList.innerHTML = ''; // Clear current list
            notifications.forEach(notification => {
                const listItem = document.createElement("li");
                listItem.textContent = notification.content;
                if (notification.type === 'friend_request') {
                    const acceptButton = document.createElement("button");
                    acceptButton.classList.add("accept-friend-request");
                    acceptButton.dataset.friendUsername = notification.username;
                    acceptButton.textContent = "Accept";
                    acceptButton.addEventListener("click", acceptFriendRequest);
                    listItem.appendChild(acceptButton);
                }
                notificationsList.appendChild(listItem);
            });
        } else {
            alert("Failed to load notifications.");
        }
    };
    xhr.send();
}

function validateEventForm(event) {
    const title = document.querySelector("input[name='title']").value;
    const details = document.querySelector("textarea[name='details']").value;
    const location = document.querySelector("input[name='location']").value;
    const eventTime = document.querySelector("input[name='event_time']").value;

    if (!title || !details || !location || !eventTime) {
        event.preventDefault();
        alert("Please fill out all fields.");
    }
}

function validateSearchForm(event) {
    const searchInput = document.querySelector("input[name='search']").value;

    if (!searchInput) {
        event.preventDefault();
        alert("Please enter a search term.");
    }
}
