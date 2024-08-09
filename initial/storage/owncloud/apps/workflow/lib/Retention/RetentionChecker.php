<?php
/**
 * ownCloud Workflow
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 * @copyright 2019 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Retention;

use OCA\Workflow\Entity\PropertyMapper;
use OCP\Files\Node;
use OCP\IConfig;
use OCP\ILogger;

class RetentionChecker {
	public const PROPERTY_NAME_UPLOAD_TIME = '{http://owncloud.org/ns}upload-time';

	/** @var PropertyMapper */
	private $propertyMapper;

	/** @var IConfig */
	private $config;

	/** @var ILogger */
	private $logger;

	public function __construct(PropertyMapper $propertyMapper, IConfig $config, ILogger $logger) {
		$this->propertyMapper = $propertyMapper;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * Check if the node reached the retention expiration
	 * @param Node $node
	 * @param int $retentionTimestamp - timestamp of the retention expiration
	 * @return bool
	 */
	public function isRetentionOver(Node $node, $retentionTimestamp) {
		$value = $this->getRetentionPropertyValue($node);
		return $value > 0 && $value < $retentionTimestamp;
	}

	/**
	 * @param Node $node
	 * @return int
	 */
	public function getRetentionPropertyValue(Node $node) {
		try {
			if ($this->config->getSystemValue('workflow.tag-based-retention.use-upload-time', false) === true) {
				$propertyValue = $this->getUploadTime($node->getId());
			} else {
				$propertyValue = $node->getMTime();
			}
		} catch (\Exception $ex) {
			$this->logger->logException($ex, ['message' => "Exception while getting upload time for {$node->getId()}"]);
			$propertyValue = -1;
		}
		return $propertyValue;
	}

	/**
	 * @param int $id
	 *
	 * @return int upload time or -1 when there is no valid upload time
	 *
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 */
	protected function getUploadTime($id) {
		/** @var \OCA\Workflow\Entity\Property|bool $prop */
		$prop = $this->propertyMapper->findByIdAndPropertyName($id, self::PROPERTY_NAME_UPLOAD_TIME);
		if ($prop !== false) {
			$dateTime = \DateTime::createFromFormat(\DateTime::ATOM, $prop->getPropertyvalue());
			if ($dateTime === false) {
				$this->logger->error("Invalid datetime format for file id {$id}: {$prop->getPropertyvalue()}");
				return -1;
			}
			return $dateTime->getTimestamp();
		}
		$this->logger->warning("No upload time stored for file id {$id}");
		return -1;
	}
}
