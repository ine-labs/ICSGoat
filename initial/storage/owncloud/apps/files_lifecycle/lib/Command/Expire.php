<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle\Command;

use OCA\Files_Lifecycle\ExpireProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Expire
 *
 * @package OCA\Files_Lifecycle\Command
 */
class Expire extends Command {

	/**
	 * @var ExpireProcessor
	 */
	protected $expireProcessor;

	/**
	 * Expire constructor.
	 *
	 * @param ExpireProcessor $expireProcessor
	 */
	public function __construct(ExpireProcessor $expireProcessor) {
		parent::__construct();
		$this->expireProcessor = $expireProcessor;
	}

	/**
	 * Set up the command
	 *
	 * @return void
	 */
	public function configure() {
		$this
			->setName('lifecycle:expire')
			->addOption('dryrun', 'd', InputOption::VALUE_OPTIONAL, 'Dont apply changes to the system', false)
			->setDescription('Expire files from Archive which have reached a certain age');
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
		$output->writeln('Start Expire Process for all Users.');
		// Just a test?
		$dryRun = $input->getOption('dryrun') !== false;
		if ($dryRun) {
			$output->writeln('<info>Running in dry run mode - no changes will be applied</info>');
		} else {
			$output->writeln('<info>Running in production mode - changes will be applied to the system!</info>');
		}
		$output->writeln('');
		$p = new ProgressBar($output);
		$p->start();
		$this->expireProcessor->expireAllUsers(
			function ($message) use ($p, $output) {
				$p->advance();
				$output->writeln($message);
			},
			$dryRun
		);
		$p->finish();
		$output->writeln('');
		$output->writeln('');
		if ($dryRun) {
			$output->writeln($p->getProgress() . ' files would have been expired.');
		} else {
			$output->writeln('Total files expired: ' . $p->getProgress());
			$output->writeln('Finished expire process successfully.');
		}
	}
}
