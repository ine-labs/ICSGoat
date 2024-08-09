<?php
/**
 * ownCloud
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright (C) 2015-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\User_Shibboleth\Command;

use OCA\User_Shibboleth\UserBackendFactory;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Mode extends Command {

	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		parent::__construct();
		$this->config = $config;
	}

	protected function configure() {
		$this
			->setName('shibboleth:mode')
			->setDescription('get or set mode of operation for user shibboleth')
			->addArgument(
				'mode',
				InputArgument::OPTIONAL,
				"set mode to '".
				UserBackendFactory::MODE_NOT_ACTIVE."', '".
				UserBackendFactory::MODE_AUTOPROVISION."' or '".
				UserBackendFactory::MODE_SSO_ONLY."'"
			);
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$mode = $input->getArgument('mode');
		$oldMode = $this->config->getAppValue('user_shibboleth', 'mode', '');
		if ($mode === UserBackendFactory::MODE_NOT_ACTIVE
			|| $mode === UserBackendFactory::MODE_AUTOPROVISION
			|| $mode === UserBackendFactory::MODE_SSO_ONLY) {
			$this->config->setAppValue('user_shibboleth', 'mode', $mode);
			$output->writeln("User Shibboleth mode changed from '$oldMode' to '$mode'");
		} elseif ($mode) {
			$output->writeln("Unknown mode '$mode'. Must be one of '".
				UserBackendFactory::MODE_NOT_ACTIVE."', '".
				UserBackendFactory::MODE_AUTOPROVISION."' or '".
				UserBackendFactory::MODE_SSO_ONLY."'");
		} else {
			$output->writeln("User Shibboleth mode is '$oldMode'");
		}
	}
}
