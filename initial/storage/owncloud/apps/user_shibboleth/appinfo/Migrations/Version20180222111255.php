<?php
namespace OCA\user_shibboleth\Migrations;

use OCA\User_Shibboleth\UserBackend;
use OCP\IUserManager;
use OCP\Migration\ISimpleMigration;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

/**
 * Fixes cases where multiple emails were inserted for one user in the accoutns table
 */
class Version20180222111255 implements ISimpleMigration {

	/**
	 * @param IOutput $out
	 */
	public function run(IOutput $out) {
		$db = \OC::$server->getDatabaseConnection();
		$userManager = \OC::$server->getUserManager();
		// Get accounts which have the issue
		$out->info("Loading accounts from Shibboleth which have multiple email adresses...");
		$accounts = $this->findAccountsWithMultipleEmails($db);
		$out->startProgress(\count($accounts));
		foreach ($accounts as $uid) {
			$uid = $uid['user_id'];
			$this->fixEmails($userManager, $uid);
			$out->advance();
		}
		$out->finishProgress();
	}

	/**
	 * Finds all accounts from shibboleth backend with mutli value emails
	 * @param IDBConnection $db
	 * @return array
	 */
	protected function findAccountsWithMultipleEmails(IDBConnection $db) {
		$query = $db->getQueryBuilder();
		$result = $query->select('user_id')
			->from('accounts')
			->where($query->expr()->like('email', $query->expr()->literal('%;%')))
			->andWhere($query->expr()->eq('backend', $query->expr()->literal(UserBackend::class)))
			->execute();
		$data = $result->fetchAll();
		$result->closeCursor();
		return $data;
	}

	/**
	 * replaces the email column for a user
	 * @param IUserManager $userManager
	 * @param $uid
	 */
	protected function fixEmails(IUserManager $userManager, $uid) {
		$user = $userManager->get($uid);
		if ($user === null) {
			return;
		}
		$emails = $user->getEMailAddress();
		$primary = \explode(';', $emails, 2)[0];
		$user->setEMailAddress($primary);
		\OC::$server->getLogger()->info("Email for user $uid changing from $emails to $primary");
	}
}
