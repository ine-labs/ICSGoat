<?php
/**
 * ownCloud Wopi
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @copyright 2018 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\WOPI\Commands;

use OC\Core\Command\Base;
use OCA\WOPI\Service\TokenService;
use OCP\IUserManager;
use OCP\Files\IRootFolder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetToken extends Base {
	/** @var IUserManager */
	private $userManager;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var TokenService */
	private $tokenService;

	public function __construct(
		IUserManager $userManager,
		IRootFolder $rootFolder,
		TokenService $tokenService
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->tokenService = $tokenService;
	}

	protected function configure() {
		$this
			->setName('wopi:get-token')
			->setDescription('Get the token for a given file')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'User id to be used - e.g. admin'
			)
			->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Path to the file - e.g. test.wopitest'
			)
			->addArgument(
				'domain',
				InputArgument::REQUIRED,
				'Domain to be used in the folderUrl - e.g. http://cloud.example.net/owncloud/index.php'
			)
			->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'output mode: json or env', 'json');
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$path = $input->getArgument('path');
		$user = $input->getArgument('user');
		$domain = $input->getArgument('domain');
		if (\is_array($domain)) {
			throw new \InvalidArgumentException('Only one domain is allowed.');
		}
		$userFolder = $this->rootFolder->getUserFolder($user);
		if (!$userFolder->nodeExists($path)) {
			throw new \InvalidArgumentException('File does not exist: ' . $userFolder->getFullPath($path));
		}
		$node = $userFolder->get($path);
		$folderUrl = "$domain/apps/files/";
		$fileId = $node->getId();

		$user = $this->userManager->get($user);
		$data = $this->tokenService->GenerateNewAuthUserAccessToken("$fileId", $folderUrl, $user);
		$data['fileId'] = $fileId;
		if ($input->getOption('output') === 'json') {
			$output->writeln(\json_encode($data));
		} else {
			$output->writeln("export WOPI_URL=\"$domain/apps/wopi/files/$fileId\"");
			$output->writeln("export WOPI_TOKEN=\"{$data['token']}\"");
			$output->writeln("export WOPI_TOKEN_TTL=\"{$data['expires']}\"");
		}
	}
}
