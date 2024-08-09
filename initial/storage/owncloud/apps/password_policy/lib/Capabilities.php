<?php
/**
 * @author Jan Ackermann <jackermann@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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

namespace OCA\PasswordPolicy;

use OCP\Capabilities\ICapability;

/**
 * Class Capabilities
 *
 * @package OCA\PasswordPolicy
 */
class Capabilities implements ICapability {

	/** @var ConfigProvider */
	private $configProvider;

	/**
	 * Capabilities constructor.
	 *
	 * @param ConfigProvider $configProvider
	 */
	public function __construct(ConfigProvider $configProvider) {
		$this->configProvider = $configProvider;
	}

	/**
	 * Return this classes capabilities
	 *
	 * array['password_policy']              		array Defines the capabilities
	 *          [password_requirements]      		array Defines the password requirements
	 *          	['minimum_characters']			integer (optional) Defines the minimum password length
	 *              ['configuration']         		array Defines the password requirements base configuration (for e.g password managers)
	 *              	['lower_case']       		array Defines the password requirements lower case configuration
	 *              		['generate_from']       array Defines the possible password parameters
	 *              			['characters']		string Defines the allowed characters (default abcdefghijklmnopqrstuvwxyz)
	 *              		['minimum']       		integer (optional) Defines the minimum amount of lower case characters
	 *              	['upper_case']       		array Defines the password requirements upper case configuration
	 *              		['generate_from']       array Defines the possible password parameters
	 *	            			['characters']      string Defines the allowed characters (default ABCDEFGHIJKLMNOPQRSTUVWXYZ)
	 *              		['minimum']       		integer (optional) Defines the minimum amount of upper case characters
	 *              	['numbers']       			array Defines the password requirements numbers configuration
	 *              		['generate_from']       array Defines the possible password parameters
	 *          				['characters']      string Defines the allowed characters (default 1234567890)
	 *              		['minimum']       		integer (optional) Defines the minimum amount of numbers
	 *              	['special_characters']      array Defines the password requirements special characters configuration
	 *              		['generate_from']       array Defines the possible password parameters
	 *           				['characters']      string (optional) Defines the allowed characters
	 * 							['any]				boolean (optional) Defines whether all special characters are allowed (default true)
	 *              		['minimum']       		integer (optional) Defines the minimum amount of special characters
	 *
	 * @return array (see above)
	 */
	public function getCapabilities() {
		$generalPasswordRequirements = [
			'configuration' => [
				'lower_case' => [
					"generate_from" => ["characters" => "abcdefghijklmnopqrstuvwxyz"],
				],
				'upper_case' => [
					"generate_from" => ["characters" => "ABCDEFGHIJKLMNOPQRSTUVWXYZ"],
				],
				'numbers' => [
					"generate_from" => ["characters" => "1234567890"],
				],
				'special_characters' => [
					"generate_from" => []
				]],
		];

		if (($spv_min_chars = $this->configProvider->get('spv_min_chars')) !== null) {
			$generalPasswordRequirements['minimum_characters'] =
				\intval($spv_min_chars);
		}

		if (($spv_lowercase = $this->configProvider->get('spv_lowercase')) !== null) {
			$generalPasswordRequirements['configuration']['lower_case']['minimum'] =
				\intval($spv_lowercase);
		}

		if (($spv_uppercase = $this->configProvider->get('spv_uppercase')) !== null) {
			$generalPasswordRequirements['configuration']['upper_case']['minimum'] =
				\intval($spv_uppercase);
		}

		if (($spv_numbers = $this->configProvider->get('spv_numbers')) !== null) {
			$generalPasswordRequirements['configuration']['numbers']['minimum'] =
				\intval($spv_numbers);
		}

		if (($spv_special_chars = $this->configProvider->get('spv_special_chars')) !== null) {
			$generalPasswordRequirements['configuration']['special_characters']['minimum'] =
				\intval($spv_special_chars);
		}

		if (($spv_def_special_chars = $this->configProvider->get('spv_def_special_chars')) !== null) {
			$generalPasswordRequirements['configuration']['special_characters']['generate_from']['characters'] =
				$spv_def_special_chars;
		} else {
			$generalPasswordRequirements['configuration']['special_characters']['generate_from']['any'] = true;
		}

		return [
			'password_policy' =>
				['password_requirements' => $generalPasswordRequirements],
		];
	}
}
