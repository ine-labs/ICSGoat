<?php
/**
 * @author Project Seminar "sciebo@Learnweb" of the University of Muenster
 * @copyright Copyright (c) 2017, University of Muenster
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
 */

namespace OCA\OAuth2\Panels;

use OCA\OAuth2\Db\ClientMapper;
use OCP\Settings\ISettings;
use OCP\Template;

class AdminPanel implements ISettings {
	/**
	 * @var \OCA\OAuth2\Db\ClientMapper
	 */
	protected $clientMapper;

	public function __construct(ClientMapper $clientMapper) {
		$this->clientMapper = $clientMapper;
	}

	public function getSectionID() {
		return 'authentication';
	}

	/**
	 * @return Template
	 */
	public function getPanel() {
		$t = new Template('oauth2', 'settings-admin');
		$t->assign('clients', $this->clientMapper->findAll());
		return $t;
	}

	public function getPriority() {
		return 20;
	}
}
