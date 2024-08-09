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
use OCP\DB;
use OCP\IConfig;
use OCP\Util;

class Settings {
	/**
	 * @var string $Attribute
	 */
	public $Attribute;

	//caches check whether Attribute setting is written to DB
	protected static $isAttributeWritten = false;

	/** @var  IConfig */
	protected $ocConfig;

	//caches the config values
	private $config = [
		'defaultMountName' => Storage::HOME_FOLDER_NAME,
	];

	//keeps the default values
	private $defaults = [
		'AttributeMode' => LDAPReader::DEFAULT_ATTRIBUTE_MODE,
		'Attribute' => LDAPReader::DEFAULT_HOME_DIR_ATTRIBUTE,
		'MountName' => Storage::HOME_FOLDER_NAME,
	];

	/**
	 * @param \OCP\IConfig $ocConfig
	 */
	public function __construct(IConfig $ocConfig) {
		$this->ocConfig = $ocConfig;
	}

	/**
	 * returns a configuration value
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getConfigValue($name) {
		if (isset($this->config[$name])) {
			return $this->config[$name];
		}
		$def = isset($this->defaults[$name]) ? $this->defaults[$name] : null;
		$value = $this->ocConfig->getAppValue('files_ldap_home', $name, $def);
		if ($value !== null) {
			$this->config[$name] = $value;
		}

		return $value;
	}

	/**
	 * returns the name of the mount point
	 *
	 * @return string
	 */
	public function getMountName() {
		return $this->getConfigValue('MountName');
	}

	/**
	 * returns the default name of the mount point
	 *
	 * @return string
	 */
	public function getDefaultMountName() {
		return $this->getConfigValue('defaultMountName');
	}

	/**
	 * returns the LDAP attribute which holds the home path
	 *
	 * @return string
	 */
	public function getAttribute() {
		return $this->getConfigValue('Attribute');
	}

	/**
	 * returns how the attribute is operated
	 *
	 * @return string
	 */
	public function getAttributeMode() {
		return $this->getConfigValue('AttributeMode');
	}

	/**
	 * magic setter – sets config values
	 *
	 * @param string $name the config name, matches configkey in appconfig
	 * @param string $value the new value for the key
	 * @return void
	 * @throws \Exception when save not possible or allowed
	 */
	public function __set($name, $value) {
		Util::writeLog('files_ldap_home',
			'Attempting to set '.$name.' to '.$value, Util::DEBUG);
		if ($name === 'serverHosts') {
			//read only
			throw new \Exception('serverHosts is a read-only property');
		}
		if (isset($this->config[$name]) || isset($this->defaults[$name])) {
			$this->ocConfig->setAppValue('files_ldap_home', $name, $value);
			$this->config[$name] = $value;
			return;
		}
		if (\strpos($name, 'AttributeS-') !== false) {
			$this->ocConfig->setAppValue('files_ldap_home', $name, $value);
			$this->config[$name] = $value;
			return;
		}
		throw new \Exception('Could not set value for '.$name);
	}

