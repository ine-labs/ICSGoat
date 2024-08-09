<?php
/**
 * ownCloud
 *
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_LDAP_Home;

use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\User_Proxy;
use \OCP\IConfig;

class LDAPReader extends User_Proxy {
	const DEFAULT_HOME_DIR_ATTRIBUTE = 'homeDirectory';
	const DEFAULT_ATTRIBUTE_MODE = 'uni';

	/** @var  IConfig */
	protected $ocConfig;

	/**
	 * Constructor
	 * Extended with functionality check, everything else like inherited
	 *
	 * @param array $serverConfigPrefixes containing the config Prefixes
	 * @param IConfig $ocConfig
	 */
	public function __construct($serverConfigPrefixes, IConfig $ocConfig) {
		Helper::checkOperability();
		$this->ocConfig = $ocConfig;
		$ldap = new LDAP();
		parent::__construct($serverConfigPrefixes, $ldap, $ocConfig);
	}

	/**
	 * gets the path to the user home folder from LDAP
	 *
	 * @param string $uid the internal username (as used in ownCloud)
	 * @return string|bool  string on success containing the path, false otherwise
	 */
	public function getUserSystemHome($uid) {
		$dn = $this->handleRequest($uid, 'username2dn', [$uid]);
		if (!$dn) {
			return false;
		}
		$prefix = $this->getFromCache($this->getUserCacheKey($uid));

		//Check whether the value is cached
		$cacheKey = 'getUserSystemHome-'.$uid;
		/** @var \OCA\User_LDAP\Connection $connection */
		$connection = $this->getAccess($prefix)->connection;
		$userSystemHome = $connection->getFromCache($cacheKey);
		if ($userSystemHome) {
			return $userSystemHome;
		}

		//determine the user's home
		$homeSet = $this->handleRequest(
			$uid, 'readAttribute', [$dn, $this->getHomeAttribute($prefix)]
		);
		if (\is_array($homeSet) && isset($homeSet[0])) {
			$userSystemHome = $homeSet[0];
		} else {
			$userSystemHome = false;
		}

		$connection->writeToCache($cacheKey, $userSystemHome);
		return $userSystemHome;
	}

	/**
	 * Retrieves the home attribute from the appconfig dependent on the
	 * configured mode and, if applicable, LDAP configuration
	 *
	 * @param string $prefix
	 * @return string
	 */
	private function getHomeAttribute($prefix) {
		//Mode: one attribute for all servers ('uni') or specific settings for
		//all servers ('spec')
		$mode = $this->ocConfig->getAppValue(
			'files_ldap_home', 'AttributeMode', self::DEFAULT_ATTRIBUTE_MODE
		);

		switch ($mode) {
			case 'spec':
				$configKey = 'AttributeS-'.$prefix;
				break;
			case 'uni':
			default:
				$configKey = 'Attribute';
				break;
		}

		$attribute = $this->ocConfig->getAppValue(
			'files_ldap_home', $configKey, self::DEFAULT_HOME_DIR_ATTRIBUTE
		);

		return $attribute;
	}
}
