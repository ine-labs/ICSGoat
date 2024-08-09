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

namespace OCA\PasswordPolicy\Command;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCA\PasswordPolicy\Db\OldPasswordMapper;
use OCA\PasswordPolicy\Db\OldPassword;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\AppFramework\Utility\ITimeFactory;

class ExpirePassword extends Command {
	/**
	 * Catchall for general errors
	 * @see http://tldp.org/LDP/abs/html/exitcodes.html#FTN.AEN23647
	 */
	public const EX_GENERAL_ERROR = 1;

	/**
	 * return EX_NOUSER from /usr/include/sysexits.h
	 * @see http://tldp.org/LDP/abs/html/exitcodes.html#FTN.AEN23647
	 */
	public const EX_NOUSER = 67;

	/** @var \OCP\IConfig */
	private $config;

	/** @var \OCP\IUserManager */
	protected $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var OldPasswordMapper */
	private $mapper;

	/**
	 * ExpirePassword constructor.
	 *
	 * @param IConfig $config
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param ITimeFactory $timeFactory
	 * @param OldPasswordMapper $mapper
	 */
	public function __construct(
		IConfig $config,
		IUserManager $userManager,
		IGroupManager $groupManager,
		ITimeFactory $timeFactory,
		OldPasswordMapper $mapper
	) {
		parent::__construct();
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->timeFactory = $timeFactory;
		$this->mapper = $mapper;
	}

