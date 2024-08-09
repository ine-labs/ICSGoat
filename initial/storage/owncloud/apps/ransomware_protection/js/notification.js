/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2017 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

$(document).ready(function(){
	var docLink = OC.generateUrl('/settings/personal?sectionid=security'),
		text = t('ransomware_protection', 'Ransomware detected: Your account is locked (read-only for client access) to protect your data. Click here to unlock.'),
		element = $('<a>').attr('href', docLink).text(text);

	OC.Notification.showHtml(element, {
			type: 'error'
	});
});
