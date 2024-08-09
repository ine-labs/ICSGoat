<?php
/**
 * @author Jan Ackermann <jackermann@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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

class ConfigProvider {

	/** @var IConfig */
	private $config;

	/**
	 * Capabilities constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Extracts the password requirements from app config
	 *
	 * @return array
	 */
	public function getActivePasswordRequirements(): array {
		$configKeys = [
			'spv_min_chars',
			'spv_lowercase',
			'spv_uppercase',
			'spv_numbers',
			'spv_special_chars',
			'spv_def_special_chars'
		];

		$passwordRequirements = [];

		foreach ($configKeys as $configKey) {
			if ($this->config->getAppValue('password_policy', $configKey.'_checked', null) === 'on') {
				$passwordRequirements[$configKey.'_value'] = $this->config->getAppValue('password_policy', $configKey.'_value', null);
			}
		}

		return $passwordRequirements;
	}

	/**
	 * Extracts the password requirements from app config
	 *
	 * @param string $configKey for example spv_min_chars without _key or _value
	 *
	 * @return null|string
	 */
	public function get(string $configKey) {
		if ($this->config->getAppValue('password_policy', $configKey."_checked", null) === 'on') {
			return $this->config->getAppValue('password_policy', $configKey."_value", null);
		}

		return null;
	}
}
