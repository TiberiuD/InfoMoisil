var notifications = []

function refreshNotifications() {
	$("#notificationArea").empty();

	var unread = 0;

	$.each(notifications, function(key, item) {
		if(item['read']) {
			text = ' \
			<li class="collection-item amber lighten-4 avatar"> \
				<i class="material-icons circle blue">info</i> \
				<span class="title">' + item['message'] + '</span> \
				<p>' + item['time'] + '</p> \
			</li>';
		} else {
			text = ' \
			<li class="collection-item amber lighten-2 avatar"> \
				<i class="material-icons circle blue">info</i> \
				<span class="title">' + item['message'] + '</span> \
				<p>' + item['time'] + '</p> \
				<a onclick="readNotification(' + key + ')" class="secondary-content"><i class="material-icons">check</i></a> \
			</li>';

			unread = unread + 1;
		}
		$("#notificationArea").append(text);
		$("#notificationBell").addClass("yellow-text text-lighten-2");
		$("#readAllNotifications").show();
	});

	if(unread == 0) {
		$("#notificationBell").removeClass("yellow-text text-lighten-2");
		$("#readAllNotifications").hide();
	}

	if(notifications.length === 0) {
		$("#notificationArea").append("<p>Nu exista notificari necitite!</p>");
	}
}

function retrieveNotifications() {
	$.ajax({
		url: '/?ajax&type=get_notifications',
		success: function(data) {
			notifications = jQuery.parseJSON(data)
			refreshNotifications();
		}
	});

	setTimeout(retrieveNotifications, 5000);
}

function readNotification(key) {
	notifications[key]["read"] = true;
	refreshNotifications();

	$.ajax({
		url: '/?ajax&type=read_notification&id=' + notifications[key]["id"],
	});
}

function readAllNotifications() {
	$.each(notifications, function(key, item) {
		readNotification(key);
	});
}

$(document).ready(function() {
	retrieveNotifications();
});