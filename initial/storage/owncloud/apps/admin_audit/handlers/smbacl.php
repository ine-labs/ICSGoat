<?php
/**
 * ownCloud Admin_Audit
 *
 * @author Juan Pablo Villafáñez <jvillafanez@solidgeargroup.com>
 * @copyright (C) 2019 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;

class SmbAcl extends Base {
	public static function beforeSetAcl($event) {
		self::getLogger()->log(
			'{actor} tried to set {descriptor} as descriptor for {ocPath}, mapped as {smbPath} in the SMB server',
			[
			'ocPath' => $event->getArgument('ocPath'),
			'smbPath' => $event->getArgument('smbPath'),
			'descriptor' => \json_encode($event->getArgument('descriptor')),
		],
			[
			'action' => 'before_set_acl',
			'user' => $event->getArgument('user')->getUID(),
			'ocPath' => $event->getArgument('ocPath'),
			'smbPath' => $event->getArgument('smbPath'),
			'descriptor' => $event->getArgument('descriptor'),
		]
		);
	}

	public static function afterSetAcl($event) {
		self::getLogger()->log(
			'{actor} successfully set {descriptor} as descriptor for {ocPath}, mapped as {smbPath} in the SMB server',
			[
			'ocPath' => $event->getArgument('ocPath'),
			'smbPath' => $event->getArgument('smbPath'),
			'descriptor' => \json_encode($event->getArgument('descriptor')),
		],
			[
			'action' => 'after_set_acl',
			'user' => $event->getArgument('user')->getUID(),
			'ocPath' => $event->getArgument('ocPath'),
			'smbPath' => $event->getArgument('smbPath'),
			'descriptor' => $event->getArgument('descriptor'),
		]
		);
	}
}
