<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Market\Controller;

use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\IRequest;

class LocalAppsController extends Controller {

	/** @var IAppManager */
	private $appManager;

	public function __construct($appName, IRequest $request, IAppManager $appManager) {
		parent::__construct($appName, $request);
		$this->appManager = $appManager;
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @return array|mixed
	 */
	public function index($state = 'enabled') {
		$apps = \OC_App::listAllApps();
		$apps = \array_filter($apps, function ($app) use ($state) {
			if ($state === 'enabled') {
				return $app['active'];
			}
			return !$app['active'];
		});

		return \array_values(\array_map(function ($app) {
			$missing = $this->getMissingDependencies($app);
			$app['canInstall'] = empty($missing);
			$app['missingDependencies'] = $missing;
			$app['installed'] = true;
			$app['updateInfo'] = [];

			return $app;
		}, $apps));
	}

	private function getMissingDependencies($appInfo) {
		// bad hack - should use OCP
		$l10n = \OC::$server->getL10N('settings');
		$config = \OC::$server->getConfig();
		$dependencyAnalyzer = new DependencyAnalyzer(new Platform($config), $l10n);

		return $dependencyAnalyzer->analyze($appInfo);
	}
}
