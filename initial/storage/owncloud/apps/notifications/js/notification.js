/**
 * ownCloud - Notifications
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tom Needham <tom@owncloud.com>
 * @copyright Tom Needham 2015
 */

(function() {

	/**
	 * Initialise the notification
	 */
	var Notif = function(jsonData){
		// TODO handle defaults
		this.app = jsonData.app;
		this.user = jsonData.user;
		this.timestamp = moment(jsonData.datetime).format('X');
		this.object_type = jsonData.object_type;
		this.object_id = jsonData.object_id;
		this.subject = jsonData.subject;
		this.message = jsonData.message;
		this.link = jsonData.link;
		this.icon = jsonData.icon;
		this.actions = jsonData.actions; // TODO some parsing here?
		this.notification_id = jsonData.notification_id;
	};

	Notif.prototype = {

		app: null,

		user: null,

		timestamp: null,

		object_type: null,

		object_id: null,

		subject: null,

		message: null,

		link: null,

		actions: [],

		notification_id: null,

		getSubject: function() {
			return this.subject;
		},

		getTimestamp: function() {
			return this.timestamp;
		},

		getObjectId: function() {
			return this.object_id;
		},

		getLink: function() {
			return this.link;
		},

		getIcon: function() {
			return this.icon;
		},

		getActions: function() {
			return this.actions;
		},

		getId: function() {
			return this.notification_id;
		},

		getMessage: function() {
			var message = this.message;

			/**
			 * Trim on word end after 100 chars or hard 120 chars
			 */
			if (message.length > 120) {
				var spacePosition = message.indexOf(' ', 100);
				if (spacePosition !== -1 && spacePosition <= 120) {
					message = message.substring(0, spacePosition);
				} else {
					message = message.substring(0, 120);
				}
				message += '…';
			}

			message = message.replace(new RegExp("\n", 'g'), ' ');

			return message;
		},

		getRawMessage: function() {
			return this.message;
		},

		getEl: function() {
			return $('div.notification[data-id='+escapeHTML(this.getId())+']');
		},

		getApp: function() {
			return this.app;
		},

		/**
		 * Generates the HTML for the notification
		 */
		renderElement: function() {
			// FIXME: use handlebars template
			var cssNameSpace = 'notification';

			var $container = $('<div>', {
				'class'          : cssNameSpace,
				'data-id'        : escapeHTML(this.getId()),
				'data-timestamp' : escapeHTML(this.getTimestamp())
			});

			var $close = $('<button>', {
				'class' : cssNameSpace + '-delete icon icon-close svg',
				'text'  : t('notifications', 'Dismiss')
			});

			var $content = $('<div>', {
				'class' : cssNameSpace + '-content'
			});

			var $actions = $('<div>', {
				'class' : cssNameSpace + '-actions'
			});

			var $title = $('<h3>', {
				'class' : cssNameSpace + '-title',
				'text'  : this.getSubject()
			});

			var $message = $('<p>', {
				'class' : cssNameSpace + '-message',
				'text'  : this.getMessage()
			});

			$container
				.append($close);

			$content
				.append($title, $message, $actions)
				.appendTo($container);

			// --- optional ----------------------------------------------------

			if (this.getIcon()) {
				var $icon = $('<img>', {
					'class' : cssNameSpace + '-icon',
					'src'   : this.getIcon()
				});

				// Insert before container
				$container.prepend($icon);
			}

			if (this.getLink()) {
				var $link = $('<a>', {
					'class' : cssNameSpace + '-link',
					'href'  : this.getLink(),
					'text'  : this.getSubject()
				});

				// Replace title content with link
				$title.html($link);
			}

			// --- Add actions -------------------------------------------------

			var actionsData = this.getActions();

			_.each(actionsData, function(actionData) {
				$('<button>', {
					'class'     : cssNameSpace + '-action-button' + (actionData.primary ? ' primary': ''),
					'data-type' : escapeHTML(actionData.type),
					'data-href' : escapeHTML(actionData.link),
					'html'      : escapeHTML(actionData.label)
				}).appendTo($actions);
				// TODO create event handler on click for given action type
			});

			return $container;
		},

		/**
		 * Register notification Binds
		 */
		bindNotificationEvents: function() {

		}

	};

	OCA.Notifications.Notif = Notif;

})();
