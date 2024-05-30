document.addEventListener("DOMContentLoaded", function() {
    // Attach event listeners to mark notifications as read
    const readButtons = document.querySelectorAll(".mark-as-read");
    readButtons.forEach(button => {
        button.addEventListener("click", markAsRead);
    });

    // Attach event listeners to accept friend request buttons
    const acceptFriendButtons = document.querySelectorAll(".accept-friend-request");
    acceptFriendButtons.forEach(button => {
        button.addEventListener("click", acceptFriendRequest);
    });

    // Attach event listeners to accept event invite buttons
    const acceptEventButtons = document.querySelectorAll(".accept-event-invite");
    acceptEventButtons.forEach(button => {
        button.addEventListener("click", acceptEventInvite);
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

    // Attach event listeners to invite buttons
    const inviteButtons = document.querySelectorAll(".invite-button");
    inviteButtons.forEach(button => {
        button.addEventListener("click", function() {
            inviteFriendToEvent(button.dataset.eventId);
        });
    });

    // Load friends list on page load
    updateFriendsList();

    // Load notifications on page load
    loadNotifications();

    // Load events on page load
    loadEvents();
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
                button.closest(".notification-item").remove();
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

function acceptEventInvite(event) {
    event.preventDefault();
    const button = event.target;
    const eventId = button.dataset.eventId;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "accept_event_invite.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                alert("Event invitation accepted!");
                button.closest(".notification-item").remove();
                addEventToList(response.event);
            } else {
                alert(response.message);
            }
        } else {
            alert("Failed to accept event invitation.");
        }
    };
    xhr.send("event_id=" + encodeURIComponent(eventId));
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

function inviteFriendToEvent(eventId) {
    const friendUsername = prompt("Enter your friend's username to invite:");
    if (friendUsername) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "invite_friend.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert(`Invitation sent to ${friendUsername}`);
            } else {
                alert(xhr.responseText);
            }
        };
        xhr.send("event_id=" + eventId + "&friend_username=" + encodeURIComponent(friendUsername));
    }
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
                listItem.classList.add("notification-item");
                listItem.textContent = notification.content;
                if (notification.type === 'friend_request') {
                    const acceptButton = document.createElement("button");
                    acceptButton.classList.add("accept-friend-request");
                    acceptButton.dataset.friendUsername = notification.username;
                    acceptButton.textContent = "Accept";
                    acceptButton.addEventListener("click", acceptFriendRequest);
                    listItem.appendChild(acceptButton);
                }
                if (notification.type === 'event_invite') {
                    const acceptButton = document.createElement("button");
                    acceptButton.classList.add("accept-event-invite");
                    acceptButton.dataset.eventId = notification.event_id;
                    acceptButton.textContent = "Accept";
                    acceptButton.addEventListener("click", acceptEventInvite);
                    listItem.appendChild(acceptButton);
                }
                notificationsList.appendChild(listItem);

                // Remove notification after 5 seconds
                setTimeout(() => {
                    listItem.remove();
                }, 5000);
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

function loadEvents() {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "fetch_events.php", true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const events = JSON.parse(xhr.responseText);
            const eventsList = document.querySelector("#events-list");
            eventsList.innerHTML = ''; // Clear current list
            events.forEach(event => {
                const listItem = document.createElement("li");
                listItem.innerHTML = `
                    <h3>${event.title}</h3>
                    <p>${event.details}</p>
                    <p>Location: ${event.location}</p>
                    <p>Time: ${event.event_time}</p>
                    <p>Shared by: ${event.username}</p>
                    <p>Attending: ${event.attendees_count}</p>
                    ${event.visibility === 'private' ? `<button class="invite-button" data-event-id="${event.id}">Invite a Friend</button>` : ''}
                `;
                eventsList.appendChild(listItem);
            });

            // Re-attach event listeners to invite buttons
            const inviteButtons = document.querySelectorAll(".invite-button");
            inviteButtons.forEach(button => {
                button.addEventListener("click", function() {
                    inviteFriendToEvent(button.dataset.eventId);
                });
            });
        } else {
            alert("Failed to load events.");
        }
    };
    xhr.send();
}

function addEventToList(event) {
    const eventsList = document.querySelector("#events-list");
    const listItem = document.createElement("li");
    listItem.innerHTML = `
        <h3>${event.title}</h3>
        <p>${event.details}</p>
        <p>Location: ${event.location}</p>
        <p>Time: ${event.event_time}</p>
        <p>Shared by: ${event.username}</p>
        <p>Attending: ${event.attendees_count}</p>
        ${event.visibility === 'private' ? `<button class="invite-button" data-event-id="${event.id}">Invite a Friend</button>` : ''}
    `;
    eventsList.appendChild(listItem);

    // Attach event listener to the new invite button
    const inviteButton = listItem.querySelector(".invite-button");
    if (inviteButton) {
        inviteButton.addEventListener("click", function() {
            inviteFriendToEvent(inviteButton.dataset.eventId);
        });
    }
}
