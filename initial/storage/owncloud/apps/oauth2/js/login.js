/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

$(document).ready(function(){
	var $loginMessage = $('#body-login').find('#message');
	if ($loginMessage.length) {
		var data = $("data[key='oauth2']");
		var msg = t('oauth2', 'The application "{app}" is requesting access to your account. To authorize it, please log in first.', {app : data.attr('client')});
		$loginMessage.parent().append('<div class="warning"><div class="icon-info-white" />'+msg+'</div>');
		var user = data.attr('user');
		if (user) {
			$('#password')
				.val('')
				.get(0).focus();
			$('#user')
				.val(user);
		}
	}
});
