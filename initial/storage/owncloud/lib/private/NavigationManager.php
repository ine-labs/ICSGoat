<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC;

use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;

/**
 * Manages the ownCloud navigation
 */
class NavigationManager implements INavigationManager {
	protected $entries = [];
	protected $closureEntries = [];
	protected $activeEntry;
	/** @var bool */
	protected $init = false;
	/** @var IAppManager */
	protected $appManager;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IFactory */
	private $l10nFac;
	/** @var IUserSession */
	private $userSession;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IConfig */
	private $config;

	public function __construct(
		IAppManager $appManager = null,
		IURLGenerator $urlGenerator = null,
		IFactory $l10nFac = null,
		IUserSession $userSession = null,
		IGroupManager $groupManager = null,
		IConfig $config = null
	) {
		$this->appManager = $appManager;
		$this->urlGenerator = $urlGenerator;
		$this->l10nFac = $l10nFac;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->config = $config;
	}

	/**
	 * Creates a new navigation entry
	 *
	 * @param array|\Closure $entry Array containing: id, name, order, icon and href key
	 *					The use of a closure is preferred, because it will avoid
	 * 					loading the routing of your app, unless required.
	 * @return void
	 */
	public function add($entry) {
		if ($entry instanceof \Closure) {
			$this->closureEntries[] = $entry;
			return;
		}

		$entry['active'] = false;
		if (!isset($entry['icon'])) {
			$entry['icon'] = '';
		}
		$this->entries[] = $entry;
	}

	/**
	 * returns all the added Menu entries
	 * @return array an array of the added entries
	 */
	public function getAll() {
		$this->init();
		foreach ($this->closureEntries as $c) {
			$this->add($c());
		}
		$this->closureEntries = [];
		return $this->entries;
	}

	/**
	 * removes all the entries
	 */
	public function clear() {
		$this->entries = [];
		$this->closureEntries = [];
		$this->init = false;
	}

	/**
	 * Sets the current navigation entry of the currently running app
	 * @param string $id of the app entry to activate (from added $entry)
	 */
	public function setActiveEntry($id) {
		$this->activeEntry = $id;
	}

	/**
	 * gets the active Menu entry
	 * @return string id or empty string
	 *
	 * This function returns the id of the active navigation entry (set by
	 * setActiveEntry
	 */
	public function getActiveEntry() {
		return $this->activeEntry;
	}

	private function init() {
		if ($this->init) {
			return;
		}
		$this->init = true;
		if ($this->appManager === null) {
			return;
		}
		foreach ($this->appManager->getInstalledApps() as $app) {
			// load plugins and collections from info.xml
			$info = $this->appManager->getAppInfo($app);
			if (!isset($info['navigation'])) {
				continue;
			}
			$nav = $info['navigation'];
			// either a route or a static page must be defined
			if (!isset($nav['route']) && !isset($nav['static'])) {
				continue;
			}
			$role = isset($nav['@attributes']['role']) ? $nav['@attributes']['role'] : 'all';
			if ($role === 'admin' && !$this->isAdmin()) {
				continue;
			}
			$l = $this->l10nFac->get($app);
			$order = isset($nav['order']) ? $nav['order'] : 100;
			if (isset($nav['route'])) {
				$route = $this->urlGenerator->linkToRoute($nav['route']);
			} else {
				$html = 'index.html';
				if (isset($nav['static'])) {
					$html = $nav['static'];
				}
				$route = $this->urlGenerator->linkTo($app, $html);
			}
			$name = isset($nav['name']) ? $nav['name'] : \ucfirst($app);
			$icon = isset($nav['icon']) ? $nav['icon'] : 'app.svg';
			$iconPath = null;
			foreach ([$icon, "$app.svg"] as $i) {
				try {
					$iconPath = $this->urlGenerator->imagePath($app, $i);
					break;
				} catch (\RuntimeException $ex) {
					// no icon? - ignore it then
				}
			}

			if ($iconPath === null) {
				$iconPath = $this->urlGenerator->imagePath('core', 'default-app-icon.svg');
			}

			$this->add([
				'id' => $app,
				'order' => $order,
				'href' => $route,
				'icon' => $iconPath,
				'name' => $l->t($name),
			]);
		}

		// add web if setup
		$webBaseUrl = $this->config->getSystemValue('web.baseUrl', null);
		$webIconKey = 'web.icon';
		$webIconLabel = 'web.label';
		if (!$webBaseUrl) {
			// Check the old phoenix.baseUrl system key to provide compatibility across
			// the name change from phoenix to web.
			$webBaseUrl = \OC::$server->getConfig()->getSystemValue('phoenix.baseUrl', null);
			$webIconKey = 'phoenix.icon';
			$webIconLabel = 'phoenix.label';
		}
		if ($webBaseUrl && !$this->suppressWebNavItem()) {
			$iconPath = $this->config->getSystemValue($webIconKey, $this->urlGenerator->imagePath('core', 'apps/web.svg'));
			$l = $this->l10nFac->get("core");
			$label = $this->config->getSystemValue($webIconLabel, $l->t('New Design'));
			$this->add([
				'id' => 'web',
				'href' => \rtrim($webBaseUrl, '/') . '/index.html',
				'name' => $label,
				'icon' => $iconPath,
				'order' => 99
			]);
		}
	}

	/**
	 * Decides if the `Web` nav item should be hidden.
	 *
	 * Since we have two different deployment modes - external or as oc10 app -
	 * we can't just decide based on whether or not the app is enabled. If it's
	 * not installed at all we skip the `enabled` check and just rely on the
	 * webBaseUrl being configured or not.
	 *
	 * @return bool
	 */
	private function suppressWebNavItem(): bool {
		if (!$this->appManager->isInstalled('web')) {
			return false;
		}
		return !$this->appManager->isEnabledForUser('web');
	}

	private function isAdmin() {
		$user = $this->userSession->getUser();
		if ($user !== null) {
			return $this->groupManager->isAdmin($user->getUID());
		}
		return false;
	}
}
