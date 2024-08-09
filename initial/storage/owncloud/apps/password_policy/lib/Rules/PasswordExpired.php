<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\PasswordPolicy\Rules;

use OCA\PasswordPolicy\Db\OldPassword;
use OCA\PasswordPolicy\Db\OldPasswordMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use OCP\ILogger;
use OCP\Security\IHasher;

class PasswordExpired extends Base {

	/** @var ILogger */
	protected $logger;

	/** @var OldPasswordMapper */
	protected $mapper;

	/** @var IHasher */
	protected $hasher;

	/** @var ITimeFactory */
	protected $timeFactory;

	/**
	 * @param IL10N $l10n
	 * @param ILogger $logger
	 * @param OldPasswordMapper $mapper
	 * @param IHasher $hasher
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(
		IL10N $l10n,
		ILogger $logger,
		OldPasswordMapper $mapper,
		IHasher $hasher,
		ITimeFactory $timeFactory
	) {
		parent::__construct($l10n);
		$this->logger = $logger;
		$this->mapper = $mapper;
		$this->hasher = $hasher;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param string $password
	 * @param int $days days until a password is considered expired
	 * @param string $uid
	 * @throws PolicyException
	 */
	public function verify($password, $days, $uid) {
		$latestPassword = $this->mapper->getLatestPassword($uid);
		if ($latestPassword === null) {
			$this->logger->debug(
				'Cannot determine the password\'s age, as no existing password for uid {uid} was found.',
				[
					'app' => __METHOD__,
					'uid' => $uid
				]
			);
			return;
		}

		if ($latestPassword->getPassword() !== OldPassword::EXPIRED && !$this->hasher->verify($password, $latestPassword->getPassword())) {
			$this->logger->warning(
				'Cannot determine the password\'s age, as the existing password {id} for uid {uid} does not match the new password.',
				[
					'app' => __METHOD__,
					'id' => $latestPassword->getId(),
					'uid' => $latestPassword->getUid()
				]
			);
			return;
		}

		/** @var int|null $changed */
		$changed = $latestPassword->getChangeTime();
		if ($changed === null) {
			$this->logger->warning(
				'Cannot determine the password\'s, as the existing password {id} for uid {uid} as it has no change time.',
				[
					'app'=>__METHOD__,
					'id' => $latestPassword->getId(),
					'uid' => $latestPassword->getUid()
				]
			);
			return;
		}

		$date = new \DateTime("@$changed");
		$date->add(new \DateInterval("P{$days}D"));
		$date->setTime(23, 59, 59); // include last day
		$expiresAt = $date->getTimestamp();

		$now = $this->timeFactory->getTime();

		if ($expiresAt < $now) {
			throw new PolicyException(
				$this->l10n->t('The password is older than %d days.', [$days])
			);
		}
		$this->logger->debug(
			'The existing password {id} for uid {uid} is still valid for {seconds} seconds.',
			[
				'app'=>__METHOD__,
				'id' => $latestPassword->getId(),
				'uid' => $latestPassword->getUid(),
				'seconds' => $expiresAt - $now
			]
		);
	}
}
