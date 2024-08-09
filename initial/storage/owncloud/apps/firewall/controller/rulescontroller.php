<?php
/**
 * ownCloud Firewall
 *
 * @author Clark Tomlinson <clark@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Firewall\Controller;

use OCA\Firewall\Config;
use OCA\Firewall\Firewall;
use OCA\Firewall\RuleFactory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class RulesController extends Controller {

	/** @var IUserSession */
	private $userSession;
	/** @var Config */
	private $config;
	/** @var Firewall */
	private $firewall;
	/** @var RuleFactory */
	private $ruleFactory;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param Firewall $firewall
	 * @param RuleFactory $ruleFactory
	 * @param Config $config
	 * @param IUserSession $userSession
	 */
	public function __construct($appName, IRequest $request, Firewall $firewall, RuleFactory $ruleFactory, Config $config, IUserSession $userSession) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->config = $config;
		$this->firewall = $firewall;
		$this->ruleFactory = $ruleFactory;
	}

	/**
	 * Saves rules to persistent storage
	 *
	 * @param $rules
	 * @return DataResponse
	 */
	public function save($rules) {
		try {
			$validateRules = $this->firewall->validateRules($rules);
			$this->config->setRules($validateRules);
			return new DataResponse([], Http::STATUS_OK);
		} catch (\OutOfBoundsException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_NOT_ACCEPTABLE);
		} catch (\Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Set our debugLevel
	 *
	 * @param $level
	 * @return DataResponse
	 */
	public function debug($level) {
		try {
			$this->config->setDebug($level);
			return new DataResponse([], Http::STATUS_OK);
		} catch (\Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Return rules and debug level for the ui to consume
	 *
	 * @return DataResponse
	 */
	public function getUIData() {
		$rules = $this->config->getRules();

		$validationError = false;
		try {
			$this->firewall->validateRules($rules);
		} catch (\OutOfBoundsException $e) {
			$validationError = $e->getMessage();
		}

		$ruleValues = [];
		foreach ($this->ruleFactory->getRules() as $id) {
			$rule = $this->ruleFactory->getRuleInstance($id);
			try {
				$validValues = $rule->getValidValues();
			} catch (\Exception $e) {
				$validationError = $e->getMessage();
			}
			if (\is_array($validValues)) {
				$ruleValues[$id] = $validValues;
			}
		}

		return new DataResponse([
			'rules'				=> $rules,
			'validationError'	=> $validationError,
			'debugLevel'		=> $this->config->getDebugLevel(),
			'ruleValues'		=> $ruleValues,
		], Http::STATUS_OK);
	}
}
