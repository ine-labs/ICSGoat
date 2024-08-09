<?php
/**
 * ownCloud - Template Editor
 *
 * @author Jörn Dreyer <jfd@owncloud.com>
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\TemplateEditor\AppInfo;

use OC\Helper\EnvironmentHelper;
use OCA\TemplateEditor\Controller\AdminSettingsController;
use OCA\TemplateEditor\TemplateEditor;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;

class Application extends App {

	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct('templateeditor', $urlParams);

		$container = $this->getContainer();

		$container->registerService('TemplateEditor', function (IAppContainer $container) {
			return new TemplateEditor(
				$container->query('ThemeService'),
				$container->query('AppManager'),
				new EnvironmentHelper(),
				\OC::$server->getL10NFactory()->get('templateeditor')
			);
		});

		$container->registerService('AdminSettingsController', function(IAppContainer $container) {
			return new AdminSettingsController(
				$container->query('AppName'),
				$container->query('Request'),
				$container->query('TemplateEditor')
			);
		});
	}
}
