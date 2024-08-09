<?php
/**
* @author Sujith Haridasan <sharidasan@owncloud.com>
* @copyright (C) 2018 ownCloud GmbH.
*
* This code is covered by the ownCloud Commercial License.
*
* You should have received a copy of the ownCloud Commercial License
* along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
*
*/

namespace OCA\Admin_Audit\Handlers;

use Symfony\Component\EventDispatcher\GenericEvent;

class Impersonate extends Base {
	public static function impersonated(GenericEvent $event) {
		$message = '{actor} {user} impersonated as {targetUser}';
		//The user who tries to switch to other user
		$impersonator = $event->getArgument('impersonator');
		//The user to be switched
		$targetUser = $event->getArgument('targetUser');
		self::getLogger()->log($message, [
			'user' => $impersonator,
			'targetUser' => $targetUser,
		], [
			'action' => 'impersonated',
			'user' => $impersonator,
			'targetUser' => $targetUser
		]);
	}

	public static function loggedout(GenericEvent $event) {
		$message = '{actor} {targetUser} logged out and switch back to original user {user}';
		$impersonator = $event->getArgument('impersonator');
		$targetUser = $event->getArgument('targetUser');
		self::getLogger()->log($message, [
			'targetUser' => $targetUser,
			'user' => $impersonator
		], [
			'action' => 'impersonate_logout',
			'user' => $impersonator,
			'targetUser' => $targetUser
		]);
	}
}
