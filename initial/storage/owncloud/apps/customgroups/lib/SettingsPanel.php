<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\CustomGroups;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Template;
use OCA\CustomGroups\Service\MembershipHelper;

class SettingsPanel implements ISettings {
	/** @var MembershipHelper */
	private $helper;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IUserSession */
	private $userSession;
	/** @var IConfig */
	private $config;

	public function __construct(
		MembershipHelper $helper,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IConfig $config
	) {
		$this->helper = $helper;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->config = $config;
	}

	public function getPanel() {
		// TODO: cache or add to info.xml ?
		$modules = \json_decode(\file_get_contents(__DIR__ . '/../js/modules.json'), true);
		$tmpl = new Template('customgroups', 'index');
		$tmpl->assign('modules', $modules);
		$tmpl->assign('canCreateGroups', $this->helper->canCreateGroups());
		return $tmpl;
	}

	public function getPriority() {
		return 0;
	}

	public function getSectionID() {
		/**
		 * Check if this app should be shown or not if the user belongs to the disallowed
		 * groups in system config. If the user belongs to the disallowed groups
		 * in system config, then lets not show this app in the personal settings
		 * page of the user.
		 */
		$user = $this->userSession->getUser();
		$disallowedGroups = $this->config->getSystemValue('customgroups.disallowed-groups', null);
		if ($user !== null && $disallowedGroups !== null) {
			foreach ($disallowedGroups as $disallowedGroup) {
				$group = $this->groupManager->get($disallowedGroup);
				if ($group === null) {
					continue;
				}

				if ($this->groupManager->isInGroup($user->getUID(), $group->getGID())) {
					return '';
				}
			}
		}

		return 'customgroups';
	}
}
