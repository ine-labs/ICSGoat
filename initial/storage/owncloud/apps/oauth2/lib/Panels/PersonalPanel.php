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
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Template;

class PersonalPanel implements ISettings {

	/**
	 * @var \OCA\OAuth2\Db\ClientMapper
	 */
	protected $clientMapper;
	/**
	 * @var IUserSession
	 */
	protected $userSession;

	/**
	 * @var IURLGenerator
	 */
	protected $urlGenerator;

	public function __construct(
		ClientMapper $clientMapper,
		IUserSession $userSession,
		IURLGenerator $urlGenerator
	) {
		$this->clientMapper = $clientMapper;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
	}

	public function getSectionID() {
		return 'security';
	}

	/**
	 * @return Template
	 */
	public function getPanel() {
		$userId = $this->userSession->getUser()->getUID();
		$t = new Template('oauth2', 'settings-personal');
		$t->assign('clients', $this->clientMapper->findByUser($userId));
		$t->assign('user_id', $userId);
		/** @phan-suppress-next-line PhanTypeMismatchArgument */
		$t->assign('urlGenerator', $this->urlGenerator);
		return $t;
	}

	public function getPriority() {
		return 20;
	}
}
