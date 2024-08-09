<?php
/**
 * @author Jan Ackermann <jackermann@owncloud.com>
 * @author Jannik Stehle <jstehle@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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

namespace OCA\OAuth2\Commands;

use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Utilities;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModifyClient extends Command {

	/** @var ClientMapper */
	private $clientMapper;

	public function __construct(ClientMapper $clientMapper) {
		parent::__construct();

		$this->clientMapper = $clientMapper;
	}

	protected function configure() {
		$this
			->setName('oauth2:modify-client')
			->setDescription('Modify OAuth2 client details')
			->addArgument(
				'name',
				InputArgument::REQUIRED,
				'Name of client'
			)
			->addArgument(
				'key',
				InputArgument::REQUIRED,
				'Key to be changed. Valid keys are : name, client-id, client-secret, redirect-url, allow-sub-domains, trusted'
			)
			->addArgument(
				'value',
				InputArgument::REQUIRED,
				'The new value of the key.'
			);
		;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$name = $input->getArgument('name');
		$key = $input->getArgument('key');
		$value = $input->getArgument('value');

		try {
			/** @var  \OCA\OAuth2\Db\Client  $client */
			$client = $this->clientMapper->findByName($name);
		} catch (DoesNotExistException $e) {
			$output->writeln("Client with name <$name> does not exist.");
			return 1;
		}

		$funcMapper = [
			'name' => 'setName',
			'client-id' => 'setIdentifier',
			'client-secret' => 'setSecret',
			'redirect-url' => 'setRedirectUri',
			'allow-sub-domains' => 'setAllowSubdomains',
			'trusted' => 'setTrusted',
		];

		if (!\array_key_exists($key, $funcMapper)) {
			$output->writeln("Key <$key> is not valid.");
			return 1;
		}

		if ($key === 'client-id' && \strlen($value) < 32) {
			throw new \InvalidArgumentException('The client id should be at least 32 characters long');
		}
		if ($key === 'client-secret' && \strlen($value) < 32) {
			throw new \InvalidArgumentException('The client secret should be at least 32 characters long');
		}
		if ($key === 'redirect-url' && !Utilities::isValidUrl($value)) {
			throw new \InvalidArgumentException('The redirect URL is not valid.');
		}
		if ($key === 'allow-sub-domains' && !\in_array($value, ['true', 'false'])) {
			throw new \InvalidArgumentException('Please enter true or false for allowed-sub-domains.');
		}
		if ($key === 'trusted' && !\in_array($value, ['true', 'false'])) {
			throw new \InvalidArgumentException('Please enter true or false for trusted.');
		}

		if ($key === 'trusted' || $key == 'allow-sub-domains') {
			$value = \filter_var($value, FILTER_VALIDATE_BOOLEAN);
		}

		if ($key === 'name') {
			try {
				$this->clientMapper->findByName($value);
				$output->writeln("Client name <$value> is already known.");
				return 1;
			} catch (DoesNotExistException $e) {
				// this is good - name is uniq
			}
		}

		if ($key === 'client-id') {
			try {
				$this->clientMapper->findByIdentifier($value);
				$output->writeln("Client <$value> is already known.");
				return 1;
			} catch (DoesNotExistException $ex) {
				// this is good - identifier is uniq;
			}
		}

		\call_user_func([$client, $funcMapper[$key]], $value);
		$this->clientMapper->update($client);
	}
}
