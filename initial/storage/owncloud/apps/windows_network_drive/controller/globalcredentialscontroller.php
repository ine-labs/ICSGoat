<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Windows_network_drive\Controller;

use OCA\windows_network_drive\Lib\Auth\GlobalAuth;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

class GlobalCredentialsController extends Controller {
	/** @var GlobalAuth */
	private $globalAuth;

	private $userSession;

	private $groupManager;

	public function __construct(
		$appName,
		IRequest $request,
		GlobalAuth $globalAuth,
		IUserSession $userSession,
		IGroupManager $groupManager
	) {
		parent::__construct($appName, $request);
		$this->globalAuth = $globalAuth;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
	}

	/**
	 * @param string $uid
	 * @param string $user
	 * @param string $password
	 *
	 * @NoAdminRequired
	 */
	public function save($uid, $user, $password) {
		$activeUser = $this->userSession->getUser();
		if ($activeUser->getUID() === $uid || $this->groupManager->isAdmin($activeUser->getUID())) {
			$this->globalAuth->saveAuth($uid, $user, $password);
		} else {
			$response = new Response();
			$response->setStatus(Http::STATUS_FORBIDDEN);
			return $response;
		}
	}
}
