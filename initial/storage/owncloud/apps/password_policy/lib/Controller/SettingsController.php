<?php
/**
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

namespace OCA\PasswordPolicy\Controller;

use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Settings\ISettings;
use OCP\Template;
use OCA\PasswordPolicy\UserNotificationConfigHandler;

class SettingsController extends Controller implements ISettings {

	/** @var IConfig  */
	protected $config;

	public const DEFAULTS = [
		'spv_min_chars_checked' => false,
		'spv_min_chars_value' => 8,
		'spv_lowercase_checked' => false,
		'spv_lowercase_value' => 1,
		'spv_uppercase_checked' => false,
		'spv_uppercase_value' => 1,
		'spv_numbers_checked' => false,
		'spv_numbers_value' => 1,
		'spv_special_chars_checked' => false,
		'spv_special_chars_value' => 1,
		'spv_def_special_chars_checked' => false,
		'spv_def_special_chars_value' => '#!',
		'spv_password_history_checked' => false,
		'spv_password_history_value' => 3,
		'spv_user_password_expiration_checked' => false,
		'spv_user_password_expiration_value' => 90,
		'spv_user_password_expiration_notification_checked' => false,
		'spv_user_password_expiration_notification_value' => UserNotificationConfigHandler::DEFAULT_EXPIRATION_FOR_NORMAL_NOTIFICATION,
		'spv_user_password_force_change_on_first_login_checked' => false,
		'spv_expiration_password_checked' => false,
		'spv_expiration_password_value' => 7,
		'spv_expiration_nopassword_checked' => false,
		'spv_expiration_nopassword_value' => 7,
	];

	/**
	 * functions to convert values between what is shown and what is stored
	 * these functions must be defined in this class, they're per config key
	 */
	public const CONVERSIONS = [
		'spv_user_password_expiration_notification_value' => [
			'in' => 'daysToSeconds',
			'out' => 'secondsToDays',
		],
	];

	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
	}

	/**
	 *
	 */
	public function updatePolicy() {
		foreach (self::DEFAULTS as $key => $default) {
			if ($this->request->getParam($key) !== null) {
				if ($key !== 'spv_def_special_chars_value' && \substr($key, -6) === '_value') {
					$value = \min(\max(0, (int)$this->request->getParam($key)), 255);
					if (\array_key_exists($key, self::CONVERSIONS)
						&& \array_key_exists('in', self::CONVERSIONS[$key])
					) {
						$convertFuncName = self::CONVERSIONS[$key]['in'];
						$value = $this->$convertFuncName($value);
					}
					$this->config->setAppValue('password_policy', $key, $value);
				} else {
					$this->config->setAppValue('password_policy', $key, $this->request->getParam($key));
				}
			} else {
				$this->config->setAppValue('password_policy', $key, $default);
			}
		}
	}

	public function getSectionID() {
		return 'security';
	}

	public function getPriority() {
		return 10;
	}

	public function getPanel() {
		$template = new Template('password_policy', 'admin');
		foreach (self::DEFAULTS as $key => $default) {
			$value = $this->config->getAppValue('password_policy', $key, $default);
			if (\array_key_exists($key, self::CONVERSIONS)
				&& \array_key_exists('out', self::CONVERSIONS[$key])
			) {
				$convertFuncName = self::CONVERSIONS[$key]['out'];
				$value = $this->$convertFuncName($value);
			}
			$template->assign($key, $value);
		}
		return $template;
	}

	/**
	 * Convert the days to seconds
	 * @param int $days
	 * @return int the number of seconds
	 */
	private function daysToSeconds($days) {
		return $days * 24 * 60 * 60;
	}

	/**
	 * Convert seconds to days. The value will always be rounded up,
	 * so 1 second will be converted to 1 day
	 * @param int $seconds the number of seconds to be converted
	 * @return int the number of days in those seconds, rounded up
	 */
	private function secondsToDays($seconds) {
		$floatDays = $seconds / (24 * 60 * 60);
		return \intval(\ceil($floatDays));
	}
}
