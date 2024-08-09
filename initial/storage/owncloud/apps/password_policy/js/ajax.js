/**
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
$(document).ready(function() {
	$("#password_policy").submit(function(event) {
		event.preventDefault();
		OC.msg.startSaving('#password_policy .msg');

		if (($("input[name=spv_def_special_chars_checked]").is(':checked') === true) &&
			($("input[name=spv_def_special_chars_value]").val() === "")) {
			OC.Notification.showTemporary(t('password_policy', 'Error: The special characters cannot be empty.'));
			OC.msg.finishedSaving('#password_policy .msg', {status: "failed", data: {message: t('password_policy', 'Failed to save!')} });
			return;
		}

		// Load the new token
		$.ajax({
			type: "POST",
			url: OC.generateUrl('/apps/password_policy/update_policy'),
			data: $("#password_policy").serialize()
		}).done(function(data) {
			OC.msg.finishedSaving('#password_policy .msg', {status: "success", data: { message: t('password_policy', 'Saved')} });
		}).fail(function(data) {
			OC.msg.finishedSaving('#password_policy .msg', {status: "failed", data: { message: t('password_policy', 'Failed to save!')} });
		});
	});
});
