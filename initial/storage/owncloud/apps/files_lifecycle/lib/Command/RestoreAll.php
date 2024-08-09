<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
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

use OCA\Files_Lifecycle\RestoreProcessor;
use OCP\IUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RestoreAll
 *
 * Command to restore all files in the system to essentially uninstall lifecycle
 *
 * @package OCA\Files_Lifecycle\Command
 */
class RestoreAll extends Command {
	/**
	 * @var RestoreProcessor
	 */
	protected $restoreProcessor;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * @var ProgressBar|null
	 */
	protected $progress = null;

	/**
	 * Restore constructor.
	 *
	 * @param RestoreProcessor $restoreProcessor
	 */
	public function __construct(
		RestoreProcessor $restoreProcessor
	) {
		parent::__construct();
		$this->restoreProcessor = $restoreProcessor;
	}

	/**
	 * Setup the command
	 *
	 * @return void
	 */
	public function configure() {
		$this
			->setName('lifecycle:restore-all')
			->setDescription(
				'Restore all archived files in the system back to their original locations'
			);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	public function execute(InputInterface $input, OutputInterface $output) {
		$this->output = $output;
		$output->writeln("<info>Starting restore of all files, for all users...</info>");
		$this->progress = new ProgressBar($output);
		$this->restoreProcessor->restoreAllFiles([$this, 'reportProgress']);
		$this->progress->finish();
		$this->output->writeln("");
		$this->output->writeln("<info>Done</info>");
		return 0;
	}

	/**
	 * @param int $total (the total number of users to be restored)
	 * @param int $num (the current user being processed)
	 * @param IUser $user
	 *
	 * @return void
	 */
	public function reportProgress($total, $num, IUser $user) {
		if ($this->progress === null) {
			$this->progress = new ProgressBar($this->output, $total);
			$this->progress->start($total);
		}
		$this->progress->advance();
		$this->progress->setMessage(
			'Restoring user: ' . $user->getUID() . ' - ' . $user->getDisplayName()
		);
	}
}
