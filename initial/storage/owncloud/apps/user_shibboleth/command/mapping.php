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

use OCA\User_Shibboleth\Mapper\NoOpMapper;
use OCA\User_Shibboleth\UserBackendFactory;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Mapping extends Command {

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
			->setName('shibboleth:mapping')
			->setDescription('configure the mapping of environment variables')
			->addOption(
				'shib-session',
				's',
				InputOption::VALUE_OPTIONAL,
				'The environment variable used to detect a shibboleth session'
			)
			->addOption(
				'uid',
				'u',
				InputOption::VALUE_OPTIONAL,
				"Use this environment variable as the uid of the user.\n".
				"In '".UserBackendFactory::MODE_SSO_ONLY."' mode another user".
				'backend needs to be setup to provide email and display name.'
			)
			->addOption(
				'email',
				'e',
				InputOption::VALUE_OPTIONAL,
				"In '".UserBackendFactory::MODE_AUTOPROVISION."' mode, use ".
				"this environment variable as the email of the user.\n".
				"Ignored in '".UserBackendFactory::MODE_SSO_ONLY."' mode."
			)
			->addOption(
				'display-name',
				'd',
				InputOption::VALUE_OPTIONAL,
				"In '".UserBackendFactory::MODE_AUTOPROVISION."' mode, use ".
				"this environment variable as the display name of the user.\n".
				"Ignored in '".UserBackendFactory::MODE_SSO_ONLY."' mode."
			)
			->addOption(
				'quota',
				'Q',
				InputOption::VALUE_OPTIONAL,
				"In '".UserBackendFactory::MODE_AUTOPROVISION."' mode, use ".
				"this environment variable as the quota of the user.\n".
				"Ignored in '".UserBackendFactory::MODE_SSO_ONLY."' mode."
			);
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$shibSession = $input->getOption('shib-session');
		$uid         = $input->getOption('uid');
		$email       = $input->getOption('email');
		$displayName = $input->getOption('display-name');
		$quota       = $input->getOption('quota');

		if ($shibSession) {
			$this->config->setAppValue(
				'user_shibboleth', 'env-source-shib-session', $shibSession
			);
		}
		if ($uid) {
			$this->config->setAppValue(
				'user_shibboleth', 'env-source-uid', $uid
			);
		}
		if ($email) {
			$this->config->setAppValue(
				'user_shibboleth', 'env-source-email', $email
			);
		}
		if ($displayName) {
			$this->config->setAppValue(
				'user_shibboleth', 'env-source-displayname', $displayName
			);
		}
		if ($quota) {
			$this->config->setAppValue(
				'user_shibboleth', 'env-source-quota', $quota
			);
		}

		$output->writeln(
			'Checking "' . $this->config->getAppValue(
				'user_shibboleth', 'env-source-shib-session') .
			'" in environment to detect shibboleth session');
		$output->writeln(
			'Reading uid from "' . $this->config->getAppValue(
				'user_shibboleth', 'env-source-uid') . '"');
		$output->writeln(
			'Reading email from "' . $this->config->getAppValue(
				'user_shibboleth', 'env-source-email') . '"');
		$output->writeln(
			'Reading display name from "' . $this->config->getAppValue(
				'user_shibboleth', 'env-source-displayname') . '"');
		$output->writeln(
			'Reading quota from "' . $this->config->getAppValue(
				'user_shibboleth', 'env-source-quota') . '"');
		$mapper = $this->config->getAppValue(
			'user_shibboleth',
			'uid_mapper',
			NoOpMapper::class
		);

		$output->writeln("Using uid mapper: $mapper");
	}
}
