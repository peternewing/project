document.addEventListener("DOMContentLoaded", function() {
    let notificationHideTimer;

    const notificationsButton = document.querySelector(".notifications-button");
    const notificationsList = document.querySelector(".notifications-list");

    if (notificationsButton && notificationsList) {
        notificationsButton.addEventListener("mouseenter", showNotifications);
        notificationsButton.addEventListener("mouseleave", startHideNotificationsTimer);

        notificationsList.addEventListener("mouseenter", clearHideNotificationsTimer);
        notificationsList.addEventListener("mouseleave", startHideNotificationsTimer);
    } else {
        console.error("Notifications button or list element not found.");
    }

    document.body.addEventListener("click", function(event) {
        if (event.target.classList.contains("accept-friend-request")) {
            acceptFriendRequest(event);
        }
        if (event.target.classList.contains("accept-event-invite")) {
            acceptEventInvite(event);
        }
        if (event.target.classList.contains("add-friend-button")) {
            sendFriendRequest(event.target.dataset.friendUsername);
        }
        if (event.target.classList.contains("invite-button")) {
            inviteFriendToEvent(event.target.dataset.eventId);
        }
    });

    const eventForm = document.querySelector("#event-form");
    if (eventForm) {
        eventForm.addEventListener("submit", validateEventForm);
    }

    const searchForm = document.querySelector("#search-form");
    if (searchForm) {
        searchForm.addEventListener("submit", validateSearchForm);
    }

    updateFriendsList();
    loadEvents();

    function showNotifications() {
        clearHideNotificationsTimer();
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "get_notifications.php", true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const result = JSON.parse(xhr.responseText);
                if (result.success) {
                    notificationsList.innerHTML = ''; // Clear current list
                    result.notifications.forEach(notification => {
                        const listItem = document.createElement("li");
                        listItem.innerHTML = `
                            ${notification.content}
                            ${notification.type === 'friend_request' ? `<button class="accept-friend-request" data-friend-username="${notification.username}">Accept</button>` : ''}
                            ${notification.type === 'event_invite' ? `<button class="accept-event-invite" data-event-id="${notification.event_id}">Accept</button>` : ''}
                        `;
                        notificationsList.appendChild(listItem);
                    });
                    notificationsList.style.display = "block";
                } else {
                    console.error("Failed to load notifications: " + result.message);
                }
            } else {
                console.error("Failed to load notifications with status: " + xhr.status);
            }
        };
        xhr.onerror = function() {
            console.error("Network error while loading notifications.");
        };
        xhr.send();
    }

    function hideNotifications() {
        if (notificationsList) {
            notificationsList.style.display = "none";
        } else {
            console.error("Notifications list element not found.");
        }
    }

    function startHideNotificationsTimer() {
        notificationHideTimer = setTimeout(hideNotifications, 3000);
    }

    function clearHideNotificationsTimer() {
        clearTimeout(notificationHideTimer);
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
                    const listItem = button.closest("li");
                    if (listItem) {
                        listItem.remove();
                    } else {
                        console.error("List item element not found.");
                    }
                    updateFriendsList();
                } else {
                    alert(response.message);
                    console.error(response.message);
                }
            } else {
                alert("Failed to accept friend request.");
                console.error("Failed with status: " + xhr.status);
                console.error(xhr.responseText);
            }
        };
        xhr.onerror = function() {
            console.error("Network error while accepting friend request.");
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
                const result = JSON.parse(xhr.responseText);
                if (result.success) {
                    alert("Event invitation accepted!");
                    const listItem = button.closest("li");
                    if (listItem) {
                        listItem.remove();
                    } else {
                        console.error("List item element not found.");
                    }
                    addEventToList(result.event);
                } else {
                    alert(result.message);
                    console.error(result.message);
                }
            } else {
                alert("Failed to accept event invitation.");
                console.error("Failed with status: " + xhr.status);
                console.error(xhr.responseText);
            }
        };
        xhr.onerror = function() {
            console.error("Network error while accepting event invitation.");
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
        xhr.onerror = function() {
            console.error("Network error while sending friend request.");
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
            xhr.onerror = function() {
                console.error("Network error while inviting friend to event.");
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
                if (friendsList) {
                    friendsList.innerHTML = ''; // Clear current list
                    friends.forEach(friend => {
                        const listItem = document.createElement("li");
                        listItem.textContent = friend.username; // Use friend's username
                        friendsList.appendChild(listItem);
                    });
                } else {
                    console.error("Friends list element not found.");
                }
            } else {
                alert("Failed to load friends list.");
                console.error("Failed with status: " + xhr.status);
                console.error(xhr.responseText);
            }
        };
        xhr.onerror = function() {
            console.error("Network error while loading friends list.");
        };
        xhr.send();
    }

    function loadEvents() {
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "fetch_events.php", true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const events = JSON.parse(xhr.responseText);
                const eventsList = document.querySelector("#events-list");
                if (eventsList) {
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

                    const inviteButtons = document.querySelectorAll(".invite-button");
                    inviteButtons.forEach(button => {
                        button.addEventListener("click", function() {
                            inviteFriendToEvent(button.dataset.eventId);
                        });
                    });
                } else {
                    console.error("Events list element not found.");
                }
            } else {
                alert("Failed to load events.");
                console.error("Failed with status: " + xhr.status);
            }
        };
        xhr.onerror = function() {
            console.error("Network error while loading events.");
        };
        xhr.send();
    }

    function addEventToList(event) {
        const eventsList = document.querySelector("#events-list");
        if (eventsList) {
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

            const inviteButton = listItem.querySelector(".invite-button");
            if (inviteButton) {
                inviteButton.addEventListener("click", function() {
                    inviteFriendToEvent(inviteButton.dataset.eventId);
                });
            }
        } else {
            console.error("Events list element not found.");
        }
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
});
