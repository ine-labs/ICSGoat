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
namespace OCA\Files_LDAP_Home;

use OCP\Settings\ISettings;
use OCP\Template;

class AdminPanel implements ISettings {

	/** @var Settings */
	protected $settings;
	/** @var Helper */
	protected $helper;

	/**
	 * AdminPanel constructor.
	 *
	 * @param Settings $settings
	 * @param Helper $helper
	 */
	public function __construct(Settings $settings, Helper $helper) {
		$this->settings = $settings;
		$this->helper = $helper;
	}

	public function getPriority() {
		// Show under LDAP app settings
		return 19;
	}

	public function getSectionID() {
		return 'authentication';
	}

	public function getPanel() {
		try {
			$this->helper->checkOperability();
			$isAppWorking = true;
		} catch (\Exception $e) {
			$isAppWorking = false;
		}

		$tmpl = new Template('files_ldap_home', 'settings');
		$tmpl->assign('enabled', $isAppWorking);

		if ($isAppWorking) {
			$tmpl->assign('homeMountNameDefault', $this->settings->getDefaultMountName());
			$tmpl->assign('homeMountName', $this->settings->getMountName());
			$tmpl->assign('attributeMode', $this->settings->getAttributeMode());
			$tmpl->assign('attribute', $this->settings->getAttribute());
			$tmpl->assign('serverHosts', $this->settings->getServerHosts());
		}
		return $tmpl;
	}
}
