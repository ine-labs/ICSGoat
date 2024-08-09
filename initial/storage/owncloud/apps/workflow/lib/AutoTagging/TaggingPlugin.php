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

namespace OCA\Workflow\AutoTagging;

use OCA\Workflow\PublicAPI\Event\CollectTypesInterface;
use OCA\Workflow\PublicAPI\Event\FileActionInterface;
use OCA\Workflow\PublicAPI\Event\ValidateFlowInterface;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\ILogger;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;

class TaggingPlugin {

	/** @var ISystemTagManager */
	protected $systemTagManager;

	/** @var ISystemTagObjectMapper */
	protected $objectMapper;

	/** @var ILogger */
	protected $logger;

	/** @var IL10N */
	protected $l;

	/** @var IManager */
	protected $manager;

	/**
	 * TaggingPlugin constructor.
	 *
	 * @param ISystemTagManager $systemTagManager
	 * @param ISystemTagObjectMapper $objectMapper
	 * @param ILogger $logger
	 * @param IL10N $l
	 * @param IManager $manager
	 */
	public function __construct(ISystemTagManager $systemTagManager, ISystemTagObjectMapper $objectMapper, ILogger $logger, IL10N $l, IManager $manager) {
		$this->systemTagManager = $systemTagManager;
		$this->objectMapper = $objectMapper;
		$this->logger = $logger;
		$this->l = $l;
		$this->manager = $manager;
	}

	/**
	 * Make sure the tags exist and are unique
	 *
	 * @param ValidateFlowInterface $event
	 */
	public function validateFlow(ValidateFlowInterface $event) {
		$flow = $event->getFlow();
		if ($flow->getType() !== 'workflow_autotagging') {
			return;
		}

		$actions = $flow->getActions();

		if (!\is_array($actions) || !isset($actions['setTags']) || !\is_array($actions['setTags'])) {
			throw new \OutOfBoundsException((string) $this->l->t('No Autotagging tag given'), 3);
		}

		if (empty($actions['setTags'])) {
			throw new \OutOfBoundsException((string) $this->l->t('No Autotagging tag given'), 3);
		}

		$tags = \array_unique($actions['setTags']);

		try {
			$this->systemTagManager->getTagsByIds($tags);
		} catch (TagNotFoundException $e) {
			$notFoundTagsList = '"' . \implode('", "', $e->getMissingTags()) . '"';
			throw new \OutOfBoundsException((string) $this->l->t('The following Autotagging tags do not exist: %s', [$notFoundTagsList]), 3);
		} catch (\InvalidArgumentException $e) {
			throw new \OutOfBoundsException((string) $this->l->t('At least one of the Autotagging tags does not exist'), 3);
		}

		$event->setFlowActions([
			'setTags' => $tags,
		]);

		// No other plugin needs to take care of this flow.
		$event->stopPropagation();
	}

	/**
	 * Assign the tags to the file
	 *
	 * @param FileActionInterface $event
	 */
	public function fileAction(FileActionInterface $event) {
		$flow = $event->getFlow();
		if ($flow->getType() !== 'workflow_autotagging') {
			return;
		}

		$actions = $flow->getActions();
		$this->manager->setAgentAuthor(IEvent::AUTOMATION_AUTHOR);
		try {
			$this->objectMapper->assignTags((string)$event->getFileId(), 'files', $actions['setTags']);
		} catch (TagNotFoundException $e) {
			$this->logger->logException($e, ['app' => 'workflow']);
		} finally {
			$this->manager->restoreAgentAuthor();
		}

		// No other plugin needs to take care of this flow.
		$event->stopPropagation();
	}

	/**
	 * Register the type
	 *
	 * @param CollectTypesInterface $event
	 */
	public function collectTypes(CollectTypesInterface $event) {
		$event->addType('workflow_autotagging', $this->l->t('Add tags'));
	}
}
