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

$(function() {
	$('#ransomware-protection-locking-enabled').change(function() {
		$.post(
			OC.generateUrl('/apps/ransomware_protection/lockingEnabled'),
			{
				status: this.checked
			}
		);
	});
});
