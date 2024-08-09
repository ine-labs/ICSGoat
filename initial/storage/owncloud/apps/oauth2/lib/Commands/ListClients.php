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

use OC\Core\Command\Base;
use OCA\OAuth2\Db\ClientMapper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListClients extends Base {

	/** @var ClientMapper */
	private $clientMapper;

	public function __construct(ClientMapper $clientMapper) {
		parent::__construct();

		$this->clientMapper = $clientMapper;
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('oauth2:list-clients')
			->setDescription('Lists OAuth2 clients');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$clients  = $this->clientMapper->findAll();
		$clientsOutput = [];

		/** @var  \OCA\OAuth2\Db\Client  $client */
		foreach ($clients as $client) {
			$clientsOutput[$client->getName()] = [
				'name' => $client->getName(),
				'redirect-url' => $client->getRedirectUri(),
				'client-id' => $client->getIdentifier(),
				'client-secret' => $client->getSecret(),
				'allow-sub-domains' => $client->getAllowSubdomains(),
				'trusted' => $client->getTrusted(),
			];
		}
		parent::writeArrayInOutputFormat($input, $output, $clientsOutput, self::DEFAULT_OUTPUT_PREFIX, true);
		return 0;
	}
}
