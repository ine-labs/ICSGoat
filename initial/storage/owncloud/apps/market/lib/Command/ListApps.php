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

class ListApps extends Command {
	private $marketService;

	public function __construct(MarketService $marketService) {
		parent::__construct();
		$this->marketService = $marketService;
	}

	protected function configure() {
		$this
			->setName('market:list')
			->setDescription('Lists apps as available on the marketplace.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		try {
			$apps = $this->marketService->listApps();
		} catch (\Exception $ex) {
			$output->writeln("<error>{$ex->getMessage()} </error>");
			return 1;
		}

		\usort($apps, function ($a, $b) {
			return \strcmp($a['id'], $b['id']);
		});

		foreach ($apps as $app) {
			$output->writeln("{$app['id']}");
		}
		return 0;
	}
}
