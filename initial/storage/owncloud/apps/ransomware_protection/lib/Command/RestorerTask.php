<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2017 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Ransomware_Protection\Command;

use OCA\Ransomware_Protection\Restorer;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestorerTask extends Command {

	/** @var Restorer $restorer */
	private $restorer;

	/** @var IUserManager $userManager */
	private $userManager;

	public function __construct(Restorer $restorer, IUserManager $userManager) {
		$this->restorer = $restorer;
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('ransomguard:restore')
			->setDescription('Restore your files to state at a given timestamp.')
			->addArgument(
				'timestamp',
				InputArgument::REQUIRED,
				'Timestamp to which files will be restored'
			)
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'User ID to restore files for'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$timestamp = (int)$input->getArgument('timestamp');
		$userId = $input->getArgument('user');

		if (empty($timestamp) || !\is_int($timestamp)) {
			$output->writeln("<error>Invalid timestamp</error>");
			return 1;
		}

		if (!$this->userManager->userExists($userId)) {
			$output->writeln("<error>User <$userId> does not exist.</error>");
			return 1;
		}

		return $this->restore($timestamp, $userId, $output);
	}

	private function restore($timestamp, $userId, &$output) {
		$result = $this->restorer->restore($timestamp, $userId);

		if (!empty($result['errors'])) {
			$output->writeln("<error>One or more errors occurred. Could not restore files.</error>");
			foreach ($result['errors'] as $msg) {
				$output->writeln("<error>    * $msg</error>");
			}
			return 1;
		}

		if (empty($result['restored'])) {
			foreach ($result['msg'] as $msg) {
				$output->writeln("<info>$msg</info>");
			}
			foreach ($result['restored'] as $msg) {
				$output->writeln("<info>$msg</info>");
			}
		} else {
			$output->writeln("<info>Files have been restored.</info>");
			foreach ($result['restored'] as $msg) {
				$output->writeln("<info>$msg</info>");
			}
		}

		return 0;
	}
}
