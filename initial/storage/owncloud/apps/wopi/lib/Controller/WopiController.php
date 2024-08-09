<?php /** @noinspection HtmlUnknownTag */

/**
 * ownCloud Wopi
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Piotr Mrowczynski <piotr@owncloud.com>
 * @copyright 2019 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\WOPI\Controller;

use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\Encryption;
use OC_Util;
use OCA\WOPI\LockTokenComparer;
use OCA\WOPI\Service\TokenService;
use OCA\WOPI\Service\FileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OC\HintException;
use OCP\Files\Storage\IPersistentLockingStorage;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lock\Persistent\ILock;

require_once __DIR__ . '/../../vendor/autoload.php';

class WopiController extends Controller {

	/** @var ILogger */
	private $logger;
	/** @var IL10N */
	private $l10n;
	/** @var IUserSession */
	private $userSession;
	/** @var IUserManager */
	private $userManager;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var TokenService */
	private $tokenService;
	/** @var FileService */
	private $fileService;
	/** @var LockTokenComparer */
	private $comparer;
	/** @var IConfig */
	private $config;
	/** @var EventDispatcherInterface */
	private $dispatcher;
	/** @var Encryption\Manager */
	private $enryptionManager;

	/**
	 * WopiController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param IL10N $l10n
	 * @param IUserSession $userSession
	 * @param IUserManager $userManager
	 * @param IURLGenerator $urlGenerator
	 * @param TokenService $tokenService
	 * @param FileService $fileService
	 * @param LockTokenComparer $comparer
	 * @param IConfig $config
	 * @param EventDispatcherInterface $dispatcher
	 * @param Encryption\Manager $enryptionManager
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		ILogger $logger,
		IL10N $l10n,
		IUserSession $userSession,
		IUserManager $userManager,
		IURLGenerator $urlGenerator,
		TokenService $tokenService,
		FileService $fileService,
		LockTokenComparer $comparer,
		IConfig $config,
		EventDispatcherInterface $dispatcher,
		Encryption\Manager $enryptionManager
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;
		$this->tokenService = $tokenService;
		$this->fileService = $fileService;
		$this->comparer = $comparer;
		$this->config = $config;
		$this->dispatcher = $dispatcher;
		$this->enryptionManager = $enryptionManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $fileId
	 * @param string $access_token
	 * @return JSONResponse
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws SecurityException|HintException
	 */
	public function CheckFileInfo($fileId, $access_token): JSONResponse {
		$this->logger->debug("CheckFileInfo for $fileId", ['app' => 'wopi']);

		$tokenData = $this->tokenService->verifyToken($access_token ?? '', $fileId);
		$internalFileId = $tokenData['FileId'];

		$LicenseCheckForEditIsEnabled = false;
		if (isset($tokenData['UserId'])) {
			$file = $this->getFileById($tokenData['UserId'], $internalFileId);

			$location = $this->buildFilePath($file);
			$locationInUrl = \rawurlencode("/$location");
			$brandUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkTo('', 'index.php'));
			$folderUrl = $this->urlGenerator->linkToRouteAbsolute('files.view.index') . "?dir=$locationInUrl";
			$folderShareUrl = $this->urlGenerator->linkToRouteAbsolute('files.view.index') . "?dir=$locationInUrl&fileid=$internalFileId&details=shareTabView";
			$hostEditUrl = $this->urlGenerator->linkToRouteAbsolute('wopi.page.Office', ['_action' => 'edit', 'fileId' => $internalFileId]);
			$hostViewUrl = $this->urlGenerator->linkToRouteAbsolute('wopi.page.Office', ['_action' => 'view', 'fileId' => $internalFileId]);

			$user = $this->userManager->get($tokenData['UserId']);
			if ($user === null) {
				throw new SecurityException('', Http::STATUS_UNAUTHORIZED);
			}

			$userId = $tokenData['UserId'];
			$userFriendlyName = $user->getDisplayName();

			if ($this->config->getSystemValue('wopi.business-flow.enabled', false) === 'yes') {
				$LicenseCheckForEditIsEnabled = true;
			}
		} else {
			$file = $this->getByShareToken($tokenData['ShareToken'], $internalFileId);

			$location = $this->buildFilePath($file);
			$brandUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkTo('', 'index.php'));
			$folderUrl = ''; // not supported yet
			$folderShareUrl = ''; // disallow sharing
			$hostEditUrl = ''; // not possible with public link
			$hostViewUrl = $this->urlGenerator->linkToRouteAbsolute('wopi.page.OfficePublicLink', ['_action' => 'view', 'shareToken' => $tokenData['ShareToken'], 'fileId' => $internalFileId]);

			$userId = 'remote';
			$userFriendlyName = $this->l10n->t('Public Link User');
		}

		$this->logger->debug("CheckFileInfo OK for {$file->getName()}", ['app' => 'wopi']);
		$resp = [
			'BaseFileName' => $file->getName(),
			'OwnerId' => $file->getOwner()->getUID(),
			'Size' => $file->getSize(),
			'UserId' => $userId,
			'Version' => $this->buildVersion($file),
			// optional below
			'BreadcrumbBrandName' => '☁️',
			'BreadcrumbBrandUrl' => $brandUrl,
			'BreadcrumbFolderUrl' => $folderUrl,
			'BreadcrumbFolderName' => $location,
			'UserFriendlyName' => $userFriendlyName,
			'UserCanWrite' => $file->isUpdateable(),
			'UserCanNotWriteRelative' => !$file->getParent()->isCreatable(),
			'UserCanRename' => false,
			'SupportsGetLock' => true,
			'SupportsLocks' => true,
			'SupportsUpdate' => true,
			'SupportsContainers' => false,
			'SupportsAddActivities' => false,
			'SupportedShareUrlTypes' => [],
			'SupportsExtendedLockLength' => true,
			'SupportsDeleteFile' => true,
			'SupportsRename' => false,
			'HostEditUrl' => $hostEditUrl,
			'HostViewUrl' => $hostViewUrl,
			'FileSharingUrl' => $folderShareUrl,
			'LicenseCheckForEditIsEnabled' => $LicenseCheckForEditIsEnabled,
		];

		return new JSONResponse($resp);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $fileId
	 * @param string $access_token
	 * @return Http\DataDisplayResponse
	 * @throws SecurityException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function GetFile(string $fileId, string $access_token): Http\DataDisplayResponse {
		$this->logger->debug("GetFile for $fileId", ['app' => 'wopi']);

		$tokenData = $this->tokenService->verifyToken($access_token ?? '', $fileId);
		$internalFileId = $tokenData['FileId'];

		if (isset($tokenData['UserId'])) {
			$file = $this->getFileById($tokenData['UserId'], $internalFileId);
		} else {
			$file = $this->getByShareToken($tokenData['ShareToken'], $internalFileId);
		}

		$this->logger->debug("GetFile OK for {$file->getName()}", ['app' => 'wopi']);
		$response = new Http\DataDisplayResponse($file->getContent());
		$response->addHeader('X-WOPI-ItemVersion', $this->buildVersion($file));
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $fileId
	 * @param string $access_token
	 * @return JSONResponse
	 * @throws SecurityException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function PutFile(string $fileId, string $access_token) : JSONResponse {
		$this->logger->debug("PutFile for $fileId", ['app' => 'wopi']);
		$tokenData = $this->tokenService->verifyToken($access_token ?? '', $fileId);
		$internalFileId = $tokenData['FileId'];

		if (isset($tokenData['UserId'])) {
			$file = $this->getFileById($tokenData['UserId'], $internalFileId);
		} else {
			$this->logger->error(
				"PutFile {$fileId}
					saving data not permitted for anonymous and remote users",
				['app' => 'wopi']
			);
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$storage = $this->getStorage($file);
		if ($storage === null) {
			return new JSONResponse([], Http::STATUS_NOT_IMPLEMENTED);
		}

		if ($this->request->getHeader('X-WOPI-Override') === 'PUT') {
			$wopiLock = $this->request->getHeader('X-WOPI-Lock');
			if ($wopiLock === null && $file->getSize() > 0) {
				return new JSONResponse([], Http::STATUS_CONFLICT);
			}
			$response = $this->verifyLock($storage, $file, $wopiLock);
			if ($response !== null) {
				return $response;
			}

			$data = \fopen('php://input', 'rb');

			try {
				/** @var File $file */
				$file->putContent($data);
				\fclose($data);
			} catch (NotPermittedException $e) {
				$this->logger->error(
					"PutFile {$file->getName()} 
						saving data not permitted",
					['app' => 'wopi']
				);
				return new JSONResponse([], Http::STATUS_BAD_REQUEST);
			}

			$this->logger->debug("PutFile OK for {$file->getName()}", ['app' => 'wopi']);
			$response = new JSONResponse([], Http::STATUS_OK);
			$response->addHeader('X-WOPI-ItemVersion', $this->buildVersion($file));
			return $response;
		}
		return new JSONResponse([], Http::STATUS_NOT_IMPLEMENTED);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $fileId
	 * @param string $access_token
	 * @return JSONResponse
	 * @throws SecurityException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function FileOperation(string $fileId, string $access_token) : JSONResponse {
		$header = $this->request->getHeader('X-WOPI-Override');
		$this->logger->debug("FileOperation $header request for fileid {$fileId}", ['app' => 'wopi']);

		$tokenData = $this->tokenService->verifyToken($access_token ?? '', $fileId);
		$internalFileId = $tokenData['FileId'];
		
		if (isset($tokenData['UserId'])) {
			$file = $this->getFileById($tokenData['UserId'], $internalFileId);
		} else {
			$this->logger->error(
				"FileOperation {$header} for {$internalFileId}
					not permitted for anonymous and remote users",
				['app' => 'wopi']
			);
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$storage = $this->getStorage($file);
		if ($storage === null) {
			return new JSONResponse([], Http::STATUS_NOT_IMPLEMENTED);
		}

		switch ($header) {
			case 'LOCK':
				$wopiOldLock = $this->request->getHeader('X-WOPI-OldLock');
				if ($wopiOldLock !== null) {
					$this->unlock($storage, $file, $wopiOldLock);
				}
				$wopiLock = $this->request->getHeader('X-WOPI-Lock');
				return $this->lock($storage, $file, $wopiLock, $tokenData);
			case 'UNLOCK':
				$wopiLock = $this->request->getHeader('X-WOPI-Lock');
				return $this->unlock($storage, $file, $wopiLock);
			case 'REFRESH_LOCK':
				$wopiLock = $this->request->getHeader('X-WOPI-Lock');
				return $this->refreshLock($storage, $file, $wopiLock);
			case 'GET_LOCK':
				return $this->getLock($storage, $file);
			case 'DELETE':
				return $this->deleteFile($storage, $file);
			case 'PUT_RELATIVE':
				// utf-7 to utf-8 converted
				// https://wopi.readthedocs.io/projects/wopirest/en/latest/files/PutRelativeFile.html#putrelativefile
				$suggestedTarget = \iconv(
					'utf-7',
					'utf-8',
					$this->request->getHeader('X-WOPI-SuggestedTarget')
				);
				$relativeTarget = \iconv(
					'utf-7',
					'utf-8',
					$this->request->getHeader('X-WOPI-RelativeTarget')
				);
				// Parse overwrite header
				$overwrite = $this->request->getHeader('X-WOPI-OverwriteRelativeTarget') === 'True';
				// other headers
				$fileConversion = $this->request->getHeader('X-WOPI-FileConversion');
				return $this->putFileRelative($file, $suggestedTarget, $relativeTarget, $overwrite, $fileConversion);
			case 'RENAME_FILE':
			case 'PUT_USER_INFO':
			case 'GET_SHARE_URL':
				$this->logger->warning("FileOperation $header unsupported", ['app' => 'wopi']);
				break;
			default:
				$this->logger->warning("FileOperation $header unknown", ['app' => 'wopi']);
		}

		return new JSONResponse([], Http::STATUS_NOT_IMPLEMENTED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $fileId
	 * @param string $folderUrl
	 * @return JSONResponse
	 * @throws Exception
	 */
	public function GenerateNewAuthUserAccessToken($fileId, $folderUrl): JSONResponse {
		$this->logger->debug("GenerateNewAuthUserAccessToken for $fileId $folderUrl", ['app' => 'wopi']);

		return new JSONResponse(
			$this->tokenService->GenerateNewAuthUserAccessToken(
				$fileId,
				$folderUrl,
				$this->userSession->getUser()
			)
		);
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 *
	 * @param string $fileId
	 * @param string|null $shareToken
	 * @param string $folderUrl
	 * @return JSONResponse
	 * @throws Exception
	 */
	public function GenerateNewPublicLinkAccessToken($fileId, $shareToken, $folderUrl): JSONResponse {
		$this->logger->debug("GenerateNewPublicAccessToken for $fileId $shareToken $folderUrl", ['app' => 'wopi']);

		return new JSONResponse(
			$this->tokenService->GenerateNewPublicLinkAccessToken(
				$fileId,
				$folderUrl,
				$shareToken
			)
		);
	}

	/**
	 * Put relative is copy operation of source file to target path
	 * in the source directory
	 *
	 * @param File $sourceFile
	 * @param string $suggestedTarget
	 * @param string $relativeTarget
	 * @param bool $overwrite
	 * @param string $fileConversion
	 * @return JSONResponse
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	private function putFileRelative(
		File $sourceFile,
		$suggestedTarget,
		$relativeTarget,
		$overwrite,
		$fileConversion
	) : JSONResponse {
		// check for unsupported operations
		if ($fileConversion) {
			$this->logger->warning(
				"FileOperation PUT_RELATIVE fileConversion ($fileConversion) is currently not supported",
				['app' => 'wopi']
			);
			return new JSONResponse([], Http::STATUS_NOT_IMPLEMENTED);
		}

		// retrieve target file name
		$parent = $sourceFile->getParent();
		if ($relativeTarget && !$suggestedTarget) {
			// with relative target, throw conflict or bad request,
			// if target cannot be created
			if ($parent->nodeExists($relativeTarget)) {
				$targetFile = $parent->get($relativeTarget);
				if (!($targetFile instanceof File)) {
					$this->logger->error(
						"FileOperation PUT_RELATIVE $relativeTarget 
						unexpected target",
						['app' => 'wopi']
					);
					return new JSONResponse([], Http::STATUS_BAD_REQUEST);
				}

				$targetStorage = $this->getStorage($targetFile);
				$lock = null;
				if ($targetStorage) {
					$lock = $this->getLockByFile($targetStorage, $targetFile);
				}

				// with relative target that exists, throw conflict
				// when overwrite not requested or
				// overwrite filerequested but on file with a valid lock
				if (($overwrite && $lock) || !$overwrite) {
					$this->logger->debug(
						"FileOperation PUT_RELATIVE $relativeTarget conflict",
						['app' => 'wopi']
					);
					$response = new JSONResponse(
						[
							'Name' => $targetFile->getName(),
							'Url' => $this->getWopiSrc($targetFile, $this->userSession->getUser()),
						],
						Http::STATUS_CONFLICT
					);
					if ($lock) {
						$response->addHeader('X-WOPI-Lock', $lock->getToken());
					}
					return $response;
				}
			}

			$targetFilename = $relativeTarget;
		} elseif (!$relativeTarget && $suggestedTarget) {
			// with suggested target, do not throw conflict or bad request,
			// find valid path if possible
			if (\strpos($suggestedTarget, '.') === 0) {
				// SuggestedExtension
				$filename = \pathinfo($sourceFile->getName(), PATHINFO_FILENAME);
				$ext = \substr($suggestedTarget, 1);
			} else {
				// SuggestedName
				$filename = \pathinfo($suggestedTarget, PATHINFO_FILENAME);
				$ext = \pathinfo($suggestedTarget, PATHINFO_EXTENSION);
			}

			try {
				$targetFilename = $parent->getNonExistingName($filename . '.' . $ext);
			} catch (NotPermittedException $e) {
				// according to spec, with suggested name we should not return
				// bad request, throw internal error
				$this->logger->error(
					"FileOperation PUT_RELATIVE $filename.$ext 
					failed to find non existing filename for path",
					['app' => 'wopi']
				);
				return new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
			}
		} else {
			// One of the options has to be specified
			$this->logger->warning(
				"FileOperation PUT_RELATIVE suggested/relative headers conflict",
				['app' => 'wopi']
			);
			return new JSONResponse([], Http::STATUS_NOT_IMPLEMENTED);
		}

		$data = \file_get_contents('php://input');

		try {
			// do the "save as" to target
			$targetFile = $parent->newFile($targetFilename);
			$targetFile->putContent($data);
		} catch (NotPermittedException $e) {
			// respond bad request, and do not suggest any valid path with
			// header X-WOPI-ValidRelativeTarget e.g. due to invalid filename
			$this->logger->error(
				"FileOperation PUT_RELATIVE $targetFilename 
				creating file failed",
				['app' => 'wopi']
			);
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		} catch (Exception $e) {
			// Make sure to return bad request instead of internal error
			$this->logger->error(
				"FileOperation PUT_RELATIVE $targetFilename 
				unexpected exception",
				['app' => 'wopi']
			);
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$this->logger->debug(
			"FileOperation PUT_RELATIVE $targetFilename OK",
			['app' => 'wopi']
		);

		// required response property - name and url of file endpoint for a new file
		// https://wopi.readthedocs.io/projects/wopirest/en/latest/files/PutRelativeFile.html#required-response-properties
		return new JSONResponse([
			'Name' => $targetFile->getName(),
			'Url' => $this->getWopiSrc($targetFile, $this->userSession->getUser()),
		], Http::STATUS_OK);
	}

	/**
	 * Delete file
	 *
	 * @param IPersistentLockingStorage $sourceStorage
	 * @param File $sourceFile
	 * @return JSONResponse
	 */
	private function deleteFile(IPersistentLockingStorage $sourceStorage, File $sourceFile) : JSONResponse {
		if ($lock = $this->getLockByFile($sourceStorage, $sourceFile)) {
			$wopiLock = $lock->getToken();
			$this->logger->debug(
				"FileOperation DELETE file locked",
				['app' => 'wopi']
			);
			$response = new JSONResponse([], Http::STATUS_CONFLICT);
			$response->addHeader('X-WOPI-Lock', $wopiLock);
			return $response;
		}

		try {
			$sourceFile->delete();

			$this->logger->debug(
				"FileOperation DELETE {$sourceFile->getName()} OK",
				['app' => 'wopi']
			);
			return new JSONResponse([], Http::STATUS_OK);
		} catch (NotPermittedException $e) {
			$this->logger->error(
				"FileOperation DELETE {$sourceFile->getName()} 
				failed",
				['app' => 'wopi']
			);
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @param string $userId
	 * @param string $internalFileId
	 * @return File
	 * @throws HintException
	 * @throws NotPermittedException
	 */
	private function getFileById(string $userId, string $internalFileId): File {
		$this->initFileSystem($userId);
		$file = $this->fileService->getByFileId(\intval($internalFileId));

		if ($file instanceof File) {
			return $file;
		}
		throw new NotPermittedException("Resource with $internalFileId is not a file");
	}

	/**
	 * @param string $shareToken
	 * @param string $internalFileId
	 * @return File
	 * @throws HintException
	 */
	private function getByShareToken($shareToken, $internalFileId): File {
		$file = $this->fileService->getByShareToken($shareToken, (int)$internalFileId);
		if ($file instanceof File) {
			return $file;
		}
		throw new NotPermittedException("Resource with $internalFileId is not a file");
	}

	/**
	 * Returns the path to a given file
	 *
	 * @param FileInfo $file
	 * @return string
	 */
	public function buildFilePath(FileInfo $file): string {
		// path looks like /admin/files/foo/bar.txt
		$location = \explode('/', $file->getPath());
		// remove empty string before the first /
		\array_shift($location);
		// removes the user id
		\array_shift($location);
		// removes 'files'
		\array_shift($location);
		// removes the file
		\array_pop($location);
		// glue the path together
		return \implode('/', $location);
	}

	/**
	 * @param IPersistentLockingStorage $storage
	 * @param File $file
	 * @param string $wopiLock
	 * @param array $tokenData
	 * @return JSONResponse
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	private function lock(
		IPersistentLockingStorage $storage,
		File $file,
		string $wopiLock,
		array $tokenData
	): JSONResponse {
		$lock = $this->getLockByToken($storage, $file, $wopiLock);
		if ($lock === null) {
			// set new lock
			$user = $this->userManager->get($tokenData['UserId']);

			$storage->lockNodePersistent($file->getInternalPath(), [
				'token' => $wopiLock,
				'owner' => "{$user->getDisplayName()} via Office Online"
			]);
			$response = new JSONResponse([], Http::STATUS_OK);
			$response->addHeader('X-WOPI-ItemVersion', $this->buildVersion($file));

			return $response;
		}

		if (!$this->tokenMatchesLock($wopiLock, $lock)) {
			$this->logLockConflict($wopiLock, $lock);
			$response = new JSONResponse([], Http::STATUS_CONFLICT);
			$response->addHeader('X-WOPI-LockFailureReason', "Locked by {$lock->getOwner()}");
			$response->addHeader('X-WOPI-Lock', $lock->getToken());

			return $response;
		}

		return $this->refreshLock($storage, $file, $wopiLock);
	}

	/**
	 * @param IPersistentLockingStorage $storage
	 * @param File $file
	 * @return JSONResponse
	 */
	private function getLock(IPersistentLockingStorage $storage, File $file) : JSONResponse {
		$lock = $this->getLockByFile($storage, $file);
		if ($lock) {
			// locked but not by Office Online
			if (\strpos($lock->getOwner(), 'Office Online') === false) {
				$this->logLockConflict('', $lock);
				$response = new JSONResponse([], Http::STATUS_CONFLICT);
				$response->addHeader('X-WOPI-LockFailureReason', "Locked by {$lock->getOwner()}");
				$response->addHeader('X-WOPI-Lock', $lock->getToken());

				return $response;
			}

			// locked - send the lock header
			$response = new JSONResponse([], Http::STATUS_OK);
			$response->addHeader('X-WOPI-Lock', $lock->getToken());
			return $response;
		}

		// not locked - send empty lock header
		$response = new JSONResponse([], Http::STATUS_OK);
		$response->addHeader('X-WOPI-Lock', '');

		return $response;
	}

	/**
	 * @param IPersistentLockingStorage $storage
	 * @param File $file
	 * @param string $wopiLock
	 * @return JSONResponse
	 */
	private function refreshLock(
		IPersistentLockingStorage $storage,
		File $file,
		string $wopiLock
	): JSONResponse {
		$lock = $this->getLockByToken($storage, $file, $wopiLock);
		if ($lock === null) {
			$response = new JSONResponse([], Http::STATUS_CONFLICT);
			$response->addHeader('X-WOPI-LockFailureReason', 'Not locked');
			$response->addHeader('X-WOPI-Lock', '');

			return $response;
		}

		if (!$this->tokenMatchesLock($wopiLock, $lock)) {
			$this->logLockConflict($wopiLock, $lock);
			$response = new JSONResponse([], Http::STATUS_CONFLICT);
			$response->addHeader('X-WOPI-LockFailureReason', "Locked by {$lock->getOwner()}");
			$response->addHeader('X-WOPI-Lock', $lock->getToken());

			return $response;
		}

		// refresh the lock
		$storage->lockNodePersistent($file->getInternalPath(), [
			'token' => $lock->getToken(),
		]);
		$response = new JSONResponse([], Http::STATUS_OK);
		$response->addHeader('X-WOPI-Lock', $wopiLock);

		return $response;
	}

	/**
	 * @param IPersistentLockingStorage $storage
	 * @param File $file
	 * @param string $wopiLock
	 * @return JSONResponse
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	private function unlock(
		IPersistentLockingStorage $storage,
		File $file,
		string $wopiLock
	): JSONResponse {
		$lock = $this->getLockByToken($storage, $file, $wopiLock);
		if ($lock === null) {
			$response = new JSONResponse([], Http::STATUS_CONFLICT);
			$response->addHeader('X-WOPI-LockFailureReason', 'Not locked');
			$response->addHeader('X-WOPI-Lock', '');

			return $response;
		}

		if ($this->tokenMatchesLock($wopiLock, $lock)) {
			$storage->unlockNodePersistent($file->getInternalPath(), [
				'token' => $lock->getToken()
			]);
			$response = new JSONResponse([], Http::STATUS_OK);
			$response->addHeader('X-WOPI-ItemVersion', $this->buildVersion($file));
			return $response;
		}

		$this->logLockConflict($wopiLock, $lock);
		$response = new JSONResponse([], Http::STATUS_CONFLICT);
		$response->addHeader('X-WOPI-LockFailureReason', "Locked by {$lock->getOwner()}");
		$response->addHeader('X-WOPI-Lock', $lock->getToken());

		return $response;
	}

	/**
	 * @param IPersistentLockingStorage $storage
	 * @param FileInfo $file
	 * @return null|ILock
	 */
	private function getLockByFile(IPersistentLockingStorage $storage, FileInfo $file) : ?ILock {
		$locks = $storage->getLocks($file->getInternalPath(), false);
		foreach ($locks as $lock) {
			// locked
			return $lock;
		}
		// not locked
		return null;
	}

	/**
	 * @param IPersistentLockingStorage $storage
	 * @param FileInfo $file
	 * @param string $wopiLock
	 * @return null|ILock
	 */
	private function getLockByToken(
		IPersistentLockingStorage $storage,
		FileInfo $file,
		string $wopiLock
	) : ?ILock {
		$locks = $storage->getLocks($file->getInternalPath(), false);
		if (empty($locks)) {
			return null;
		}
		foreach ($locks as $lock) {
			if ($this->tokenMatchesLock($wopiLock, $lock)) {
				return $lock;
			}
		}

		return $locks[0];
	}

	/**
	 * @param string $wopiLock
	 * @param ILock $lock
	 * @return bool
	 */
	private function tokenMatchesLock(string $wopiLock, ILock $lock): bool {
		return $this->comparer->compare($wopiLock, $lock->getToken());
	}

	/**
	 * @param string $wopiLock
	 * @param ILock $lock
	 */
	private function logLockConflict(string $wopiLock, ILock $lock): void {
		$this->logger->warning("Requested lock $wopiLock is conflicting with existing lock {$lock->getToken()}", ['app' => 'wopi']);
	}

	/**
	 * @param IPersistentLockingStorage $storage
	 * @param FileInfo $file
	 * @param string|null $wopiLock
	 * @return JSONResponse|null
	 */
	private function verifyLock(
		IPersistentLockingStorage $storage,
		FileInfo $file,
		?string $wopiLock
	): ?JSONResponse {
		$lock = $this->getLockByToken($storage, $file, $wopiLock ?? '');
		if ($lock === null && $wopiLock === null) {
			return null;
		}
		if ($lock === null) {
			$response = new JSONResponse([], Http::STATUS_CONFLICT);
			$response->addHeader('X-WOPI-LockFailureReason', 'Not locked');
			$response->addHeader('X-WOPI-Lock', '');

			return $response;
		}

		if (!$this->tokenMatchesLock($wopiLock, $lock)) {
			$this->logLockConflict($wopiLock, $lock);
			$response = new JSONResponse([], Http::STATUS_CONFLICT);
			$response->addHeader('X-WOPI-LockFailureReason', "Locked by {$lock->getOwner()}");
			$response->addHeader('X-WOPI-Lock', $lock->getToken());

			return $response;
		}

		return null;
	}

	/**
	 * Gets WOPISrc URL as specified by
	 * https://wopi.readthedocs.io/projects/wopirest/en/latest/concepts.html#term-wopisrc
	 *
	 * @param File $targetFile
	 * @param IUser $user
	 * @return string
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	private function getWopiSrc(File $targetFile, IUser $user) : string {
		$newToken = $this->tokenService->GenerateNewAuthUserAccessToken(
			(string) $targetFile->getId(),
			$targetFile->getParent()->getPath(),
			$user
		);
		$queryParameters = [
			'access_token' => $newToken['token']
		];

		return \implode([
			$this->urlGenerator->linkToRouteAbsolute('wopi.wopi.CheckFileInfo', ['fileId' => $targetFile->getId()]),
			'?',
			\http_build_query($queryParameters)
		]);
	}

	/**
	 * @param FileInfo $file
	 * @return string
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	private function buildVersion(FileInfo $file): string {
		return 'V' . $file->getEtag() . \md5($file->getChecksum());
	}

	/**
	 * @param FileInfo $file
	 * @return IPersistentLockingStorage|IStorage|null
	 * @throws NotFoundException
	 */
	private function getStorage(FileInfo $file) {
		/** @var IStorage | IPersistentLockingStorage $storage */
		$storage = $file->getStorage();
		if (!$storage->instanceOfStorage(IPersistentLockingStorage::class)) {
			return null;
		}
		return $storage;
	}

	private function initFileSystem(string $userId) {
		// get user for given uid
		$user = $this->userManager->get($userId);

		// setting a user is required to have e.g. proper activity entry, audit entry,
		// support encryption etc.
		$this->userSession->setUser($user);

		if ($this->enryptionManager->isEnabled()) {
			// the master key encryption requires to emit after login event in order to
			// initialize encryption backend for given user. NULL is passed for credential as
			// encryption backend used needs to retrieve delegating credential
			// on its own (e.g. master key). Backends requiring user credentials are
			// thus not supported for this app
			$this->logger->debug("Sending user.afterlogin event for user {$user->getUID()}", ['app' => 'wopi']);
			$afterEvent = new GenericEvent(
				null,
				['loginType' => 'password', 'user' => $user, 'uid' => $user->getUID(), 'password' => null]
			);
			/** @phpstan-ignore-next-line */
			$this->dispatcher->dispatch($afterEvent, 'user.afterlogin');
		}
		
		// setup filesystem for the user
		OC_Util::setupFS($userId);
	}
}
