/**
 * ownCloud
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @copyright (C) 2014-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

$(document).ready(function () {
	$('#logout.shibboleth-logout').on( "click", function () {
		OC.dialogs.info(
			t("user_shibboleth", "You are currently logged in using Shibboleth authentication. You must close your browser to log out of {themeName}.", { themeName : OC.theme.name }),
			t("user_shibboleth", "Logout")
		);
	});

	$(document).on('click', '.settings-button', function(){
		$('#webdavurl').val(OC.linkToRemote('nonshib-webdav'));
	});
});
