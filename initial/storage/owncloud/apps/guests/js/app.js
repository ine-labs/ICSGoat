/**
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
(function() {
	if (!OCA.Guests) {
		OCA.Guests = {};
	}

	$(document).ready(function () {
		// not all are always loaded, depending on what page is displayed,
		// so need to initialize conditionally

		// guests.js
		if (OCA.Guests.initSettingsPage) {
			OCA.Guests.initSettingsPage();
		}
		// guestshare.js
		if (OCA.Guests.initGuestSharePlugin) {
			OCA.Guests.initGuestSharePlugin();
		}
		// navigation.js
		if (OCA.Guests.updateNavigation) {
			OCA.Guests.updateNavigation();
		}

		$('#body-login form').on('submit', function(e) {
			$(this).find('button[type="submit"]').addClass('icon-loading-small');
		});
	});

})();
