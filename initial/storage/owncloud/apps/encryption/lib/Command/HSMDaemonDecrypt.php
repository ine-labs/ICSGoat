<?php
/**
 * @author Jannik Stehle <jstehle@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\Command;

use OCA\Encryption\Crypto\CryptHSM;
use OCA\Encryption\Exceptions\PrivateKeyMissingException;
use OCA\Encryption\JWT;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Util;
use OCP\AppFramework\Utility\ITimeFactory;
use Symfony\Component\Console\Input\InputArgument;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class HSMDaemonDecrypt extends Command {

	/** @var IClient */
	private $httpClient;
	/** @var IConfig */
	private $config;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var KeyManager */
	private $keyManager;
	/** @var CryptHSM */
	private $crypt;
	/** @var Util */
	private $util;

	/**
	 * @param IClientService $httpClient
	 * @param IConfig $config
	 * @param ITimeFactory $timeFactory
	 * @param KeyManager $keyManager
	 * @param CryptHSM $crypt
	 * @param Util $util
	 */
	public function __construct(IClientService $httpClient,
								IConfig $config,
								ITimeFactory $timeFactory,
								KeyManager $keyManager,
								CryptHSM $crypt,
								Util $util) {
		$this->httpClient = $httpClient->newClient();
		$this->config = $config;
		$this->timeFactory = $timeFactory;
		$this->keyManager = $keyManager;
		$this->crypt = $crypt;
		$this->util = $util;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('encryption:hsmdaemon:decrypt')
			->setDescription('Decrypt a base64 encoded string via hsmdaemon')
			->addArgument(
				'decrypt',
				InputArgument::REQUIRED,
				'The string to decrypt'
			);

		$this->addOption(
			'username',
			null,
			InputOption::VALUE_OPTIONAL,
			'The name of the user who is able to decrypt the provided string'
		);
		$this->addOption(
			'keyId',
			null,
			InputOption::VALUE_OPTIONAL,
			'The keyId which was used to encrypt the provided string'
		);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$hsmUrl = $this->config->getAppValue('encryption', 'hsm.url');
		if (!$hsmUrl || !\is_string($hsmUrl)) {
			$output->writeln("<error>hsm.url not set</error>");
			return 1;
		}

		$password = '';
		if ($input->getOption('username')) {
			$question = new Question('Please enter password for user "' . $input->getOption('username') . '": ');
			$question->setHidden(true);
			$password = $this->getHelper('question')->ask($input, $output, $question);
		}

		try {
			$keyId = $this->getKeyId($input, $password);
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}

		$response = $this->httpClient->post($hsmUrl . '/decrypt/' . $keyId, [
			'headers' => [
				'Authorization' => 'Bearer ' . JWT::token([
						'iss' => $this->config->getSystemValue('instanceid'),
						'aud' => 'hsmdaemon',
						'exp' => $this->timeFactory->getTime(),
					], $this->config->getAppValue('encryption', 'hsm.jwt.secret', 'secret'))
			],
			'body' => \base64_decode($input->getArgument('decrypt'))
		]);

		$decryptedStr = $response->getBody();
		$output->writeln("decrypted string (base64 encoded): '".\base64_encode($decryptedStr)."'");
	}

	/**
	 * Get the key id used for decryption
	 *
	 * @param InputInterface $input
	 * @param string $password
	 * @return false|string
	 * @throws MissingInputException|PrivateKeyMissingException
	 */
	private function getKeyId(InputInterface $input, string $password) {
		$keyId = $input->getOption('keyId');
		if ($keyId) {
			return $keyId;
		}

		$username = $input->getOption('username');
		if ($username) {
			return $this->getKeyIdFromUser($username, $password);
		}

		if ($this->util->isMasterKeyEnabled()) {
			return $this->getKeyIdFromMasterKey();
		}

		throw new MissingInputException('please provide either a keyId or a username');
	}

	/**
	 * Get the key id of a given user
	 *
	 * @param string $username
	 * @param string $password
	 * @return false|string
	 * @throws PrivateKeyMissingException
	 */
	private function getKeyIdFromUser(string $username, string $password) {
		$privateKey = $this->keyManager->getPrivateKey($username);
		return $this->crypt->decryptPrivateKey($privateKey, $password, $username);
	}

	/**
	 * Get the key id when encryption mode is set to master
	 *
	 * @return false|string
	 */
	private function getKeyIdFromMasterKey() {
		$masterKeyId = $this->config->getAppValue('encryption', 'masterKeyId');
		$masterKeyPassword = $this->config->getSystemValue('secret');
		$privateKey = $this->keyManager->getSystemPrivateKey($masterKeyId);
		return $this->crypt->decryptPrivateKey($privateKey, $masterKeyPassword, $masterKeyId);
	}
}
