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

use OCA\Files_Lifecycle\RestoreProcessor;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Restore
 *
 * @package OCA\Files_Lifecycle\Command
 */
class Restore extends Command {
	/**
	 * @var RestoreProcessor
	 */
	protected $restoreProcessor;

	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;

	/**
	 * @var IUserManager
	 */
	protected $userManager;

	/**
	 * Restore constructor.
	 *
	 * @param RestoreProcessor $restoreProcessor
	 * @param IRootFolder $rootFolder
	 * @param IUserManager $userManager
	 */
	public function __construct(
		RestoreProcessor $restoreProcessor,
		IRootFolder $rootFolder,
		IUserManager $userManager
	) {
		parent::__construct();
		$this->restoreProcessor = $restoreProcessor;
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
	}

	/**
	 * Setup the command
	 *
	 * @return void
	 */
	public function configure() {
		$this
			->setName('lifecycle:restore')
			->setDescription('Restore files from Archive to the original location')
			->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Enter path to a folder or to a single file'
			);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 *
	 */
	public function execute(InputInterface $input, OutputInterface $output) {
		// Todo Check ltrim
		$path = \ltrim($input->getArgument('path'), '/');
		$parts = \explode('/', $path);
		$user = $this->userManager->get($parts[0]);
		if ($user == null
			|| $this->userManager->get($parts[0])->getUID() != $parts[0]
		) {
			$output->writeln(
				"Invalid Archive folder. No Folder found for User {$parts[0]}"
			);
			return;
		}
		$userID = $user->getUID();
		if (!isset($parts[1]) || $parts[1] != "archive") {
			$output->writeln(
				"Invalid Path. Path must start with /$userID/archive"
			);
			return;
		}

		$this->restoreProcessor->restoreFileFromPath($path, $user);
		$output->writeln("Restoring files for path /{$path}");
	}
}
