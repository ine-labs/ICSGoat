<?php
/**
* @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
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

namespace OCA\Files_external_ftp\AppInfo;

use OCP\AppFramework\App;
use OCP\Files\External\Config\IBackendProvider;

/**
 * @package OCA\Files_External_FTP\AppInfo
 */
class Application extends App implements IBackendProvider {

	public function __construct(array $urlParams = array()) {
		parent::__construct('files_external_ftp', $urlParams);

		$container = $this->getContainer();

		$backendService = $container->getServer()->getStoragesBackendService();
		$backendService->registerBackendProvider($this);
	}

	/**
	 * @{inheritdoc}
	 */
	public function getBackends() {
		$container = $this->getContainer();

		$backends = [
			$container->query('OCA\Files_external_ftp\Backend\FTP'),
		];

		return $backends;
	}
}
