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

namespace OCA\Workflow\Controller;

use OCA\Workflow\Retention\Manager;
use OCA\Workflow\Retention\Exception\TagAlreadyHasRetention;
use OCA\Workflow\Retention\Exception\TagHasNoRetention;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IL10N;
use OCP\IRequest;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagNotFoundException;

class RetentionController extends Controller {

	/** @var Manager */
	protected $manager;

	/** @var ISystemTagManager */
	protected $tagManager;

	/** @var IL10N */
	protected $l;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param Manager $manager
	 * @param ISystemTagManager $tagManager
	 * @param IL10N $l
	 */
	public function __construct($AppName, IRequest $request, Manager $manager, ISystemTagManager $tagManager, IL10N $l) {
		parent::__construct($AppName, $request);
		$this->manager = $manager;
		$this->tagManager = $tagManager;
		$this->l = $l;
	}

	/**
	 * @return JSONResponse
	 */
	public function getRetentionPeriods() {
		return new JSONResponse($this->manager->getAll());
	}

	/**
	 * @param string $tagId
	 * @param int $numUnits Number of days|weeks|months|years until retention is performed
	 * @param string $unit One of days|weeks|months|years
	 * @return JSONResponse
	 */
	public function addRetentionPeriod($tagId, $numUnits, $unit) {
		try {
			$this->validate($tagId, $numUnits, $unit);
		} catch (\OutOfBoundsException $e) {
			return new JSONResponse(
				['error' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$conditions = $this->manager->add($tagId, $numUnits, $unit);
		} catch (TagAlreadyHasRetention $e) {
			return new JSONResponse(
				['error' => (string) $this->l->t('The tag already has a retention period')],
				Http::STATUS_BAD_REQUEST
			);
		}

		return new JSONResponse($conditions);
	}

	/**
	 * @param string $tagId
	 * @param int $numUnits Number of days|weeks|months|years until retention is performed
	 * @param string $unit One of days|weeks|months|years
	 * @return JSONResponse
	 */
	public function updateRetentionPeriod($tagId, $numUnits, $unit) {
		try {
			$this->validate($tagId, $numUnits, $unit);
		} catch (\OutOfBoundsException $e) {
			return new JSONResponse(
				['error' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$conditions = $this->manager->update($tagId, $numUnits, $unit);
		} catch (TagHasNoRetention $e) {
			return new JSONResponse(
				['error' => (string) $this->l->t('The tag does not have a retention period')],
				Http::STATUS_BAD_REQUEST
			);
		}

		return new JSONResponse($conditions);
	}

	/**
	 * @param string $tagId
	 * @return Response
	 */
	public function deleteRetentionPeriod($tagId) {
		try {
			$this->manager->delete($tagId);
		} catch (TagHasNoRetention $e) {
			// Kill the exception:
			// No retention is exactly what we want to achieve
		}

		return new Response();
	}

	/**
	 * Validate the input data
	 *
	 * @param string $tagId
	 * @param int $numUnits Number of days|weeks|months|years until retention is performed
	 * @param string $unit One of days|weeks|months|years
	 * @throws \OutOfBoundsException
	 */
	protected function validate($tagId, $numUnits, $unit) {
		try {
			$this->tagManager->getTagsByIds($tagId);
		} catch (TagNotFoundException $e) {
			throw new \OutOfBoundsException((string) $this->l->t('The given tag does not exist'), 1);
		}

		if (!\is_int($numUnits) || $numUnits <= 0) {
			throw new \OutOfBoundsException((string) $this->l->t('Invalid retention period'), 2);
		}

		switch ($unit) {
			case 'days':
			case 'weeks':
			case 'months':
			case 'years':
				break;
			default:
				throw new \OutOfBoundsException((string) $this->l->t('Invalid retention period unit'), 3);
		}
	}
}
