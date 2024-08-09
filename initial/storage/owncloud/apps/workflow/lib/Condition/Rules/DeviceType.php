<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2015 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Condition\Rules;

use OCA\Workflow\Condition\Context;
use OCA\Workflow\Condition\Operators;

class DeviceType extends BaseRule {

	/** @var array */
	protected $defaultClients = [
		Context::SYNC_CLIENT_ANDROID,
		Context::SYNC_CLIENT_IOS,
		Context::SYNC_CLIENT_DESKTOP,
	];

	/** @var array */
	protected $brandedClients = [
		Context::SYNC_CLIENT_ANDROID_BRANDED,
		Context::SYNC_CLIENT_IOS_BRANDED,
		Context::SYNC_CLIENT_DESKTOP_BRANDED,
	];

	/**
	 * @param mixed $ruleValue
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the value is not allowed
	 */
	public function validateRuleValue($ruleValue, $ruleId) {
		if (!\is_string($ruleValue)) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}

		$validValues = $this->getValidValues();
		if (isset($validValues[$ruleValue])) {
			return;
		}

		throw new \OutOfBoundsException('The rule value "' . $ruleValue . '" is not valid for rule "' . $ruleId . '"');
	}

	/**
	 * Can return an array to fill a select box in the UI
	 *
	 * @return array|null
	 */
	public function getValidValues() {
		$devices = [
			Context::SYNC_CLIENT_ANDROID	=> 'Android Client',
			Context::SYNC_CLIENT_IOS		=> 'iOS Client',
			Context::SYNC_CLIENT_DESKTOP	=> 'Desktop Client',
		];

		if ($this->hasBrandedClient()) {
			if ($this->hasBrandedClient([Context::SYNC_CLIENT_ANDROID_BRANDED])) {
				$devices[Context::SYNC_CLIENT_ANDROID_BRANDED] = 'Android Client (Branded)';
			}
			if ($this->hasBrandedClient([Context::SYNC_CLIENT_IOS_BRANDED])) {
				$devices[Context::SYNC_CLIENT_IOS_BRANDED] = 'iOS Client (Branded)';
			}
			if ($this->hasBrandedClient([Context::SYNC_CLIENT_DESKTOP_BRANDED])) {
				$devices[Context::SYNC_CLIENT_DESKTOP_BRANDED] = 'Desktop Client (Branded)';
			}

			$devices[Context::SYNC_CLIENT_BRANDED] = 'All branded clients';
			$devices[Context::SYNC_CLIENT_NON_BRANDED] = 'All non-branded clients';
		}

		$devices[Context::SYNC_CLIENT_OTHER] = 'Others (Browsers, etc.)';

		return $devices;
	}

	protected function hasBrandedClient($brandedClients = []) {
		if (empty($brandedClients)) {
			$brandedClients = $this->brandedClients;
		}

		$clients = $this->context->getBrandedClients();

		foreach ($clients as $userAgent => $client) {
			if (\in_array($client, $brandedClients)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return an array with the allowed operators
	 *
	 * @return string[]
	 */
	protected function getValidOperators() {
		return [Operators::OPERATOR_EQUALS, Operators::OPERATOR_NOT_EQUALS];
	}

	/**
	 * @param string $operator
	 * @param mixed $ruleValue
	 * @return boolean True if the check passes, false if the check fails
	 */
	public function doCheck($operator, $ruleValue) {
		$client = $this->context->getClientDevice();

		if ($ruleValue === Context::SYNC_CLIENT_BRANDED) {
			$ruleValue = $this->brandedClients;
			$operator = ($operator === Operators::OPERATOR_EQUALS) ? Operators::OPERATOR_IN : Operators::OPERATOR_NOT_IN;
		} elseif ($ruleValue === Context::SYNC_CLIENT_NON_BRANDED) {
			$ruleValue = $this->defaultClients;
			$operator = ($operator === Operators::OPERATOR_EQUALS) ? Operators::OPERATOR_IN : Operators::OPERATOR_NOT_IN;
		}

		return $this->operators->assert($client, $operator, $ruleValue);
	}
}
