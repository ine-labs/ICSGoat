<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HSMDaemon extends Command {

	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
		parent::__construct();
	}

	// TODO add route for hsmdaemon to post current secret
	// TODO add encrypt masterkey command / as option
	protected function configure() {
		$this
			->setName('encryption:hsmdaemon')
			->setDescription('hsmdaemon tool');
		$this->addOption(
			'export-masterkey',
			null,
			InputOption::VALUE_NONE,
			'export the private master key in base64'
		);
		$this->addOption(
			'import-masterkey',
			null,
			InputOption::VALUE_REQUIRED,
			'import a base64 encoded private masterkey'
		);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		/** @var string|null $hsmUrl */
		$hsmUrl = $this->config->getAppValue('encryption', 'hsm.url');
		if (\is_string($hsmUrl) && $hsmUrl !== '') {
			if ($input->getOption('export-masterkey')) {
				$manager = \OC::$server->getEncryptionKeyStorage();
				$keyId = $this->config->getAppValue('encryption', 'masterKeyId').'.privateKey';
				$key = $manager->getSystemUserKey($keyId, \OC::$server->getEncryptionManager()->getDefaultEncryptionModuleId());
				// FIXME key might be too long to encrypt in one piece
				$output->writeln("current masterkey (base64 encoded): '".\base64_encode($key)."'");
			}
		} else {
			$output->writeln("<error>hsm.url not set</error>");
		}
	}
}
