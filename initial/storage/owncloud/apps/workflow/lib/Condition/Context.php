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

namespace OCA\Workflow\Condition;

use OCA\DAV\Upload\UploadHome;
use OCP\Files\IMimeTypeDetector;
use OCP\IConfig;
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
	public const REQUEST_TYPE_UPLOAD = 'upload';
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

	/** @var IConfig */
	protected $config;

	/** @var array */
	protected $fileIds = [];

	/**
	 * @param IRequest $request
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param IMimeTypeDetector $mimeTypeDetector
	 * @param IConfig $config
	 */
	public function __construct(IRequest $request, IGroupManager $groupManager, IUserSession $userSession, IMimeTypeDetector $mimeTypeDetector, IConfig $config) {
		$this->request = $request;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->mimeTypeDetector = $mimeTypeDetector;
		$this->config = $config;
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
		if (
			// public.php
			$this->isScriptName('public.php') ||
			// share links via index.php/s/<token>
			($this->isScriptName('index.php') && \substr($this->request->getPathInfo(), 0, \strlen('/s/')) === '/s/')

		) {
			return self::REQUEST_TYPE_PUBLIC;
		}

		if ($this->isScriptName('remote.php')
			&& (
				($this->request->getPathInfo() === '/webdav' || \substr($this->request->getPathInfo(), 0, \strlen('/webdav/')) === '/webdav/') ||
			($this->request->getPathInfo() === '/dav/files' || \substr($this->request->getPathInfo(), 0, \strlen('/dav/files/')) === '/dav/files/') ||
			($this->request->getPathInfo() === '/dav/uploads' || \substr($this->request->getPathInfo(), 0, \strlen('/dav/uploads/')) === '/dav/uploads/')
			)
		) {
			return self::REQUEST_TYPE_WEBDAV;
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
	 * @return int
	 */
	public function getUploadSize() {
		if (\in_array($this->request->getMethod(), ['PUT', 'MOVE'])) {
			$uploadFolder = $this->getUploadFolderIfChunkingMove($this->request);

			if ($uploadFolder) {
				return $uploadFolder->getSize();
			}

			$uploadSize = $this->request->getHeader('OC_TOTAL_LENGTH');
			if ($uploadSize !== null) {
				return (int) $uploadSize;
			}

			$uploadSize = $this->request->getHeader('CONTENT_LENGTH');
			if ($uploadSize !== null) {
				return (int) $uploadSize;
			}
		}

		return 0;
	}

	/**
	 * HACK: Chunking NG initially uploads to /$user/uploads/$randomid so
	 * no PUT is received here. To get fileinfo we parse the upload id from
	 * the URI build the path to the upload dir and then get it`s size etc...
	 *
	 * Would be nice to have a move hook after upload in core.
	 *
	 */
	private function getUploadFolderIfChunkingMove(\OCP\IRequest $request) {
		if ($request->getMethod() !== 'MOVE') {
			return null;
		}

		$user = $this->userSession->getUser();
		$uploadHomeName = (new UploadHome(['user' => $user]))->getName();
		$uid = $user->getUID();

		$uri = $this->request->getRequestUri();
		$matches = [];
		$matchCount = \preg_match("/.*\/([.\w+-]+)\/.file/", $uri, $matches);

		if ($matchCount === 1) {
			$uploadId = $matches[1];
			$uploadFolder = \OC::$server->getRootFolder()->get("/$uid/$uploadHomeName/$uploadId");

			return $uploadFolder;
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getUploadType() {
		$requestType = $this->getRequestType();
		if ($requestType === self::REQUEST_TYPE_WEBDAV || $requestType === self::REQUEST_TYPE_PUBLIC) {
			if ($this->request->getMethod() === 'PUT' || $this->getUploadFolderIfChunkingMove($this->request)) {
				return $this->mimeTypeDetector->detectPath($this->request->getPathInfo());
			}
		} elseif ($this->getRequestType(true) === self::REQUEST_TYPE_PUBLIC) {
			if ($this->request->getMethod() === 'POST') {
				$file = $this->request->getUploadedFile('file');
				if (isset($file['type']) && \is_string($file['type'])) {
					return $file['type'];
				}
			}
		} else {
			if (\in_array($this->request->getMethod(), ['POST', 'PUT'])) {
				$files = $this->request->getUploadedFile('files');
				if (isset($files['type'][0])) {
					return $files['type'][0];
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
	public function getServerAddress() {
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

		$brandedClients = $this->getBrandedClients();
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

	/**
	 * @param string $userAgent
	 * @param array $devices
	 * @return string
	 */
	protected function findDevice($userAgent, array $devices) {
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

		// Trailing wildcard only
		if (\substr($device, 0, 1) !== '*') {
			$beginning = \substr($device, 0, -1);
			return \substr($userAgent, 0, \strlen($beginning)) === $beginning;
		}

		// Leading wildcard only
		if (\substr($device, -1) !== '*') {
			$ending = \substr($device, 1);
			return \substr($userAgent, 0 - \strlen($ending)) === $ending;
		}

		// Double wildcard, find anywhere
		return \strpos($userAgent, \substr($device, 1, -1)) !== false;
	}

	/**
	 * Returns branded user agents in the config
	 *
	 * @return array
	 */
	public function getBrandedClients() {
		$config = $this->config->getSystemValue('workflow.branded_clients', []);
		if (empty($config) || !\is_array($config)) {
			return [];
		}

		return $config;
	}

	/*
	 * File related methods
	 */

	/**
	 * Set the file that is checked
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
		$fileIds = \array_filter($fileIds, function ($value) {
			return \is_int($value);
		});
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
