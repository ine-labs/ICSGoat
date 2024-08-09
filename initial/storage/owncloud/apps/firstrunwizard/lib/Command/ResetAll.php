<?php
/**
* @author Tom Needham <tom@owncloud.com>
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

namespace OCA\FirstRunWizard\Command;

use OCA\FirstRunWizard\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ResetAll
 *
 * @package OCA\FirstRunWizard\Command
 */
class ResetAll extends Command {
	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * ResetAll constructor.
	 *
	 * @param Config $config
	 */
	public function __construct(Config $config) {
		parent::__construct();
		$this->config = $config;
	}

	/**
	 * Setup the command
	 *
	 * @return int|null|void
	 */
	public function configure() {
		$this
			->setName('firstrunwizard:reset-all')
			->setDescription('Reset the first run wizard for all users');
	}

	/**
	 * Execute the command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 */
	public function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln('Resetting firstrunwizard for all users');
		$progress = new ProgressBar($output);
		$this->config->resetAllUsers(function ($user) use ($progress) {
			$progress->advance();
		});
		$progress->finish();
		$output->writeln("");
		$output->writeln("<info>Done</info>");
	}
}
