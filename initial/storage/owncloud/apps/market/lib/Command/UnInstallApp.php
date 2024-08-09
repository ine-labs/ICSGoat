<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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

namespace OCA\Market\Command;

use OCA\Market\MarketService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnInstallApp extends Command {

	/** @var MarketService */
	private $marketService;

	/** @var int  */
	private $exitCode = 0;

	public function __construct(MarketService $marketService) {
		parent::__construct();
		$this->marketService = $marketService;
	}

	protected function configure() {
		$this
			->setName('market:uninstall')
			->setDescription('Un-Install apps.')
			->addArgument(
				'ids',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'Ids of the apps'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!$this->marketService->canInstall()) {
			throw new \Exception("Un-Installing apps is not supported because the app folder is not writable.");
		}

		$appIds = $input->getArgument('ids');
		$appIds = \array_unique($appIds);

		if (!\count($appIds)) {
			$output->writeln("No appIds specified. Nothing to do.");
			return 0;
		}

		foreach ($appIds as $appId) {
			try {
				$output->writeln("$appId: Un-Installing ...");
				$this->marketService->uninstallApp($appId);
				$output->writeln("$appId: App uninstalled.");
			} catch (\Exception $ex) {
				$output->writeln("<error>$appId: {$ex->getMessage()}</error>");
				$this->exitCode = 1;
			}
		}
		return $this->exitCode;
	}
}
