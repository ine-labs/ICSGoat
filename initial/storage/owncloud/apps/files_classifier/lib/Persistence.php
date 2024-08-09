<?php
/**
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license OCL
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
namespace OCA\FilesClassifier;

use OCA\FilesClassifier\Model\Rule;
use OCA\FilesClassifier\Model\RuleCollection;
use OCP\IConfig;

class Persistence {

	/** @var IConfig  */
	private $config;
	/** @var Serializer  */
	private $serializer;

	public function __construct(IConfig $config, Serializer $serializer) {
		$this->config = $config;
		$this->serializer = $serializer;
	}

	/**
	 * @param Rule[] $rules
	 */
	public function storeRules(array $rules) {
		$this->config->setAppValue('files_classifier', 'rules', $this->serializer->serialize($rules));
	}

	/**
	 * @return Rule[]
	 */
	public function loadRules() {
		$rawRules = $this->config->getAppValue('files_classifier', 'rules', '[]');
		return $this->serializer->deserialize($rawRules, Rule::class.'[]');
	}

	/**
	 * @return RuleCollection
	 */
	public function loadRulesIndexedByTagId() : RuleCollection {
		return new RuleCollection($this->loadRules());
	}
}