	/**
	 * retrieves an array with per-server settings
	 *
	 * @return array in the form of:
	 * array(
	 *	array(
	 *		'prefix' => 's01',
	 *		'name' => 'hostname',
	 *		'attribute' => 'homeDirectory'
	 *	),
	 *	array(
	 *		'prefix' => 's02',
	 *		'name' => 'hostname2',
	 *		'attribute' => 'homePath'
	 *	),
	 * );
	 */
	public function getServerHosts() {
		$this->checkDBPrerequisites();

		//Following lines to ensure database compatibility
		$charLengthFunc = 'CHAR_LENGTH';
		$dbtype = $this->ocConfig->getSystemValue('dbtype');
		if ($dbtype === 'sqlite3' || \strpos($dbtype, 'sqlite') !== false) {
			$charLengthFunc = 'LENGTH';	//sqlite does not know CHAR_LENGTH
		}
		if ($dbtype === 'mssql') {
			$charLengthFunc = 'LEN';	//mssql does not know CHAR_LENGTH
		}

		//PostgreSQL cannot use aliases in Having clause. For better readability
		//they are inserted using $prefix and $prefix2
		$groupBy = 'GROUP BY `a`.`configkey`, `b`.`configkey`,
						`a`.`configvalue`, `b`.`configvalue`';
		$prefixDef  = 'SUBSTR(`a`.`configkey`, 1, '.$charLengthFunc.
			'(`a`.`configkey`) - 9)';
		$prefix2Def = 'SUBSTR(`b`.`configkey`, 12)';
		$prefix = $prefixDef;
		$prefix2 = $prefix2Def;

		//Sqlite and MySQL on the other site require to use aliases in Having
		//clause. So $prefix and $prefix2 need to be adjusted
		if ($dbtype === 'sqlite3'
			|| \strpos($dbtype, 'sqlite') !== false
			|| \strpos($dbtype, 'mysql') !== false) {
			$prefix = '`prefix`';
			$prefix2 = '`prefix2`';
		}

		//get a ready to use array. Looks complicated, but avoids fiddling
		//around in PHP, which would not be less ugly.
		//Explanation:
		//1) SELECT: we need the LDAP config prefix, (host)name and attribute
		//setting. Other fields are necessary for the HAVING part
		//2) FROM/JOIN: we get the data from user_ldap and files_ldap_home
		//settings, which is in the same table. Requires a JOIN
		//Unfortunately there is no direct match between both apps, so we cannot
		//use ON and need to rely on WHERE and HAVING
		//Things are harder because either there is already a setting for the
		//LDAP connection, or not. In latter case the default needs to be
		//retrieved
		//3) WHERE: we filter out only really necessary rows, which is good to
		//be done because of table keys.
		//4) HAVING: here is the pick logic:
		//case A) when prefixes match, we have specific settings for the LDAP
		//connection. Also ensure, that the correct configkey is selected
		//Because user_ldap allows an empty config prefix, this requires
		//an additional check via subquery. Ugly, but not avoidable.
		//case B) no specific setting, so take the default. In this case, the
		//prefixes do not match (except empty prefix, to neglect), the correct
		//configkey must be at hand and for the prefix no specific setting
		//must exist. The latter can only be checked with the subquery.
		//APPENDIX) two lengths are hardcoded:
		//9 ← ldap_host (stringlength)
		//12 ← AttributeS- (stringlength + 1 offset)
		$query = DB::prepare('
			SELECT DISTINCT
				'.$prefixDef.' AS `prefix`,
				'.$prefix2Def.' AS `prefix2`,
				`a`.`configvalue` AS `name`,
				`b`.`configvalue` AS `attribute`,
				`b`.`configkey`
				FROM `*PREFIX*appconfig` AS `a`, `*PREFIX*appconfig` AS `b`
				WHERE `a`.`appid` = ?
					AND `b`.`appid` = ?
					AND `a`.`configkey` LIKE ?
					AND (
						`b`.`configkey` LIKE ?
						OR `b`.`configkey` = ?
					)
				' . $groupBy . '
				HAVING (
					('.$prefix.' = '.$prefix2.'
						AND `b`.`configkey` LIKE ?
						OR (
							'.$prefix.' = ?
							AND '.$prefix.' NOT IN
								(SELECT
									SUBSTR(`configkey`, 12)
								FROM `*PREFIX*appconfig`
								WHERE `appid` = ?
									AND `configkey` LIKE ?)
						)
					)
					OR ('.$prefix.' <> '.$prefix2.'
						AND `b`.`configkey` = ? AND '.$prefix.' NOT IN
							(SELECT
								SUBSTR(`configkey`, 12)
							FROM `*PREFIX*appconfig`
							WHERE `appid` = ?
								AND `configkey` LIKE ?)
					)
				)
		');

		$serverHosts = $query->execute([
			'user_ldap', 'files_ldap_home', '%ldap_host',
			'AttributeS-%', 'Attribute', 'AttributeS-%',
			'', 'files_ldap_home', 'AttributeS-%',
			'Attribute', 'files_ldap_home', 'AttributeS-%'
			])->fetchAll();

		return $serverHosts;
	}

	/**
	 * Ensures that Attribute setting is written to the DB so that SQL query
	 * in getServerHosts() will work
	 */
	private function checkDBPrerequisites() {
		if (!self::$isAttributeWritten) {
			$configured = $this->ocConfig->getAppValue(
				'files_ldap_home', 'Attribute', 'NOT CONFIGURED');
			if ($configured === 'NOT CONFIGURED') {
				$this->Attribute = LDAPReader::DEFAULT_HOME_DIR_ATTRIBUTE;
			}
			self::$isAttributeWritten = true;
		}
	}
}