	protected function configure() {
		$this
			->setName('user:expire-password')
			->setDescription('Expire a user\'s password')
			->addArgument(
				'expiredate',
				InputArgument::OPTIONAL,
				'The date and time when a password expires, e.g. "2019-01-01 14:00:00 CET".',
				'-1 days'		// base.php sets timezone to utc, so
										// make sure date is in the past
			)
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'Will add password expiry to all known users. uid and group option will be discarded if all option is provided by user.'
			)
			->addOption(
				'uid',
				'u',
				InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				'The user\'s uid is used. This option can be used as --uid "Alice" --uid "Bob"'
			)
			->addOption(
				'group',
				'g',
				InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				'Add password expiry to user(s) under group(s). This option can be used as --group "foo" --group "bar" to add expiry passwords for users in group foo and bar. If uid option (eg: --uid "user1") is passed with group, then uid will also be processed.'
			);
	}

	/**
	 * sets a user value as a flag in the user config that will be checked by
	 * the AccountModule on login.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws \OCP\PreConditionNotMetException
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$groups = $input->getOption('group');
		$allUsers = $input->getOption('all');
		$uids = $input->getOption('uid');

		$expireDate = new \DateTime();
		$oldDate = new \DateTime();
		$timeZone = new \DateTimeZone('UTC');
		$inputExpireDate = $input->getArgument('expiredate');

		if ($this->config->getAppValue('password_policy', 'spv_user_password_expiration_checked', false) !== 'on') {
			$output->writeln("<error>Cannot use this command because no expiration rule was configured</error>");
			return 1;
		}

		//This array will hold the uids of users process when group option is passed
		$users = [];
		if ($allUsers !== false) {
			$this->userManager->callForAllUsers(function (IUser $user) use ($expireDate, $oldDate, $timeZone, $inputExpireDate, $output, &$users) {
				if (isset($users[$user->getUID()]) === false) {
					if ($user->canChangePassword()) {
						$calculatedExpireDate = $this->setPasswordExpiry($expireDate, $oldDate, $timeZone, $inputExpireDate, $user);
						$output->writeln('The password for ' . $user->getUID() . ' is set to expire on ' . $calculatedExpireDate->format('Y-m-d H:i:s T') . '.');
						$users[$user->getUID()] = true;
					}
				}
			});
		} else {
			$numGroupsMissing = 0;
			$numUidsMissing = 0;

			if (\count($groups) > 0) {
				foreach ($groups as $group) {
					if ($this->groupManager->groupExists($group) === true) {
						foreach ($this->groupManager->findUsersInGroup($group) as $user) {
							if (isset($users[$user->getUID()]) === false) {
								if ($user->canChangePassword()) {
									$calculatedExpireDate = $this->setPasswordExpiry($expireDate, $oldDate, $timeZone, $inputExpireDate, $user);
									$output->writeln('The password for ' . $user->getUID() . ' is set to expire on ' . $calculatedExpireDate->format('Y-m-d H:i:s T') . '.');
									$users[$user->getUID()] = true;
								}
							}
						}
					} else {
						$output->writeln("<error>Unknown group: $group</error>");
						$numGroupsMissing = $numGroupsMissing + 1;
					}
				}
			}

			if (\count($uids) > 0) {
				foreach ($uids as $uid) {
					/** @var \OCP\IUser|null $user */
					$user = $this->userManager->get($uid);

					if ($user === null) {
						$output->writeln("<error>Unknown user: $uid</error>");
						$numUidsMissing = $numUidsMissing + 1;
					} else {
						if (isset($users[$user->getUID()])) {
							continue;
						}
						if (!$user->canChangePassword()) {
							$output->writeln("<error>The user's backend doesn't support password changes. The password cannot be expired for user: $uid</error>");
							return 3;
						} else {
							$calculatedExpireDate = $this->setPasswordExpiry($expireDate, $oldDate, $timeZone, $inputExpireDate, $user);
							$output->writeln('The password for ' . $user->getUID() . ' is set to expire on ' . $calculatedExpireDate->format('Y-m-d H:i:s T') . '.');
						}
					}
				}
			}

			if (!(\count($groups) > 0) && !(\count($uids) > 0)) {
				$output->writeln("<error>Invalid argument given.</error>");
				return 1;
			}

			if (($numGroupsMissing > 0) || ($numUidsMissing > 0)) {
				if ($numGroupsMissing > 0) {
					if ($numGroupsMissing > 1) {
						$text = "$numGroupsMissing groups were not known";
					} else {
						$text = "1 group was not known";
					}
					$output->writeln("<error>$text</error>");
				}
				if ($numUidsMissing > 0) {
					if ($numUidsMissing > 1) {
						$text = "$numUidsMissing users were not known";
					} else {
						$text = "1 user was not known";
					}
					$output->writeln("<error>$text</error>");
				}
				return 2;
			}
		}
		return 0;
	}

	/**
	 * @param \DateTime $expireDate
	 * @param \DateTime $oldDate
	 * @param \DateTimeZone $timeZone
	 * @param mixed $inputExpireDate
	 * @param IUser $user
	 */
	protected function setPasswordExpiry(\DateTime $expireDate, \DateTime $oldDate, \DateTimeZone $timeZone, $inputExpireDate, IUser $user) {
		$expireDate->setTimezone($timeZone);
		$expireDate->setTimestamp($this->timeFactory->getTime());
		$expireDate->modify($inputExpireDate);

		$oldDate->setTimezone($timeZone);
		$oldDate->setTimestamp($this->timeFactory->getTime());
		$oldDate->modify($inputExpireDate);

		if ($this->config->getAppValue('password_policy', 'spv_user_password_expiration_checked', false) === 'on') {
			$delta = $this->config->getAppValue('password_policy', 'spv_user_password_expiration_value', 90);
			$oldDate->modify("-$delta days");
		}

		$this->config->deleteUserValue(
			$user->getUID(),
			'password_policy',
			'forcePasswordChange'
		);

		// add a dummy password in the user_password_history so the cron job
		// can notify about the expiration of the password.
		$oldPassword = new OldPassword();
		$oldPassword->setUid($user->getUID());
		$oldPassword->setPassword(OldPassword::EXPIRED);
		$oldPassword->setChangeTime($oldDate->getTimestamp());
		$this->mapper->insert($oldPassword);

		return $expireDate;
	}
}
