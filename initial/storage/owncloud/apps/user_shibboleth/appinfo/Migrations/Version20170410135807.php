<?php
/**
 * ownCloud
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @copyright (C) 2017-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\user_shibboleth\Migrations;

use OC\User\Account;
use OC\User\AccountMapper;
use OCA\User_Shibboleth\UserBackend;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\ISimpleMigration;

class Version20170410135807 implements ISimpleMigration {
	/** @var IConfig */
	private $config;

	/**
	 * All users as stored in oc_shibboleth_user will be transferred into
	 * the account table. Information in oc_preferences will be respected,
	 *
	 * @param IOutput $out
	 *
	 * @since 10.0
	 */
	public function run(IOutput $out) {
		$connection = \OC::$server->getDatabaseConnection();
		$this->config = \OC::$server->getConfig();

		// Check if we even need to migrate anything, do the tables exist?
		if (!$connection->tableExists('shibboleth_user')) {
			$out->info('No shibboleth tables present, no need to migrate users');
			return;
		}

		$ro = new \ReflectionObject(\OC::$server);
		if ($ro->hasMethod('getAccountMapper')) {
			// 10.0.1 introduced \OC::$server->getAccountMapper()
			$accountMapper = \OC::$server->getAccountMapper();
		} else {
			// 10.0.0 fallback
			$accountMapper = new AccountMapper($connection);
		}

		$knownUsers = [];
		$userWithPass = [];

		$out->startProgress();
		$q = $connection->getQueryBuilder();
		$result = $q->select(['*'])->from('shibboleth_user')->execute();
		while ($row = $result->fetch()) {
			$userId = $row['eppn'];
			$email = $row['email'];
			$display = $row['displayname'];
			$davpass = $row['webdav_password'];

			try {
				$accountMapper->getByUid($userId);
				$knownUsers[] = $userId;
			} catch (DoesNotExistException $ex) {
				$a = new Account();
				$a->setEmail($email);
				$a->setDisplayName($display);
				$a->setUserId($userId);
				$a->setBackend(UserBackend::class);
				$a->setState(Account::STATE_ENABLED);
				$a->setLastLogin($this->findLastLoginForUser($userId));
				$home = $this->config->getSystemValue("datadirectory", \OC::$SERVERROOT . "/data") . '/' . $userId;
				$a->setHome($home);
				$this->setupAccount($a, $userId);
				$accountMapper->insert($a);
			}

			if ($davpass !== null) {
				$userWithPass[]= $userId;
			}
			$out->advance();
		}
		$out->finishProgress();

		if (\count($knownUsers) > 0) {
			$out->info("Following users are already know to the system - no migration took place.");
			foreach ($knownUsers as $u) {
				$out->info(" - $u");
			}
		}
		if (\count($userWithPass) > 0) {
			$out->info("Following users have a WebDAV password setup. Please inform the user that the password will no longer work.");
			foreach ($userWithPass as $u) {
				$out->info(" - $u");
			}
		}
	}

	/**
	 * @param Account $a
	 * @param string $uid
	 * @return Account
	 */
	private function setupAccount(Account $a, $uid) {
		list($hasKey, $value) = $this->readUserConfig($uid, 'core', 'enabled');
		if ($hasKey) {
			$a->setState(($value === 'true') ? Account::STATE_ENABLED : Account::STATE_DISABLED);
		}
		list($hasKey, $value) = $this->readUserConfig($uid, 'login', 'lastLogin');
		if ($hasKey) {
			$a->setLastLogin($value);
		}
		list($hasKey, $value) = $this->readUserConfig($uid, 'settings', 'email');
		if ($hasKey) {
			$a->setEmail($value);
		}
		list($hasKey, $value) = $this->readUserConfig($uid, 'files', 'quota');
		if ($hasKey) {
			$a->setQuota($value);
		}
		return $a;
	}

	/**
	 * @param string $uid
	 * @param string $app
	 * @param string $key
	 * @return array
	 */
	private function readUserConfig($uid, $app, $key) {
		$keys = $this->config->getUserKeys($uid, $app);
		if (isset($keys[$key])) {
			$enabled = $this->config->getUserValue($uid, $app, $key);
			return [true, $enabled];
		}
		return [false, null];
	}

	/**
	 * Finds the last login for a user id from the preferences table
	 * @param string $userid
	 * @return int the timestamp of the last login where 0 = never
	 */
	private function findLastLoginForUser($userid) {
		// Defaults to 1, since they are in the shibboleth user table so they must have logged in once
		// This protects future migrations that use callForSeenUsers
		// However the user should have a lastLogin value in every case
		return $this->config->getUserValue($userid, 'login', 'lastLogin', 1);
	}
}
