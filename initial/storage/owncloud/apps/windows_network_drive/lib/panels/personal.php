<?php
/**
 * @author Tom Needham <tom@owncloud.com>
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

namespace OCA\Windows_network_drive\Panels;

use OC\Files\External\StoragesBackendService;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Security\ICredentialsManager;
use OCP\Settings\ISettings;
use OCP\Template;

class Personal implements ISettings {

	/** @var ICredentialsManager */
	protected $credentialsManager;
	/** Il10N */
	protected $l;
	/** @var  IUserSession */
	protected $userSession;
	/** @var StoragesBackendService */
	protected $backendService;

	public function __construct(
		IL10N $l,
		ICredentialsManager $credentialsManager,
		IUserSession $userSession,
		StoragesBackendService $backendService
	) {
		$this->l = $l;
		$this->credentialsManager = $credentialsManager;
		$this->userSession = $userSession;
		$this->backendService = $backendService;
	}

	public function getPriority() {
		return 0;
	}

	public function getSectionID() {
		return 'storage';
	}

	public function getPanel() {
		// only show panel if user mounting allowed
		if (!$this->backendService->isUserMountingAllowed()) {
			return null;
		}

		$globalAuth = new \OCA\windows_network_drive\Lib\Auth\GlobalAuth(
			$this->l,
			$this->credentialsManager
		);

		$tmpl = new Template('windows_network_drive', 'globalauth');
		$uid = $this->userSession->getUser()->getUID();

		$credentials = $globalAuth->getAuth($uid);
		if (isset($credentials['password']) && $credentials['password'] !== '') {
			$credentials['password'] = '**PASSWORD SET**';
		}

		$tmpl->assign('globalCredentials', $credentials);
		$tmpl->assign('globalCredentialsUid', $uid);
		return $tmpl;
	}
}
