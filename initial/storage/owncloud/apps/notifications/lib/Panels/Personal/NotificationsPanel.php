<?php
/**
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
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

namespace OCA\Notifications\Panels\Personal;

use OCP\Settings\ISettings;
use OCP\Template;
use OCP\IUserSession;
use OCP\IL10N;
use OCA\Notifications\Configuration\OptionsStorage;

class NotificationsPanel implements ISettings {
	/** @var OptionsStorage */
	private $optionsStorage;
	/** @var IUserSession */
	private $userSession;
	/** @var IL10N */
	private $l10n;

	public function __construct(OptionsStorage $optionsStorage, IUserSession $userSession, IL10N $l10n) {
		$this->optionsStorage = $optionsStorage;
		$this->userSession = $userSession;
		$this->l10n = $l10n;
	}
	public function getPanel() {
		$userObject = $this->userSession->getUser();
		if ($userObject !== null) {
			$optionList = $this->optionsStorage->getOptions($userObject->getUID());
			$emailSendingOption = $optionList['email_sending_option'];
			$possibleOptions = [
				'never' => [
					'visibleText' => (string)$this->l10n->t('Do not notify via mail'),
					'selected' => false,
				],
				'action' => [
					'visibleText' => (string)$this->l10n->t('Notify only when an action is required'),
					'selected' => false,
				],
				'always' => [
					'visibleText' => (string)$this->l10n->t('Notify about all events'),
					'selected' => false,
				],
			];

			if (!isset($possibleOptions[$emailSendingOption])) {
				$possibleOptions = \array_merge([
					$emailSendingOption => [
						'visibleText' => (string)$this->l10n->t('Choose an option'),
						'selected' => true,
					],
				], $possibleOptions);
			}
			$possibleOptions[$emailSendingOption]['selected'] = true;
		} else {
			$possibleOptions = [];
		}

		$tmpl = new Template('notifications', 'panels/personal/notifications');
		$tmpl->assign('validUserObject', $userObject !== null);
		$tmpl->assign('possibleOptions', $possibleOptions);
		return $tmpl;
	}

	public function getPriority() {
		return 90;
	}

	public function getSectionID() {
		return 'general';
	}
}
