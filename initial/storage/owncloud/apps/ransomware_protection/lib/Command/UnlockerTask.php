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

use OCA\Ransomware_Protection\Blocker;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnlockerTask extends Command {

	/** @var Blocker $blocker */
	private $blocker;

	/** @var IUserManager $userManager */
	private $userManager;

	public function __construct(Blocker $blocker, IUserManager $userManager) {
		$this->blocker = $blocker;
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('ransomguard:unlock')
			->setDescription('Unlock client access (read-only) for the specified user.')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'User ID to unlock client access for'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$userId = $input->getArgument('user');

		if (!$this->userManager->userExists($userId)) {
			$output->writeln("<error>User <$userId> does not exist.</error>");
			return 1;
		}

		return $this->unlock($userId, $output);
	}

	private function unlock($userId, &$output) {
		$result = $this->blocker->unlock($userId);

		if ($result === false) {
			$output->writeln("<error>Could not unlock client access.</error>");
			return 1;
		}
		$output->writeln("<info>Account unlocked. Full client access is reenabled for user $userId</info>");

		return 0;
	}
}
