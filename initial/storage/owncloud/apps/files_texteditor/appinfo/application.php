<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\Files_Texteditor\AppInfo;

use OCA\Files_Texteditor\Controller\FileHandlingController;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\Util;

class Application extends App {

	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct('files_texteditor', $urlParams);

		$container = $this->getContainer();
		$server = $container->getServer();

		$container->registerService('FileHandlingController', function (IAppContainer $c) use ($server) {
			/** @phan-suppress-next-line PhanUndeclaredClassMethod */
			return new FileHandlingController(
				$c->getAppName(),
				$server->getRequest(),
				$server->getL10N($c->getAppName()),
				$server->getLogger(),
				$server->getShareManager(),
				$server->getUserSession(),
				$server->getRootFolder()
			);
		});
	}

	private function registerEventHooks(): void {
		$container = $this->getContainer();
		$eventDispatcher = $container->getServer()->getEventDispatcher();
		$callback = function () {
			Util::addStyle('files_texteditor', 'DroidSansMono/stylesheet');
			Util::addStyle('files_texteditor', 'style');
			Util::addStyle('files_texteditor', 'mobile');
			Util::addscript('files_texteditor', 'editor');
			Util::addscript('files_texteditor', 'vendor/ace/src-noconflict/ace');
		};

		$eventDispatcher->addListener(
			'OCA\Files::loadAdditionalScripts',
			$callback
		);
		$eventDispatcher->addListener(
			'OCA\Files_Sharing::loadAdditionalScripts',
			$callback
		);
	}

	public function boot(): void {
		$this->registerEventHooks();
	}
}
