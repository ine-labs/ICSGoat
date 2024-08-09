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

namespace OCA\User_Shibboleth;

use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\IUserSession;

class UserBackendFactory {
	const MODE_AUTOPROVISION = 'autoprovision';
	const MODE_NOT_ACTIVE = 'notactive';
	const MODE_SSO_ONLY = 'ssoonly';

	/** @var IConfig */
	private $config;

	/** @var ILogger */
	private $logger;

	/** @var IUserSession */
	private $userSession;

	/** @var IUserManager */
	private $userManager;

	/** @var ICacheFactory */
	private $cacheFactory;

	public function __construct(IConfig $config, ILogger $logger, IUserSession $userSession, IUserManager $userManager, ICacheFactory $cacheFactory) {
		$this->config = $config;
		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->cacheFactory = $cacheFactory;
	}

	/**
	 * @param string|null $mode the currently configured mode if it is null the legacy configuration will be checked
	 * @return string
	 */
	public function checkAndUpdateLegacyConfig($mode) {
		if (!$mode) {
			$this->logger->debug(
				"shibboleth mode not configured, checking 'shibboleth_active'",
				['app' => 'user_shibboleth']
			);
			if ($this->config->getAppValue('user_shibboleth', 'shibboleth_active')) {
				$mode = self::MODE_AUTOPROVISION;
				$this->logger->info(
					"converting 'shibboleth_active' configuration to '$mode' mode ",
					['app' => 'user_shibboleth']
				);
				$this->config->deleteAppValue('user_shibboleth', 'shibboleth_active');
				$this->config->setAppValue('user_shibboleth', 'mode', $mode);
			}
		}
		return $mode;
	}

	/**
	 * @return null|UserBackend
	 * @throws ConfigurationError
	 */
	public function createBackend() {
		$mode = $this->config->getAppValue('user_shibboleth', 'mode', self::MODE_NOT_ACTIVE);
		$mode = $this->checkAndUpdateLegacyConfig($mode);

		if ($mode === self::MODE_NOT_ACTIVE) {
			return null;
		}
		if (!\in_array($mode, [self::MODE_AUTOPROVISION, self::MODE_SSO_ONLY])) {
			// for now fall back to auto provisioning mode
			throw new ConfigurationError(
				"unknown shibboleth mode '$mode' configured"
			);
		}

		return new UserBackend($this->config, $this->logger, $this->userSession, $this->userManager, $this->cacheFactory->create('user_shibboleth'), $mode);
	}

	/**
	 * @deprecated use DI
	 * @return UserBackend
	 * @throws ConfigurationError
	 */
	public static function createForStaticLegacyCode() {
		$factory = new UserBackendFactory(
			\OC::$server->getConfig(),
			\OC::$server->getLogger(),
			\OC::$server->getUserSession(),
			\OC::$server->getUserManager(),
			\OC::$server->getMemCacheFactory()
		);
		return $factory->createBackend();
	}
}
