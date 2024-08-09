/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
(function(OC, OCA) {

	if (!OCA.Guests) {
		OCA.Guests = {};
	}

	OCA.Guests.updateNavigation = function () {
		$.get(
			OC.generateUrl('apps/guests/whitelist'),
			'',
			function (data) {
				if (data.enabled) {
					// remove items from navigation menu
					$('#navigation li').each(function (i, e) {
						var $e = $(e);
						if ($.inArray($e.data('id'), data.apps) < 0) {
							$e.remove();
						}
					});

					// special treatment for apps in UI
					// activity
					if ($.inArray('activity', data.apps) < 0) {
						$('li[data-tabid="activityTabView"]').remove();
						$('#activityTabView').remove();
						OC.Notification.origShowTemporary =  OC.Notification.showTemporary;
						OC.Notification.showTemporary = function(msg) {
							if (msg === t('activity', 'Error loading activities')) {
								return;
							}
							// keep other messages
							OC.Notification.origShowTemporary(msg);
						};
					}
					// comments
					if ( $.inArray('comments', data.apps) < 0) {
						$('li[data-tabid="commentsTabView"]').remove();
						$('#commentsTabView').remove();
					}
					// gallery
					if ($.inArray('gallery', data.apps) < 0) {
						$('#gallery-button').remove();
					}
					// settings
					if ($.inArray('settings', data.apps) < 0) {
						// remove settings and help
						$('#expanddiv > ul > li:first-child').remove();
						$('#expanddiv > ul > li:first-child').remove();
					}
					// trashbin
					if ($.inArray('files_trashbin', data.apps) < 0) {
						// remove would cause a css issue
						$('li.nav-trashbin').hide();
						$('.delete-selected').remove();
					}
					// versions
					if ($.inArray('files_versions', data.apps) < 0) {
						$('li[data-tabid="versionsTabView"]').remove();
						$('#versionsTabView').remove();
					}
				}
			},
			'json'
		);
	};

})(OC, OCA);
