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

use OCA\Ransomware_Protection\Scanner;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScannerTask extends Command {

	/** @var Scanner $scanner*/
	private $scanner;

	/** @var IUserManager $userManager */
	private $userManager;

	public function __construct(Scanner $scanner, IUserManager $userManager) {
		$this->scanner = $scanner;
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('ransomguard:scan')
			->setDescription('Scan your files for suspicious activities.')
			->addArgument(
				'timestamp',
				InputArgument::REQUIRED,
				'Timestamp after which to check'
			)
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'User ID to scan files for'
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

		return $this->scan($timestamp, $userId, $output);
	}

	private function scan($timestamp, $userId, &$output) {
		$result = $this->scanner->getItems($timestamp, $userId);

		if (\count($result)) {
			$table = new Table($output);
			$table
				->setHeaders(['File Id','Name','Path','Date','Type','Current Path'])
				->setRows($result)
			;
			$table->render();
		} else {
			$output->writeln("<info>No suspicious files found.</info>");
		}

		return 0;
	}
}
