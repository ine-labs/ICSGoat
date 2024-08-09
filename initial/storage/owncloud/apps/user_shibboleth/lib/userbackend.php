<?php
/**
 * ownCloud
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @author Krzesimir Nowak
 * @author Iago López Galeiras
 * @copyright (C) 2014-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\User_Shibboleth;

use OC\HintException;
use OCA\User_Shibboleth\Mapper\IMapper;
use OCA\User_Shibboleth\Mapper\NoOpMapper;
use OCP\Authentication\IApacheBackend;
use OCP\ICache;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\User\IProvidesEMailBackend;
use OCP\User\IProvidesQuotaBackend;
use OCP\UserInterface;

class UserBackend implements UserInterface, IApacheBackend, IUserBackend, IProvidesEMailBackend, IProvidesQuotaBackend {

	/** @var IConfig */
	private $config;

	/** @var ILogger */
	private $logger;

	/** @var IUserSession */
	private $userSession;

	/** @var IUserManager */
	private $userManager;

	/** @var ICache */
	private $cache;

	/** @var string */
	private $mode;

	/** @var array [string, UserInterface] */
	private $currentUidAndBackend;

	/**
	 * UserBackend constructor.
	 *
	 * @param IConfig $config
	 * @param ILogger $logger
	 * @param IUserSession $userSession
	 * @param IUserManager $userManager
	 * @param ICache $cache
	 * @param string $mode see UserBackendFactory::MODE_* constants
	 */
	public function __construct(IConfig $config, ILogger $logger, IUserSession $userSession, IUserManager $userManager, ICache $cache, $mode) {
		$this->config = $config;
		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->cache = $cache;
		$this->mode = $mode;
	}

	/**
	 * @return string
	 */
	public function getMode() {
		return $this->mode;
	}

	/**
	* Check if backend implements actions
	* @param array|int $actions bitwise-or'ed actions
	* @returns boolean
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function implementsActions($actions) {
		return (bool)(\OC\User\Backend::GET_DISPLAYNAME & $actions);
	}

	/**
	 * returns a counts of the users
	 *
	 * @return bool
	 */
	public function countUsers() {
		return false;
	}

	/**
	* delete a user
	* @param string $uid The username of the user to delete
	* @returns boolean
	*
	* Deletes a user
	*/
	public function deleteUser($uid) {
		return false;
	}

	/**
	 * Get a list of all users
	 *
	 * @param string $search
	 * @param integer|null $limit
	 * @param integer|null $offset
	 * @return string[] with all uids
	 */
	public function getUsers($search = '', $limit = null, $offset = null) {
		return [];
	}

	/**
	* check if a user exists
	* @param string $uid the username
	* @return boolean
	*/
	public function userExists($uid) {
		return false;
	}

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string|null display name
	 * @throws \InvalidArgumentException
	 * @throws \Exception if uid mapper class could not be found
	 */
	public function getDisplayName($uid) {
		// only read from env if user is not yet known (autoprovisioning)
		// or if the current users uid matches the given $uid
		if (empty($this->currentUidAndBackend) || $uid === $this->getCurrentUserId()[0]) {
			return $this->getDisplay();
		}
		return null;
	}

	/**
	 * Get a users email address
	 *
	 * @param string $uid The username
	 * @return string|null
	 * @throws \InvalidArgumentException
	 * @throws \Exception if uid mapper class could not be found
	 * @since 10.0
	 */
	public function getEMailAddress($uid) {
		// only read from env if user is not yet known (autoprovisioning)
		// or if the current users uid matches the given $uid
		if (empty($this->currentUidAndBackend) || $uid === $this->getCurrentUserId()[0]) {
			return $this->getEMailAddressFromEnv();
		}
		return null;
	}

	/**
	 * Get a users quota
	 *
	 * @param string $uid The username
	 * @return string|null
	 * @since 10.0.3
	 */
	public function getQuota($uid) {
		if ($uid === $this->getCurrentUserId()[0]) {
			return $this->getQuotaFromEnv();
		}
		return null;
	}

	/**
	 * Get a list of all display names
	 * @param string $search
	 * @param int|null $limit
	 * @param int|null $offset
	 * @returns array with  all displayNames (value) and the corresponding uids (key)
	 *
	 * Get a list of all display names and user ids.
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		return [];
	}

	/**
	 * Check if a user list is available or not
	 * @return boolean if users can be listed or not
	 */
	public function hasUserListings() {
		return false;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function isSessionActive() {
		// Is Shibboleth authentication active?
		if ($this->mode === UserBackendFactory::MODE_NOT_ACTIVE) {
			$this->logger->debug(
				'shibboleth not active', ['app' => __CLASS__]
			);
			return false;
		}

		// we are in a shibboleth session?
		if (!$this->getShibbolethSession()) {
			$this->logger->debug(
				'No Shibboleth session active.', ['app' => __CLASS__]
			);
			return false;
		}

		// we need the auth user to be set
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			$this->logger->debug(
				'PHP_AUTH_USER not found in _SERVER', ['app' => __CLASS__]
			);
			return false;
		}

		// uid in shibboleth scenarios typically is the eppn / eduPersonPrincipalName (a globally unique user name)
		$uidAndBackend = $this->getCurrentUserId();
		if (empty($uidAndBackend)) {
			$serverInfo = \var_export($_SERVER, true);
			$envSourceUid = $this->config->getAppValue(
				'user_shibboleth', 'env-source-uid', 'eppn'
			);
			$this->logger->error(
				"Failed to login because no uid ($envSourceUid) was provided: $serverInfo",
				['app' => __CLASS__]
			);
			//TODO show request id to the user so he can tell the admin what to look for
			throw new \OutOfBoundsException("Failed to login because no uid ($envSourceUid) was provided: $serverInfo");
		}

		return true;
	}

	/**
	 * @return string|null
	 */
	protected function getEMailAddressFromEnv() {
		$envSourceEmail = $this->config->getAppValue(
			'user_shibboleth', 'env-source-email', 'eppn'
		);
		// Splits if multi values are supplied
		$value = $this->get($envSourceEmail);
		$pieces = \explode(';', $this->get($envSourceEmail), 2);
		$primary = $pieces[0];
		if (\count($pieces) > 1) {
			$this->logger->debug("Multiple emails returned from environment: $value, using first: $primary");
		}
		return $primary;
	}

	/**
	 * @return string|null
	 */
	protected function getQuotaFromEnv() {
		$envSourceQuota = $this->config->getAppValue(
			'user_shibboleth', 'env-source-quota', 'quota'
		);
		return $this->get($envSourceQuota);
	}

	/**
	 * @return string|null
	 */
	protected function getDisplay() {
		$envSourceDisplayName = $this->config->getAppValue(
			'user_shibboleth', 'env-source-displayname', 'eppn'
		);
		return $this->get($envSourceDisplayName);
	}

	/**
	 * @return string
	 * JavaScript method.
	 */
	public function getLogoutAttribute() {
		if ($this->mode === UserBackendFactory::MODE_NOT_ACTIVE) {
			return '';
		}
		if ($this->getShibbolethSession() && $this->userSession->isLoggedIn()) {
			return "class='shibboleth-logout'";
		}

		return '';
	}

	/**
	 * Return the Shibboleth session
	 * @return string|null
	 */
	public function getShibbolethSession() {
		$envSourceShibbolethSession = $this->config->getAppValue(
			'user_shibboleth', 'env-source-shib-session', 'Shib-Session-ID'
		);
		return $this->get($envSourceShibbolethSession);
	}

	/**
	 * Return the id of the current user
	 * @return array [string uid, UserInterface backend]
	 */
	public function getCurrentUserId() {
		if ($this->currentUidAndBackend !== null) {
			return $this->currentUidAndBackend;
		}

		// get user id from environment
		$envSourceUid = $this->config->getAppValue(
			'user_shibboleth',
			'env-source-uid',
			'eppn'
		);
		$samlNameId = $this->get($envSourceUid);
		if ($samlNameId === null || $samlNameId === '') {
			return [];
		}

		// Apply config specific mapping
		// Default to a no operations mapper
		$mapper = $this->config->getAppValue(
			'user_shibboleth',
			'uid_mapper',
			NoOpMapper::class
		);

		// See if we can find the mapper - if not, shout!
		if (!\class_exists($mapper)) {
			$this->logger->error(
				"Shibboleth uid mapper class $mapper not found",
				['app' => __CLASS__]
			);
			return [];
		}
		/** @var IMapper $m */
		$m = new $mapper;
		if (!$m instanceof IMapper) {
			$this->logger->error(
				"Supplied shibboleth uid mapper not a valid IMapper: $mapper",
				['app' => __CLASS__]
			);
			return [];
		}

		try {
			/** @var string|null $mappedSamlNameId */
			$mappedSamlNameId = $m->map((string) $samlNameId);
		} catch (HintException $e) {
			// Configured mapper failed to map this incoming uid
			$this->logger->logException($e, ['app' => __CLASS__]);
			return [];
		}
		if ($mappedSamlNameId === null || $mappedSamlNameId === '') {
			$this->logger->error(
				"Supplied shibboleth uid mapper $mapper produced an emptystring for $samlNameId",
				['app' => __CLASS__]
			);
			return [];
		}

		// get stored mapping from cache - in case the uid is found on another backend
		// we don't want to have to always search the backends for this user so we'll remember
		$cachedUidAndBackend = $this->cache->get("saml-name-id-$mappedSamlNameId");
		if (\is_array($cachedUidAndBackend) && \count($cachedUidAndBackend) === 2 && $this->userManager->get($cachedUidAndBackend[0])) {
			$user = $this->userManager->get($cachedUidAndBackend[0]);
			$userBackend = null;
			foreach ($this->userManager->getBackends() as $backend) {
				if (\get_class($backend) === $cachedUidAndBackend[1]) {
					$userBackend = $backend;
					break;
				}
			}
			if ($user instanceof IUser && $userBackend instanceof UserInterface) {
				$this->currentUidAndBackend = [$user->getUID(), $userBackend];
				return $this->currentUidAndBackend;
			}
		}

		// normally we would just use the $samlNameId and the user_shibboleth backend to log in a user
		// but ldap or other backends might return the actual uuid for a login name
		$this->currentUidAndBackend = $this->determineBackendFor($mappedSamlNameId);

		if (\count($this->currentUidAndBackend) === 2) {
			// cache for a day, the lookup was expensive and we don't want to do it for every request
			$this->cache->set(
				"saml-name-id-$mappedSamlNameId",
				[
					$this->currentUidAndBackend[0],
					\get_class($this->currentUidAndBackend[1])
				],
				60 * 60 * 24);
		}

		return $this->currentUidAndBackend;
	}

	/**
	 * @param string $samlNameId
	 * @return array [string uid, UserInterface backend]
	 */
	private function determineBackendFor($samlNameId) {
		foreach ($this->userManager->getBackends() as $backend) {
			if ($backend === $this) {
				continue; // would use $samlNameId as $uid
			}
			$class = \get_class($backend);
			// FIXME the next line can return zombie999 for zombie99 because it does a prefix based search, needs a new api, or exact parameter. maybe prefix|medial|exact $matchtype? or a better yet a query object
			// TODO for now recommend any attribute that has a clear suffix like email or userprincipalname
			$this->logger->debug(
				"Searching Backend $class for $samlNameId", ['app' => 'user_shibboleth']
			);

			// $userIds might contain partial patches,
			// so we need to check for exact matches
			$matchedUserIds = $this->matchBackendUserIds($backend, $samlNameId);

			switch (\count($matchedUserIds)) {
				case 0:
					$this->logger->debug(
						"Backend $class returned no matching user for $samlNameId",
						['app' => __CLASS__]
					);
					break;
				case 1:
					$uid = $matchedUserIds[0];
					$this->logger->debug(
						"Backend $class returned $uid for $samlNameId",
						['app' => __CLASS__]
					);
					// Found the user in a different backend
					return [$uid, $backend];
				default:
					throw new \InvalidArgumentException("Backend $class returned more than one user for $samlNameId: " . \implode(', ', $matchedUserIds));
			}
		}

		// No other backend provides this uid. We are responsible only in autoprovisioning mode
		if ($this->mode === UserBackendFactory::MODE_AUTOPROVISION) {
			return [$samlNameId, $this];
		}

		return [];
	}

	/**
	 * @param UserInterface $backend
	 * @param string $samlNameId
	 * @return string[]
	 */
	private function matchBackendUserIds(UserInterface $backend, string $samlNameId) {
		// $userIds might contain partial matches,
		// so we need to check for exact matches
		$matches = [];
		$userIds = $backend->getUsers($samlNameId);
		foreach ($userIds as $uid) {
			if (\strcasecmp($uid, $samlNameId) === 0) {
				$matches[] = $uid;
				continue;
			}

			// also check email, username and additional user search terms
			$user = $this->userManager->get($uid);
			if ($user) {
				if (\strcasecmp($user->getUID(), $samlNameId) === 0 ||
					\strcasecmp($user->getEMailAddress(), $samlNameId) === 0 ||
					\strcasecmp($user->getUserName(), $samlNameId) === 0
				) {
					$matches[] = $uid;
					continue;
				}
				foreach ($user->getSearchTerms() as $term) {
					if (\strcasecmp($term, $samlNameId) === 0) {
						// a search term matches exactly
						$matches[] = $uid;
						break 2;
					}
				}
			}
		}

		// continue only with exact matches
		// remove duplicates
		return \array_unique($matches);
	}

	/**
	 * @param string $key
	 * @return null|string
	 */
	protected function get($key) {
		return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
	}

	/**
	 * FIXME move to utility class / App framework controller
	 */
	public static function setTimezone() {
		if (isset($_POST['timezone-offset'])) {
			if (\is_numeric($_POST['timezone-offset'])) {
				\OC::$server->getSession()->set('timezone', (int)$_POST['timezone-offset']);
			}
		}
		\OCP\JSON::success();
	}

	/**
	 * Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return boolean
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public function checkPassword($uid, $password) {
		return false;
	}

	/**
	 * Backend name to be shown in user management
	 *
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName() {
		return 'Shibboleth';
	}
}
