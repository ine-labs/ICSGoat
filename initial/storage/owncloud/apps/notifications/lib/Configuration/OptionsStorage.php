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

namespace OCA\Notifications\Configuration;

use OCP\IConfig;

/**
 * Store the configuration options per user for the "notifications" app. The class will provide
 * a simple and clear interface to access and modify those options, as well as handle default
 * values consistenly.
 * The implementation currently uses the "preferences" DB table but might be changed to another
 * table if needed.
 */
class OptionsStorage {
	/** @var IConfig */
	private $config;

	/** @var array */
	private $validOptionValues = [
		'email_sending_option' => [
			'values' => ['never', 'action', 'always'],
			'default' => 'action',
		],
	];

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Get the supported options. It will return an array like the following:
	 * [ <option-key> => ['values' => [<s1>, <s2>, <s3>], 'default' => <s1> ]]
	 * The <s1>, <s2>, etc are supported values that can be placed in that key.
	 */
	public function getValidOptionValuesInfo() {
		return $this->validOptionValues;
	}

	/**
	 * Get the options for the user from the DB.
	 * @return array [optionKey => optionValue].
	 */
	public function getOptions($userid) {
		$data = [];
		foreach ($this->validOptionValues as $option => $optionValue) {
			$data[$option] = $this->config->getUserValue($userid, 'notifications', $option, $optionValue['default']);
		}
		return $data;
	}

	/**
	 * Set an option for the user
	 * @param string $userid the user we'll set the option for
	 * @param string $key the option key
	 * @param string $value the option value
	 * @return bool true if saved, false otherwise
	 */
	public function setOption($userid, $key, $value) {
		if (!$this->isOptionValid($key, $value)) {
			return false;
		}

		$this->config->setUserValue($userid, 'notifications', $key, $value);
		// assume the value is correctly saved
		return true;
	}

	/**
	 * Check if the option can be set
	 */
	public function isOptionValid($key, $value) {
		if (!isset($this->validOptionValues[$key])) {
			return false;
		}

		if (\in_array($value, $this->validOptionValues[$key]['values'], true)) {
			return true;
		}
		// we don't have any additional case for now, so return false. More cases could be added later
		return false;
	}

	/**
	 * Get the language of the user. NOTE: This is fetched from the user's preferences, and the value
	 * won't be set from here.
	 * @return string|null the language set for the user or null if it isn't set.
	 */
	public function getUserLanguage($userid) {
		return $this->config->getUserValue($userid, 'core', 'lang', null);
	}
}
