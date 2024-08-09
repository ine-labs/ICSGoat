<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle\Controller;

use OC\AppFramework\Http;
use OC\Files\Node\File;
use OC\Files\Node\Folder;
use OCA\Files_Lifecycle\Policy\IPolicy;
use OCA\Files_Lifecycle\RestoreProcessor;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;

/**
 * Class RestoreController
 *
 * @package OCA\Files_Lifecycle\Controller
 */
class RestoreController extends Controller {
	/**
	 * @var ISession
	 */
	private $session;

	/**
	 * @var IRootFolder
	 */
	private $rootFolder;

	/**
	 * @var RestoreProcessor
	 */
	private $restoreProcessor;

	/**
	 * @var IPolicy
	 */
	private $policy;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var bool
	 */
	private $validUser = false;

	/**
	 * @var bool
	 */
	private $validPath = false;

	/**
	 * @var IL10N $t
	 */
	private $translator;

	/**
	 * ArchiveController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param ISession $session
	 * @param IRootFolder $rootFolder
	 * @param RestoreProcessor $restoreProcessor
	 * @param IPolicy $policy
	 * @param IL10N $translator
	 * @param IUserSession $userSession
	 */
	public function __construct(
		$appName,
		IRequest $request,
		ISession $session,
		IRootFolder $rootFolder,
		RestoreProcessor $restoreProcessor,
		IPolicy $policy,
		IL10N $translator,
		IUserSession $userSession
	) {
		parent::__construct($appName, $request);
		$this->session = $session;
		$this->rootFolder = $rootFolder;
		$this->restoreProcessor = $restoreProcessor;
		$this->policy = $policy;
		$this->translator = $translator;
		$this->userSession = $userSession;
	}

	/**
	 * Restore Action
	 *
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 *
	 * @return JSONResponse
	 *
	 */
	public function restore($id) {
		try {
			$this->authRestore();
		} catch (NotPermittedException $e) {
			return new JSONResponse([], \OCP\AppFramework\Http::STATUS_UNAUTHORIZED);
		}
		$file = $this->rootFolder->getById($id, true);

		$response = $this->isValidFile(
			$file,
			$this->userSession->getUser()->getUID()
		);
		if ($response === true) {
			$path = $file[0]->getPath();
			$owner = $file[0]->getOwner();
			$destination = $this->restoreProcessor
				->restoreFileFromPath($path, $owner);
			$destinationInfo = \pathinfo($destination);
			$data = [
				'sourcePath' => $path,
				'destinationPath' => $destination,
				'message' => $this->translator->t(
					'Successfully restored "%s".',
					[$destinationInfo['basename']]
				)
			];
			return new JSONResponse($data);
		}
		return $response;
	}

	/**
	 * Authorizes a restore action
	 *
	 * @return void
	 *
	 * @throws NotPermittedException
	 */
	protected function authRestore() {
		// Get the appropriate config variables
		$userAllowedToRestore = $this->policy->userCanRestore(
			$this->userSession->getUser()
		);
		$impersonatorAllowedToRestore = $this->policy->impersonatorCanRestore();
		$impersonated = $this->session->get('impersonator') !== null;
		if ($userAllowedToRestore
			|| ($impersonatorAllowedToRestore && $impersonated)
		) {
			return;
		}
		throw new NotPermittedException();
	}

	/**
	 * Checks if a file for a given ID is valid for Restoring
	 *
	 * @param array $file
	 * @param string $userId
	 *
	 * @return bool|JSONResponse
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	protected function isValidFile($file, $userId) {
		$notFound = new JSONResponse(
			[],
			Http::STATUS_NOT_FOUND
		);

		$denied =  new JSONResponse(
			[],
			Http::STATUS_FORBIDDEN
		);

		// No files found
		if (\count($file) === 0) {
			return $notFound;
		}

		// Get File/Folder Path
		if ($file[0] instanceof File || $file[0] instanceof Folder) {
			$path = $file[0]->getPath();
		} else {
			return $notFound;
		}

		$parts = \explode('/', \ltrim($path, '/'));

		// Check if file path is in archive of requesting user
		if (isset($parts[0]) && $parts[0] === $userId) {
			$this->validUser = true;
		} else {
			return $denied;
		}

		// Check if file path is in user archive
		if (isset($parts[1]) && $parts[1] === 'archive') {
			$this->validPath = true;
		} else {
			return $notFound;
		}

		// Deny restoring the files folder itself
		if (isset($parts[2]) && !isset($parts[3]) && $parts[2] === 'files') {
			return $denied;
		}

		// Checks if path belongs to the user
		if ($file[0]->getOwner()->getUID() !== $userId) {
			return $denied;
		}

		// Check permissions
		if (!$file[0]->isUpdateable() || !$file[0]->isDeletable()) {
			return $denied;
		}

		return true;
	}
}
