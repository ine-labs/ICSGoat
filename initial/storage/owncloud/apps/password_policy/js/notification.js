/*
 * Copyright (c) 2018 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {

	$(document).ready(function() {
		// convert action URL to redirect
		$('body').on('OCA.Notification.Action', function(e) {
			if (e.notification.app === 'password_policy'
				&& (e.notification.object_type === 'about_to_expire' || e.notification.object_type === 'expired')
				&& e.action.type === 'GET'
			) {
				OC.redirect(e.notification.link);
				return false;
			}
		});
	});
})();

