<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

namespace OCA\Market;

use OCP\App\AppUpdateNotFoundException;

class Listener {
	/** @var MarketService */
	private $marketService;

	public function __construct(MarketService $marketService) {
		$this->marketService = $marketService;
	}

	public function upgradeAppStoreApp($app, $isMajorUpdate) {
		$updateVersions = $this->marketService->getAvailableUpdateVersions($app);
		$updateVersion = $this->marketService->chooseCandidate(
			$updateVersions,
			$isMajorUpdate
		);
		if ($updateVersion !== false) {
			$this->marketService->updateApp($app, $updateVersion);
		} else {
			throw new AppUpdateNotFoundException();
		}
	}

	public function reinstallAppStoreApp($app) {
		// only reinstall the code, do not run migrations
		$this->marketService->installApp($app, true);
	}
}
