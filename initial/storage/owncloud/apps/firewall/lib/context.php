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

use OC\Files\Node\Node;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class Context {
	/**
	 * DO NOT CHANGE without updating the config and docs
	 */
	public const REQUEST_TYPE_OTHER = 'other';
	public const REQUEST_TYPE_PUBLIC = 'public';
	public const REQUEST_TYPE_WEBDAV = 'webdav';
	/** @deprecated
	 *  @since OC 10
	 */
	public const REQUEST_TYPE_UPLOAD = 'upload';
	/** @deprecated
	 *  @since OC 10.0.1
	 */
	public const REQUEST_TYPE_FILES_DROP = 'files_drop';

	/**
	 * DO NOT CHANGE without updating the config and docs
	 */
	public const SYNC_CLIENT_ANDROID = 'android';
	public const SYNC_CLIENT_ANDROID_BRANDED = 'android_branded';
	public const SYNC_CLIENT_DESKTOP = 'desktop';
	public const SYNC_CLIENT_DESKTOP_BRANDED = 'desktop_branded';
	public const SYNC_CLIENT_IOS = 'ios';
	public const SYNC_CLIENT_IOS_BRANDED = 'ios_branded';
	public const SYNC_CLIENT_OTHER = 'other';
	public const SYNC_CLIENT_BRANDED = 'branded';
	public const SYNC_CLIENT_NON_BRANDED = 'non_branded';

	/** @var IRequest */
	protected $request;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserSession */
	protected $userSession;

	/** @var IMimeTypeDetector */
	protected $mimeTypeDetector;

	/** @var Config */
	protected $config;

	/** @var array */
	protected $fileIds = [];

	/**
	 *
	 * @var \OCP\Files\IRootFolder
	 */
	protected $rootFolder;

	/**
	 *
	 * @var \OC\Cache\File
	 */
	protected $fileCache;
	/**
	 *
	 *
	 * @param IRequest $request
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param IMimeTypeDetector $mimeTypeDetector
	 * @param Config $config
	 * @param \OCP\Files\IRootFolder $rootFolder
	 */
	public function __construct(
		IRequest $request,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IMimeTypeDetector $mimeTypeDetector,
		Config $config,
		IRootFolder $rootFolder
	) {
		$this->request = $request;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->mimeTypeDetector = $mimeTypeDetector;
		$this->config = $config;
		$this->rootFolder = $rootFolder;
	}

	/*
	 * Request related methods
	 */

	/**
	 * @return string
	 */
	public function getRequestFullUrl() {
		return $this->request->getServerProtocol() . '://'
			. $this->request->getServerHost()
			. $this->request->getRequestUri();
	}

	/**
	 * @return string
	 */
	public function getRequestUri() {
		return $this->request->getRequestUri();
	}

	/**
	 * @param bool $verbose If true upload can also be returned
	 * @return string webdav, public or other
	 */
	public function getRequestType($verbose = false) {
		$pathInfo = $this->request->getPathInfo();

		// share links via index.php/s/<token>
		$isShareLink = (
			$this->isScriptName('index.php')
			&& \substr($pathInfo, 0, \strlen('/s/')) === '/s/'
		);

		//public previews
		$isPublicPreview = (
			$this->isScriptName('index.php')
			&& $pathInfo === '/apps/files_sharing/ajax/publicpreview.php'
		);

		if ($this->isScriptName('public.php') // public.php
			|| $isShareLink
			|| $isPublicPreview
		) {
			return self::REQUEST_TYPE_PUBLIC;
		}

		$isDavPathV1 = (
			$pathInfo === '/webdav'
			|| \substr($pathInfo, 0, \strlen('/webdav/')) === '/webdav/'
		);
		$isDavPathV2 = (
			$pathInfo === '/dav/files'
			|| \substr($pathInfo, 0, \strlen('/dav/files/')) === '/dav/files/'
			|| \substr($pathInfo, 0, \strlen('/dav/uploads/')) === '/dav/uploads/'
		);

		if ($this->isScriptName('remote.php')
			&& ($isDavPathV1 || $isDavPathV2)
		) {
			return self::REQUEST_TYPE_WEBDAV;
		}

		if ($verbose) {
			// Files drop via index.php/apps/files_drop/<token>
			if ($this->isScriptName('index.php')
				&& \substr($pathInfo, 0, \strlen('/apps/files_drop/')) === '/apps/files_drop/'
			) {
				return self::REQUEST_TYPE_FILES_DROP;
			}
		}

		return self::REQUEST_TYPE_OTHER;
	}

	/**
	 * @param string $toFind
	 * @return bool
	 */
	protected function isScriptName($toFind) {
		return \substr($this->request->getScriptName(), 0 - \strlen('/' . $toFind)) === '/' . $toFind;
	}

	/**
	 * @return int
	 */
	public function getRequestTime() {
		return \time();
	}

	/**
	 * @param \Sabre\DAV\Server $sabreServer in case of new dav path
	 * and using new chunking
	 * @return int|null|string if size could not be determined
	 */
	public function getUploadSize(\Sabre\DAV\Server $sabreServer = null) {
		$checkSize = false;
		$path = $this->request->getPathInfo();
		$requestType = $this->getRequestType();
		$requestMethod = $this->request->getMethod();
		$ocTotalLength = $this->request->getHeader('OC_TOTAL_LENGTH');
		if ($requestType === self::REQUEST_TYPE_WEBDAV
			&& ($requestMethod === 'PUT'
			|| $requestMethod === 'MOVE'
			|| $requestMethod === 'COPY')
		) {
			$checkSize = true;
		} elseif ($requestType === self::REQUEST_TYPE_PUBLIC
			&& ($requestMethod === 'PUT')
		) {
			$checkSize = true;
		} elseif ($ocTotalLength !== null
			&& ($requestMethod === 'MKCOL')
			&& $requestType === self::REQUEST_TYPE_WEBDAV
		) {
			//for MKCOL we can trust the 'OC_TOTAL_LENGTH' header
			//no upload happens with those request methods but if the header is
			//true we can block the upload early in the process
			return $ocTotalLength;
		} elseif ($this->getRequestType(true) === self::REQUEST_TYPE_FILES_DROP) {
			if ($requestMethod === 'POST') {
				$file = $this->request->getUploadedFile('file');
				$checkSize = !empty($file);
			}
		} else {
			if (\in_array($requestMethod, ['POST', 'PUT'])) {
				$files = $this->request->getUploadedFile('files');
				$checkSize = !empty($files);
			}
		}

		if ($checkSize) {
			$fileSize = null;

			if ($this->isV1WebdavChunk()) {
				//v1 chunking protocol size check
				$path = \substr($path, \strlen('/webdav/'));
				$decoded = $this->decodeV1ChunkingName($path);
				$chunkedFile = $this->createV1Chunkedfile($decoded);
				//we need to ignore the current chunk in getCurrentSize()
				//and add the 'CONTENT_LENGTH' otherwise the calculation would
				//be wrong in the case a chunk gets uploaded twice
				$cache = $this->getFileCache();
				$prefix = $chunkedFile->getPrefix();
				$fileSize = 0;
				for ($i = 0; $i < $decoded['chunkcount']; $i++) {
					if ($i !== (int) $decoded ['index']) {
						$fileSize += $cache->size($prefix.$i);
					}
				}
			} elseif ($requestType === self::REQUEST_TYPE_WEBDAV
				&& ($requestMethod === 'PUT' || $requestMethod === 'MOVE')
				&& \substr($path, 0, \strlen('/dav/uploads/')) === '/dav/uploads/'
				&& $sabreServer !== null
			) {
				//v2 chunking protocol size check
				$path = \substr($path, \strlen('/dav/'));

				$children = $sabreServer->tree->getChildren(\dirname($path));
				foreach ($children as $child) {
					//we need to ignore the current chunk
					//and add the 'CONTENT_LENGTH' otherwise the calculation would
					//be wrong in the case a chunk gets uploaded twice
					//also ignore if we come to a futureFile, as a futureFile would
					//count all the sizes again.
					if ($child->getName() !== \basename($path)
						&& !\method_exists($child, "isFutureFile")
					) {
						$fileSize += $child->getSize();
					}
				}
			} elseif (($requestMethod === 'MOVE' || $requestMethod === 'COPY')
				&& \strpos($path, '/webdav/') === 0
			) {
				//dav path v1 MOVE or COPY
				$path = \substr($path, \strlen('/webdav/'));
				$fileSize = $this->getFileSizeFromFilesystem($path);
			} elseif (($requestMethod === 'MOVE' || $requestMethod === 'COPY')
				&& \strpos($path, '/dav/files/') === 0
				&& $sabreServer !== null
			) {
				//dav path v2 MOVE or COPY
				$path = \substr($path, \strlen('/dav/'));
				/** @var Node $node */
				$node = $sabreServer->tree->getNodeForPath($path);
				$fileSize = $node->getSize();
			}
			$uploadSize = $this->request->getHeader('CONTENT_LENGTH');
			if ($fileSize !== null) {
				return $fileSize + $uploadSize;
			}

			//it's not a chunking upload, nor a MOVE or COPY
			//so we can trust the HTTP header
			//if the client lies about CONTENT_LENGTH the server will truncate
			//the file anyway
			return $uploadSize;
		}
		return null;
	}

	/**
	 * wrapper for \OC_FileChunking::decodeName
	 *
	 * @param string $path
	 * @return array
	 */
	protected function decodeV1ChunkingName($path) {
		return \OC_FileChunking::decodeName($path);
	}

	/**
	 * creates new \OC_FileChunking
	 *
	 * @param array $decoded
	 * @return \OC_FileChunking
	 */
	protected function createV1Chunkedfile($decoded) {
		return new \OC_FileChunking($decoded);
	}

	/**
	 * checks if its a v1 chunking upload. Wrapper for \OC_FileChunking::isWebdavChunk
	 *
	 * @return boolean
	 */
	protected function isV1WebdavChunk() {
		return \OC_FileChunking::isWebdavChunk();
	}

	/**
	 *
	 * @return \OC\Cache\File
	 */
	protected function getFileCache() {
		if (!isset($this->fileCache)) {
			$this->fileCache = new \OC\Cache\File();
		}
		return $this->fileCache;
	}

	/**
	 * wrapper for \OC\Files\Filesystem::filesize
	 *
	 * @param string $path
	 * @return mixed|boolean|NULL|resource
	 */
	protected function getFileSizeFromFilesystem($path) {
		return \OC\Files\Filesystem::filesize($path);
	}
	/**
	 * @return string|null
	 */
	public function getUploadType() {
		$requestType = $this->getRequestType();
		$verboseRequestType = $this->getRequestType(true);
		if ($requestType === self::REQUEST_TYPE_WEBDAV
			|| $verboseRequestType === self::REQUEST_TYPE_PUBLIC
		) {
			if ($this->request->getMethod() === 'PUT') {
				$path = $this->request->getPathInfo();
				if ($this->request->getHeader('OC-CHUNKED')) {
					$decoded = \OC_FileChunking::decodeName($path);
					if (isset($decoded['name'])) {
						$path = $decoded['name'];
					}
				}
				return $this->mimeTypeDetector->detectPath($path);
			} elseif ($this->request->getMethod() === 'MOVE') {
				$path = $this->request->getPathInfo();
				if (\preg_match('|/dav/uploads/.*/\.file|', $path, $matches)) {
					$targetPath = $this->request->getHeader('DESTINATION');
					return $this->mimeTypeDetector->detectPath($targetPath);
				}
			}
		} elseif ($verboseRequestType === self::REQUEST_TYPE_FILES_DROP) {
			if ($this->request->getMethod() === 'POST') {
				$file = $this->request->getUploadedFile('file');
				if (isset($file['name'])) {
					return $this->mimeTypeDetector->detectPath($file['name']);
				}
			}
		} else {
			if (\in_array($this->request->getMethod(), ['POST', 'PUT'])) {
				$files = $this->request->getUploadedFile('files');
				if (isset($files['name'][0])) {
					return $this->mimeTypeDetector->detectPath($files['name'][0]);
				}
			}
		}

		return null;
	}

	/*
	 * User related methods
	 */

	/**
	 * @return string[]
	 */
	public function getUserGroups() {
		$user = $this->userSession->getUser();

		if ($user instanceof IUser) {
			return $this->groupManager->getUserGroupIds($user);
		} else {
			return [];
		}
	}

	/*
	 * Client related methods
	 */

	/**
	 * @return string
	 */
	public function getRemoteAddress() {
		return $this->request->getRemoteAddress();
	}

	/**
	 * @return string
	 */
	public function getRemoteServerAddress() {
		$header = $this->request->getHeader('SERVER_ADDR');
		return $header !== null ? $header : '';
	}

	/**
	 * @return string
	 */
	public function getUserAgent() {
		return $this->request->getHeader('User-Agent');
	}

	/**
	 * @return string
	 */
	public function getClientDevice() {
		$userAgent = \strtolower($this->getUserAgent());

		$brandedClients = $this->config->getBrandedClients();
		if (!empty($brandedClients) && \is_array($brandedClients)) {
			$device = $this->findDevice($userAgent, $brandedClients);

			if ($device !== self::SYNC_CLIENT_OTHER) {
				// Only send the device if we found one, otherwise try the
				// default clients
				return $device;
			}
		}

		$defaultClients = [
			// Current user agents
			// Mozilla/5.0 (<os>) mirall/<version>
			'*) mirall/*' => self::SYNC_CLIENT_DESKTOP,
			'Mozilla/5.0 (Android) ownCloud-android/*' => self::SYNC_CLIENT_ANDROID,
			'Mozilla/5.0 (iOS) ownCloud-iOS/*' => self::SYNC_CLIENT_IOS,
			'ownCloudApp/*iOS/*' => self::SYNC_CLIENT_IOS,

			// Older user agents
			'*mirall*' => self::SYNC_CLIENT_DESKTOP,
			'*csync*' => self::SYNC_CLIENT_DESKTOP,

			'*iOS-ownCloud' => self::SYNC_CLIENT_IOS,
			'*ownCloud iOS Client*' => self::SYNC_CLIENT_IOS,

			'*Android-ownCloud*' => self::SYNC_CLIENT_ANDROID,
			'Mozilla/5.0 (Android) ownCloud*' => self::SYNC_CLIENT_ANDROID,
		];

		return $this->findDevice($userAgent, $defaultClients);
	}

	protected function findDevice($userAgent, $devices) {
		foreach ($devices as $clientAgent => $device) {
			if ($this->userAgentMatchesDevice($userAgent, \strtolower($clientAgent)) !== false) {
				return $device;
			}
		}

		return self::SYNC_CLIENT_OTHER;
	}

	/**
	 * @param string $userAgent
	 * @param string $device
	 * @return bool
	 */
	protected function userAgentMatchesDevice($userAgent, $device) {
		if (\strlen($device) <= 3) {
			return false;
		}

		$device = \strtolower($device);

		// No wildcard means exact match
		if (\substr($device, 0, 1) !== '*' && \substr($device, -1) !== '*') {
			return $userAgent === $device;
		}

		// No leading wildcard
		if (\substr($device, 0, 1) !== '*') {
			$beginning = \substr($device, 0, -1);
			$pieces = \explode('*', $beginning);
			if (\substr($userAgent, 0, \strlen($pieces[0])) !== $pieces[0]) {
				return false;
			} else {
				if (isset($pieces[1])) {
					$remainingUserAgent = \substr($userAgent, \strlen($pieces[0]));
					return \strpos($remainingUserAgent, $pieces[1]) !== false;
				}
			}
			return true;
		}

		// Leading wildcard only
		if (\substr($device, -1) !== '*') {
			$ending = \substr($device, 1);
			return \substr($userAgent, 0 - \strlen($ending)) === $ending;
		}

		// Leading and trailing wildcard, find anywhere
		return \strpos($userAgent, \substr($device, 1, -1)) !== false;
	}

	/*
	 * File related methods
	 */

	/**
	 * Set the file that is checked
	 * if the file is a thumbnail the id of the source file
	 * is also added to the list
	 *
	 * @param array|null $fileIds
	 * @return array Cleaned and sorted list of file ids
	 */
	public function setFileIds($fileIds) {
		if (!\is_array($fileIds)) {
			$this->fileIds = [];
			return [];
		}

		// File IDs as keys
		$fileIds = \array_flip($fileIds);
		unset($fileIds[-1]);
		$fileIds = \array_keys($fileIds);
		$fileIds = \array_filter(
			$fileIds,
			function ($value) {
				return \is_int($value);
			}
		);

		//if the file is a thumbnail, we also want to check the source file
		//if the source file is blocked, we also want to block the preview
		//we only need the id of the source file once, so we only need to find
		//the folder for the thumbnail that has the source file id as name
		//even if the request comes from a file within that thumbnail folder
		//it will be blocked because the list of $fileIds will always contain
		//all parents including the folder
		foreach ($fileIds as $fileId) {
			$files = $this->rootFolder->getById($fileId);
			foreach ($files as $file) {
				/** @var string $resourceType */
				$resourceType = $file->getType();
				if ($resourceType === \OCP\Files\FileInfo::TYPE_FOLDER
					&& \is_numeric($file->getName())
					&& \preg_match("/^\/[\w\.@\-\']+\/thumbnails\/\d+$/", $file->getPath())
				) {
					//the file we are accessing seems to be a thumbnail
					//and we know the Id of its source file
					$sourceFileId = ( int ) $file->getName();
					if (empty($sourceFileId)) {
						break;
					}

					$fileIds [] = $sourceFileId;
					//now lets add all ids of the parents of the source file,
					//in case we need to block the access because of the sourcefile parents
					$sourceFileNodes = $this->rootFolder->getById($sourceFileId);
					foreach ($sourceFileNodes as $sourceFileNode) {
						$parentFolder = $sourceFileNode->getParent();
						$parentName = "";
						do {
							$path = $parentName;
							$fileIds [] = ( int ) $parentFolder->getId();
							$parentFolder = $parentFolder->getParent();
							$parentName = $parentFolder->getName();
						} while ($parentName !== $path
								  && !\in_array($parentFolder->getId(), $fileIds));
					}
				}
			}
		}
		$fileIds = \array_unique($fileIds, SORT_NUMERIC);
		\sort($fileIds);
		$this->fileIds = $fileIds;
		return $fileIds;
	}

	/**
	 * Get the file ids that is currently being checked
	 */
	public function getFileIds() {
		return $this->fileIds;
	}
}
