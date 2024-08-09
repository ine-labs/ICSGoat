<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Guests;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserSession;
use OCP\Share\IShare;

class Hooks {

	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var Mail
	 */
	private $mail;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * Hooks constructor.
	 *
	 * @param ILogger $logger
	 * @param IUserSession $userSession
	 * @param Mail $mail
	 * @param IConfig $config
	 */
	public function __construct(
		ILogger $logger,
		IUserSession $userSession,
		Mail $mail,
		IConfig $config
	) {
		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->mail = $mail;
		$this->config = $config;
	}

	public function handlePostShare(IShare $share) {
		$itemType = $share->getNodeType();
		if ($itemType !== 'file'
			&& $itemType !== 'folder'
		) {
			$this->logger->debug(
				"ignoring share for itemType '$itemType'",
				['app' => 'guests']
			);
			return;
		}

		$shareWith = $share->getSharedWith();
		$isGuest = $this->config->getUserValue(
			$shareWith,
			'owncloud',
			'isGuest',
			false
		);

		if (!$isGuest) {
			$this->logger->debug(
				"ignoring user '$shareWith', not a guest",
				['app' => 'guests']
			);

			return;
		}

		$user = $this->userSession->getUser();
		if (!$user) {
			throw new \Exception(
				'post_share hook triggered without user in session'
			);
		}

		$this->logger->debug(
			"checking if '$shareWith' has a password",
			['app' => 'guests']
		);

		$registerToken = $this->config->getUserValue(
			$shareWith,
			'guests',
			'registerToken',
			null
		);

		try {
			if ($registerToken) {
				$uid = $user->getUID();
				// send invitation
				$this->mail->sendGuestInviteMail(
					$share,
					$uid,
					$registerToken
				);
			}
		} catch (DoesNotExistException $ex) {
			$this->logger->error(
				"'$shareWith' does not exist",
				['app' => 'guests']
			);
		}
	}
}
