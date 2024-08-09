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

var Ransomware_ProtectionSettings = Ransomware_ProtectionSettings || {

	onLock: function() {
		var that = this;
		OC.dialogs.confirm(t('ransomware_protection', 'Are you sure, you want to lock write access for your account?'), t('ransomware_protection', 'Ransomware Protection'), function (e) {
			if (e === true) {
				that._lock();
			}
		});
	},

	onUnlock: function() {
		var that = this;
		OC.dialogs.confirm(t('ransomware_protection', 'Are you sure, you want to re-enable write access for your account?'), t('ransomware_protection', 'Ransomware Protection'), function (e) {
			if (e === true) {
				that._unlock();
			}
		});
	},

	_lock: function(){
		$.ajax({
			url: OC.generateUrl('/apps/ransomware_protection/lock'),
		}).done(
			function() {
				location.reload();
		});

	},

	_unlock: function(){
		$.ajax({
			url: OC.generateUrl('/apps/ransomware_protection/unlock'),
		}).done(
			function() {
				$('#notification').hide();
				$('#ransomware-protection-msg-locked').hide();
				$('#ransomware-protection-msg-unlocked').show();
		});
	}
};

$(function() {
	$('#ransomware-protection-lock').on('click', function () {
		Ransomware_ProtectionSettings.onLock();
	});
	$('#ransomware-protection-unlock').on('click', function () {
		Ransomware_ProtectionSettings.onUnlock();
	});
});
