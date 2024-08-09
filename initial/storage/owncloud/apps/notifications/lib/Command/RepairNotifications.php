<?php
/**
 * @author Jannik Stehle <jstehle@owncloud.com>
 * @author Jan Ackermann <jackermann@owncloud.com>
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

namespace OCA\Notifications\Command;

use OCA\Notifications\Handler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RepairNotifications extends Command {

	/** @var Handler */
	protected $handler;

	public static $availableSubjects = [
		'relativeLinks'
	];

	/**
	 * @param Handler $handler
	 */
	public function __construct(Handler $handler) {
		parent::__construct();
		$this->handler = $handler;
	}

	protected function configure() {
		$this
			->setName('notifications:repairNotifications')
			->setDescription('Repair existing notifications')
			->addArgument('subject', InputArgument::REQUIRED, 'Subject to repair')
		;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$subject = $input->getArgument('subject');

		if (!\in_array($subject, self::$availableSubjects)) {
			$output->writeln('Invalid subject');
			return 1;
		}

		$updatedNotificationsCount = $this->handler->removeBaseUrlFromAbsoluteLinks();

		$output->writeln("$updatedNotificationsCount notifications were updated");
		return 0;
	}
}
