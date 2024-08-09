/**
 * ownCloud
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @author Krzesimir Nowak
 * @copyright (C) 2014-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
function shibGetPasswordSuccess(data) {
	var htmlText = '';

	for (var i = 0; i < data.length; ++i) {
		htmlText += '<span>' + data[i] + '</span>';
	}

	$('#webdav_password_div').html(htmlText);
	$('#revoke_password_div').slideDown();
}

function shibGeneratePassword() {
	$.get(
		OC.generateUrl('apps/user_shibboleth/get_new_password'),
		'',
		shibGetPasswordSuccess,
		'json'
	);
}

function shibRevokePasswordSuccess() {
	$('#webdav_password_div').html('');
	$('#revoke_password_div').slideUp();
}

function shibRevokePassword() {
	$.post(
		OC.generateUrl('apps/user_shibboleth/revoke_password'),
		'',
		shibRevokePasswordSuccess,
		'json'
	);
}

function shibHasPasswordSuccess(data) {
	var htmlText = '';

	var p_len = data.desiredLength;
	var g_len = data.desiredGroupLength;
	for (var i = 0; i < Math.floor((p_len + g_len - 1)/g_len); ++i) {
		htmlText += '<span>' + Array(g_len+1).join('*') + '</span>';
	}

	$('#webdav_password_div').html(htmlText);
	$('#revoke_password_div').slideDown();
}

function shibHasPasswordCheck() {
	$.get(
		OC.generateUrl('apps/user_shibboleth/has_password'),
		'',
		shibHasPasswordSuccess,
		'json'
	);
}

document.addEventListener('DOMContentLoaded', function () {
	document.getElementById('generate_password_button').addEventListener('click', shibGeneratePassword);
	document.getElementById('revoke_password_button').addEventListener('click', shibRevokePassword);
	shibHasPasswordCheck();
});

$(document).ready(function () {
	$('fieldset.personalblock').find('h2').filter(":contains('WebDAV')").parent().hide();
});
