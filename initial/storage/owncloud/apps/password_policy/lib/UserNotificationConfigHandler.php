<?php
/**
 *
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
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

namespace OCA\PasswordPolicy;

use OCP\IConfig;
use OCA\PasswordPolicy\Db\OldPassword;

class UserNotificationConfigHandler {
	public const DEFAULT_EXPIRATION_FOR_NORMAL_NOTIFICATION = 30 * 24 * 60 * 60;  // 30 days

	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Return the number of seconds until the passwords should expire or
	 * null if it isn't set (or disabled) or has a non parseable value
	 * @return int|null seconds
	 */
	public function getExpirationTime() {
		$isChecked = $this->config->getAppValue(
			'password_policy',
			'spv_user_password_expiration_checked',
			false
		);
		if (!\filter_var($isChecked, FILTER_VALIDATE_BOOLEAN)) {
			return null;
		}

		$expirationTime = $this->config->getAppValue(
			'password_policy',
			'spv_user_password_expiration_value',
			null
		);
		if ($expirationTime === null || !\is_numeric($expirationTime) || $expirationTime < 0) {
			return null;  // passwords don't expire or have weird value
		}
		// the expiration time is currently stored in days, so we need to convert
		// it to seconds.
		return \intval($expirationTime) * 24 * 60 * 60;
	}

	/**
	 * Return the number of seconds until a user should receive a notification
	 * that their password is about to expire. This _should_ be less than the value
	 * returned by the getExpirationTime function (you'll need to verify it outside)
	 * It will return null if the value isn't set (or disabled) or it has a
	 * non-parseable value
	 * @return int|null seconds
	 */
	public function getExpirationTimeForNormalNotification() {
		$isChecked = $this->config->getAppValue(
			'password_policy',
			'spv_user_password_expiration_notification_checked',
			false
		);
		if (!\filter_var($isChecked, FILTER_VALIDATE_BOOLEAN)) {
			return null;
		}

		$expirationTime = $this->config->getAppValue(
			'password_policy',
			'spv_user_password_expiration_notification_value',
			self::DEFAULT_EXPIRATION_FOR_NORMAL_NOTIFICATION
		);
		if ($expirationTime === null || !\is_numeric($expirationTime) || $expirationTime < 0) {
			return null;  // passwords don't expire or have weird value
		}
		return \intval($expirationTime);
	}

	/**
	 * Mark that a "password about to expire" notification has been sent.
	 * Note that we're using the id of the passInfo as marker, but this might change
	 * @param OldPassword $passInfo the information about the password. It has
	 * to include the userid owning the password and an id for the password
	 */
	public function markAboutToExpireNotificationSentFor(OldPassword $passInfo) {
		$this->config->setUserValue(
			$passInfo->getUid(),
			'password_policy',
			'aboutToExpireSent',
			(string)$passInfo->getId()
		);
	}

	/**
	 * Mark that a "password expired" notification has been sent.
	 * Note that we're using the id of the passInfo as marker, but this might change
	 * @param OldPassword $passInfo the information about the password. It has
	 * to include the userid owning the password and an id for the password
	 */
	public function markExpiredNotificationSentFor(OldPassword $passInfo) {
		$this->config->setUserValue(
			$passInfo->getUid(),
			'password_policy',
			'expiredSent',
			(string)$passInfo->getId()
		);
	}

	/**
	 * Get the mark set with markAboutToExpireNotificationSentFor for the specified user
	 * @param string $userid the user id to get the mark from
	 * @return string|null the mark or null if there is no mark
	 */
	public function getMarkAboutToExpireNotificationSentFor($userid) {
		return $this->config->getUserValue($userid, 'password_policy', 'aboutToExpireSent', null);
	}

	/**
	 * Get the mark set with markExpiredNotificationSentFor for the specified user
	 * @param string $userid the user id to get the mark from
	 * @return string|null the mark or null if there is no mark
	 */
	public function getMarkExpiredNotificationSentFor($userid) {
		return $this->config->getUserValue($userid, 'password_policy', 'expiredSent', null);
	}

	/**
	 * Check if a "password about to expire" notification has been sent for that
	 * password
	 * @param OldPassword $passInfo the password information to be checked
	 * @return bool true if the notification has been sent already, false otherwise.
	 * Note that we'll check only with the last password id sent
	 */
	public function isSentAboutToExpireNotification(OldPassword $passInfo) {
		$storedId = $this->config->getUserValue($passInfo->getUid(), 'password_policy', 'aboutToExpireSent', null);
		if ($storedId === null) {
			return false;  // notification not sent
		} elseif (\intval($storedId) !== $passInfo->getId()) {
			// if the password id doesn't match the one stored, the notification hasn't been sent
			return false;
		}
		return true;
	}

	/**
	 * Check if a "password expired" notification has been sent for that
	 * password
	 * @param OldPassword $passInfo the password information to be checked
	 * @return bool true if the notification has been sent already, false otherwise.
	 * Note that we'll check only with the last password id sent
	 */
	public function isSentExpiredNotification(OldPassword $passInfo) {
		$storedId = $this->config->getUserValue($passInfo->getUid(), 'password_policy', 'expiredSent', null);
		if ($storedId === null) {
			return false;  // notification not sent
		} elseif (\intval($storedId) !== $passInfo->getId()) {
			// if the password id doesn't match the one stored, the notification hasn't been sent
			return false;
		}
		return true;
	}

	/**
	 * Reset the marks created with markAboutToExpireNotificationSentFor and
	 * markExpiredNotificationSentFor functions. This function should be call
	 * once the password for the user has been changed
	 * @param string $uid the id if the user that has changed his password
	 */
	public function resetExpirationMarks($uid) {
		$targetKeys = [
			'aboutToExpireSent',
			'expiredSent',
		];
		foreach ($targetKeys as $targetKey) {
			$this->config->deleteUserValue($uid, 'password_policy', $targetKey);
		}
	}
}
