<?php
/**
 * ownCloud Admin_Audit
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright (C) 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;

use OCP\Defaults;
use OCP\IUser;

class Session extends Base {
	/** @var Defaults  */
	private static $defaults;

	/**
	 * Session constructor.
	 *
	 * @param Defaults $defaults
	 */
	public function __construct(Defaults $defaults) {
		self::$defaults = $defaults;
	}

	/**
	 * Log successful login
	 * @param string[] $params
	 */
	public static function post_login($params) {
		if (isset($params['uid'])) {
			$uid = $params['uid'];
		} elseif (isset($params['login'])) {
			$uid = $params['login'];
		} else {
			// Core didnt tell us who logged in.
			return;
		}
		self::getLogger()->log('User {user} logged into {productName} from IP address: {ip}', [
			'user' => $uid,
			'productName' => self::$defaults->getName()
		], [
			'action' => 'user_login',
			'success' => true,
			'login' => $params['uid']
		]);
	}

	/**
	 * Log logout
	 */
	public static function logout() {
		$logger = self::getLogger();
		$user = $logger->getSessionUser();

		if ($user instanceof IUser) {
			$logger->log('User {user} logged out of {productName}', [
				'user' => $user->getUID(),
				'productName' => self::$defaults->getName()
			], [
				'action' => 'user_logout',
			]);
		}
	}

	public static function login_failed($params) {
		$logger = self::getLogger();
		$request = \OC::$server->getRequest();

		$logger->log("Login failed: '{login}' (Remote IP: '{remote_address}')", [
			'login' => $params['user'],
			'remote_address' => $request->getRemoteAddress(),
		], [
			'action' => 'user_login',
			'success' => false,
			'login' => $params['user'],
		]);
	}
}
