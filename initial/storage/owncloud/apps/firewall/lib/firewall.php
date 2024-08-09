<?php
/**
 * ownCloud Firewall
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Firewall;

use OCP\Files\ForbiddenException;
use OCP\L10N\IFactory;

class Firewall {

	/** @var array */
	protected $offender;

	/** @var RuleFactory */
	protected $ruleFactory;

	/** @var Context */
	protected $context;

	/** @var array */
	protected $rules;

	/** @var Logger */
	protected $logger;

	/** @var IFactory */
	protected $languageFactory;
	
	/**
	 *
	 * @var \Sabre\DAV\Server
	 */
	protected $sabreServer = null;

	/**
	 * Constructor to build a set of rules to evaluate against
	 *
	 * @param RuleFactory $ruleFactory
	 * @param Context $context
	 * @param Logger $logger
	 * @param IFactory $languageFactory
	 */
	public function __construct(
		RuleFactory $ruleFactory,
		Context $context,
		Logger $logger,
		IFactory $languageFactory
	) {
		$this->ruleFactory = $ruleFactory;
		$this->context = $context;
		$this->logger = $logger;
		$this->languageFactory = $languageFactory;
	}

	public function setSabreServer(\Sabre\DAV\Server $sabreServer) {
		$this->sabreServer = $sabreServer;
	}

	/**
	 * @param array $rules
	 */
	public function setRules(array $rules) {
		$this->rules = $rules;
		$this->offender = [];
	}

	/**
	 * Evaluate a condition rule to true or false based on the request criteria
	 *
	 * @param array $rule The condition rule to evaluate
	 * @return bool True if the criteria matches the rule condition, false if not
	 */
	protected function evalCondition(array $rule) {
		if (!isset($rule['id'])) {
			return false;
		}

		try {
			$ruleInstance = $this->ruleFactory->getRuleInstance($rule['id']);
		} catch (\InvalidArgumentException $e) {
			// Invalid rule, should log this
			return false;
		}
		return $ruleInstance->doCheck(
			$rule ['operator'],
			$rule ['value'],
			$this->sabreServer
		);
	}

	/**
	 * Validate whether config is a correct set of rules
	 *
	 * @param string $config JSON encoded set of rules
	 * @return string Returns a cleaned version of the rule set (removes empty rules)
	 * @throws \OutOfBoundsException when the rule set is invalid
	 */
	public function validateRules($config) {
		if (!\is_string($config)) {
			throw new \OutOfBoundsException('The firewall rule config is not a valid json array');
		}

		$config = \json_decode($config, true);

		if (!\is_array($config)) {
			throw new \OutOfBoundsException('The firewall rule config is not a valid json array');
		}

		foreach ($config as $i => $ruleSet) {
			if (empty($ruleSet)) {
				// This happens when you delete the last rule and then save
				unset($config[$i]);
				continue;
			}

			if (!\is_array($ruleSet) || \sizeof($ruleSet) !== 2 || !isset($ruleSet['name']) || !\is_string($ruleSet['name']) || !isset($ruleSet['rules']) || !\is_array($ruleSet['rules'])) {
				throw new \OutOfBoundsException('The firewall rule #' . $i . ' is invalid');
			}

			foreach ($ruleSet['rules'] as $j => $rule) {
				$this->validateRule($rule, $ruleSet['name'] . ' - Condition #' . $j);
			}
		}

		return \json_encode($config);
	}

	/**
	 * Validate whether the operator and compare value are valid for this rule
	 *
	 * @param array $rule The condition rule to evaluate
	 * @param string $ruleIdentifier
	 * @return bool
	 * @throws \OutOfBoundsException when the rule is invalid
	 */
	protected function validateRule($rule, $ruleIdentifier) {
		if (!isset($rule['id'])) {
			throw new \OutOfBoundsException('Missing firewall rule id in "' . $ruleIdentifier . '"');
		}
		if (isset($rule['rules']) || !isset($rule['operator']) || !\is_string($rule['operator']) || !isset($rule['value'])) {
			throw new \OutOfBoundsException('Invalid firewall rule "' . $ruleIdentifier . '"');
		}

		try {
			$ruleInstance = $this->ruleFactory->getRuleInstance($rule['id']);
		} catch (\InvalidArgumentException $e) {
			throw new \OutOfBoundsException($e->getMessage() . ' for rule "' . $ruleIdentifier . '"', $e->getCode(), $e);
		}

		$ruleInstance->validateOperator($rule['operator'], $rule['id'] . ' - ' . $ruleIdentifier);
		$ruleInstance->validateRuleValue($rule['value'], $rule['id'] . ' - ' . $ruleIdentifier);

		return true;
	}

	/**
	 * @param string $cacheKey
	 * @return string
	 */
	protected function getOffender($cacheKey) {
		return isset($this->offender[$cacheKey]) ? $this->offender[$cacheKey] : null;
	}

	/**
	 * Check the firewall for a set of file ids
	 *
	 * @param array $fileIds
	 * @throws ForbiddenException When the firewall blocks the request
	 */
	public function checkRulesForFiles(array $fileIds) {
		// NOTE: we need to always check with the full file path, otherwise
		// cases where parent1 holds tag1 and parent2 holds tag2, will not be
		// blocked correctly in case of rule "tag1 AND tag2"
		$fileIds = $this->context->setFileIds($fileIds);
		$cacheKey = \json_encode($fileIds);
		$result = $this->check($cacheKey);

		if (!$result) {
			$this->context->setFileIds(null);
			$this->logger->onBlock($this->getOffender($cacheKey));

			$l = $this->languageFactory->get('firewall');
			throw new ForbiddenException((string) $l->t('Access to this resource has been forbidden by a file firewall rule.'), true);
		} else {
			$this->logger->onPass();
		}

		$this->context->setFileIds(null);
	}

	/**
	 * Check if a request is allowed
	 *
	 * @param string $cacheKey
	 * @return bool True if the request should be accepted, false otherwise
	 */
	public function check($cacheKey) {
		// If our rules are empty user has not defined any thus allow the request
		if (empty($this->rules)) {
			$this->offender[$cacheKey] = false;
			return true;
		}

		if (isset($this->offender[$cacheKey])) {
			return $this->offender[$cacheKey] === false;
		}

		foreach ($this->rules as $key => $rule) {
			if (empty($rule['rules'])) {
				continue;
			}

			foreach ($rule['rules'] as $conditionNum => $condition) {
				if (!$this->evalCondition($condition)) {
					// The rule condition did not match, so we can skip the
					// remaining conditions of this rule and continue with the
					// next rule.
					continue 2;
				}
			}

			// All conditions matched, so the rule matches as well and the
			// request should be blocked.
			$this->offender[$cacheKey] = $rule['name'];
			return false;
		}

		$this->offender[$cacheKey] = false;
		return true;
	}
}
