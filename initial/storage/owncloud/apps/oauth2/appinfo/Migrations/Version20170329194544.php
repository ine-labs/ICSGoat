<?php
namespace OCA\oauth2\Migrations;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCP\Migration\ISimpleMigration;
use OCP\Migration\IOutput;

/**
 * This adds the default client ids for ownCloud mobile and desktop clients
 */
class Version20170329194544 implements ISimpleMigration {
	private static $registry = [
		['Desktop Client', 'http://localhost:*', 'xdXOt13JKxym1B1QcEncf2XDkLAexMBFwiT9j6EfhhHFJhs2KM9jbjTmf8JBXE69', 'UBntmLjC2yYCeHwsyj73Uwo9TAaecAetRwMw0xYcvNL9yRdLSUi0hUAHfvCHFeFh'],
		['Android', 'oc://android.owncloud.com', 'e4rAsNUSIUs0lF4nbv9FmCeUkTlV9GdgTLDH1b5uie7syb90SzEVrbN7HIpmWJeD', 'dInFYGV33xKzhbRmpqQltYNdfLdJIfJ9L5ISoKhNoT9qZftpdWSP71VrpGR9pmoD'],
		['iOS', 'oc://ios.owncloud.com', 'mxd5OQDk6es5LzOzRvidJNfXLUZS2oN3oUFeXPP8LpPrhx3UroJFduGEYIBOxkY1', 'KFeFWWEZO9TkisIQzR3fo7hfiMXlOpaqP8CFuTbSHzV1TUuGECglPxpiVKJfOXIx']
	];
	/**
	 * @param IOutput $out
	 */
	public function run(IOutput $out) {
		// this is necessary to make the app work with OC <10.0.3
		\OC_App::loadApp('oauth2', false);
		foreach (self::$registry as list($name, $redirectUrl, $clientId, $secret)) {
			try {
				$this->addClient($name, $redirectUrl, $clientId, $secret);

				$out->info("The client <$name> has been added.");
			} catch (UniqueConstraintViolationException $ex) {
				$out->info("The client <$name> already known.");
			}
		}
	}

	/**
	 * @param string $name
	 * @param string $redirectUrl
	 * @param string $clientId
	 * @param string $secret
	 */
	protected function addClient($name, $redirectUrl, $clientId, $secret) {
		/** @var ClientMapper $mapper */
		$mapper = \OC::$server->query(ClientMapper::class);

		$client = new Client();
		$client->setIdentifier($clientId);
		$client->setSecret($secret);
		$client->setRedirectUri($redirectUrl);
		$client->setName($name);
		$client->setAllowSubdomains(false);

		$mapper->insert($client);
	}
}
