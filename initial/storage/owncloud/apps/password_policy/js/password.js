/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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
	var $newPassword = $("#password_policy #new_password"),
		$confirmPassword = $("#password_policy #confirm_password"),
		$submit = $("#password_policy #submit"),
		check = function() {
		if ($confirmPassword.val() !== '' && $newPassword.val() !== $confirmPassword.val()) {
			$newPassword.addClass('password-mismatch');
			$confirmPassword.addClass('password-mismatch');
			$submit.attr('disabled', 'disabled');
		} else {
			$newPassword.removeClass('password-mismatch');
			$confirmPassword.removeClass('password-mismatch');
			$submit.removeAttr('disabled');
		}
	};
	$newPassword.keyup(check);
	$confirmPassword.keyup(check);

	$('form').on('submit', function() {
		$('#submit').addClass('icon-loading-small');
	});
});
