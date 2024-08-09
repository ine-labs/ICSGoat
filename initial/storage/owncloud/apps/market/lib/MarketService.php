<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Market;

use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use OCA\Market\Exception\LicenseKeyAlreadyAvailableException;
use OCA\Market\Exception\MarketException;
use OCP\App\AppManagerException;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\App\AppAlreadyInstalledException;
use OCP\App\AppNotFoundException;
use OCP\App\AppNotInstalledException;
use OCP\App\AppUpdateNotFoundException;
use OCP\Security\ISecureRandom;

class MarketService {
	/** @var HttpService */
	private $httpService;
	/** @var VersionHelper */
	private $versionHelper;
	/** @var IAppManager */
	private $appManager;
	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l10n;
	/** @var array */
	private $apps;
	/** @var array */
	private $categories;
	/** @var array */
	private $bundles;
	/** @var ISecureRandom  */
	private $rng;

	/**
	 * Service constructor.
	 *
	 * @param HttpService $httpService
	 * @param VersionHelper $versionHelper
	 * @param IAppManager $appManager
	 * @param IConfig $config
	 * @param IL10N $l10n
	 */
	public function __construct(
		HttpService $httpService,
		VersionHelper $versionHelper,
		IAppManager $appManager,
		IConfig $config,
		IL10N $l10n,
		ISecureRandom $rng
	) {
		$this->httpService = $httpService;
		$this->versionHelper = $versionHelper;
		$this->appManager = $appManager;
		$this->config = $config;
		$this->l10n = $l10n;
		$this->rng = $rng;
	}

	/**
	 * Check if we can install apps in general
	 *
	 * @return bool
	 */
	public function canInstall() {
		if (!\method_exists($this->appManager, 'canInstall')) {
			$appsFolder = \OC_App::getInstallPath();
			return $appsFolder !== null && \is_writable($appsFolder) && \is_readable($appsFolder);
		}
		return $this->appManager->canInstall();
	}

	/**
	 * Checks if the app with the given app id is installed
	 *
	 * @param string $appId
	 *
	 * @return bool
	 */
	public function isAppInstalled($appId) {
		$info = $this->getInstalledAppInfo($appId);
		return $info !== null;
	}

	/**
	 * Get application data provided by info.xml
	 *
	 * @param string $appId
	 *
	 * @return array|null
	 */
	public function getInstalledAppInfo($appId) {
		$apps = $this->appManager->getAllApps();
		foreach ($apps as $app) {
			$info = $this->appManager->getAppInfo($app);
			if (isset($info['id']) && $info['id'] === $appId) {
				return $info;
			}
		}

		return null;
	}

	/**
	 * Install an app for the given app id
	 *
	 * @param string $appId
	 * @param bool $skipMigrations whether to skip migrations
	 *
	 * @return void
	 *
	 * @throws AppAlreadyInstalledException
	 * @throws AppManagerException
	 * @throws \Exception
	 */
	public function installApp($appId, $skipMigrations = false) {
		if (!$this->canInstall()) {
			throw new \Exception("Installing apps is not supported because the app folder is not writable.");
		}

		$platformVersion = $this->versionHelper->getPlatformVersion(2);

		// Platform versions less than 10.5 don't require enterprise-key app
		if ($this->versionHelper->compare($platformVersion, "10.5", "<")) {
			$availableReleases = \array_column($this->getApps(), 'releases', 'id')[$appId];
			if (\array_shift($availableReleases)['license'] === 'ownCloud Commercial License') {
				$license = $this->getLicenseKey();
				if ($license === null) {
					throw new \Exception($this->l10n->t('Please enter a license-key in to config.php'));
				}
				if ($appId !== 'enterprise_key') {
					if (!$this->appManager->isEnabledForUser('enterprise_key')) {
						throw new \Exception($this->l10n->t('Please install and enable the enterprise_key app and enter a license-key in config.php first.'));
					}
					if (\class_exists('\OCA\Enterprise_Key\EnterpriseKey')) {
						$e = new \OCA\Enterprise_Key\EnterpriseKey($license, $this->config);
						if (!$e->check()) {
							throw new \Exception($this->l10n->t('Your license-key is not valid.'));
						}
					}
				}
			}
		}

		$info = $this->getInstalledAppInfo($appId);
		if ($info !== null) {
			throw new AppAlreadyInstalledException($this->l10n->t('App %s is already installed', [$appId]));
		}

		// download package
		$package = $this->downloadPackage($appId);
		$this->installPackage($package, $skipMigrations);
		$this->appManager->enableApp($appId);
	}

	/**
	 * Uninstall the app
	 *
	 * @param string $appId
	 *
	 * @throws AppManagerException
	 */
	public function uninstallApp($appId) {
		if (!$this->canInstall()) {
			throw new \Exception("Installing apps is not supported because the app folder is not writable.");
		}

		if ($this->appManager->isShipped($appId)) {
			throw new AppManagerException($this->l10n->t('Shipped apps cannot be uninstalled'));
		}

		if ($appId === 'market') {
			throw new AppManagerException($this->l10n->t('Market app can not uninstall itself.'));
		}

		if (!\OC_App::removeApp($appId)) {
			throw new AppManagerException($this->l10n->t('App (%s) could not be uninstalled. Please check the server logs.', [$appId]));
		}
	}

	/**
	 * Update the app
	 *
	 * @param string $appId
	 * @param string $targetVersion
	 *
	 * @throws AppManagerException
	 * @throws AppNotFoundException
	 * @throws AppNotInstalledException
	 * @throws AppUpdateNotFoundException
	 */
	public function updateApp($appId, $targetVersion = null) {
		if (!$this->canInstall()) {
			throw new \Exception("Installing apps is not supported because the app folder is not writable.");
		}

		$info = $this->getInstalledAppInfo($appId);
		if ($info === null) {
			throw new AppNotInstalledException($this->l10n->t('App (%s) is not installed', [$appId]));
		}

		// download package
		$package = $this->downloadPackage($appId, $targetVersion);
		$this->updatePackage($package);
	}

	/**
	 * Install downloaded package
	 *
	 * @param string $package package path
	 * @param bool $skipMigrations whether to skip migrations
	 *
	 * @return string appId
	 */
	public function installPackage($package, $skipMigrations = false) {
		return $this->appManager->installApp($package, $skipMigrations);
	}

	/**
	 * Update downloaded package
	 *
	 * @param string $package
	 *
	 * @return string appId
	 */
	public function updatePackage($package) {
		return $this->appManager->updateApp($package);
	}

	/**
	 * Get appinfo from package
	 *
	 * @param string $path
	 *
	 * @return string[] app info
	 */
	public function readAppPackage($path) {
		return $this->appManager->readAppPackage($path);
	}

	/**
	 * Get apps which need to be updated
	 *
	 * @return array
	 */
	public function getUpdates() {
		$result = [];
		$apps = $this->appManager->getAllApps();
		foreach ($apps as $app) {
			$info = $this->appManager->getAppInfo($app);
			if (isset($info['id'])) {
				try {
					$appId = $info['id'];
					$newVersions = $this->getAvailableUpdateVersions($appId);
					if ($newVersions['major'] !== false
						|| $newVersions['minor'] !== false
					) {
						$result[$app] = \array_merge(
							$newVersions,
							['id' => $appId]
						);
					}
				} catch (AppNotInstalledException $e) {
					// ignore exceptions thrown by getAvailableUpdateVersions
				} catch (AppNotFoundException $e) {
					// app is not published at marketplace - this is ok
				}
			}
		}

		return $result;
	}

	/**
	 * Get available minor and major versions as string or false
	 *
	 * @param string $appId
	 *
	 * @return string[]|bool[]
	 *
	 * @throws AppNotFoundException
	 * @throws AppNotInstalledException
	 */
	public function getAvailableUpdateVersions($appId) {
		$info = $this->getInstalledAppInfo($appId);
		if ($info === null) {
			throw new AppNotInstalledException($this->l10n->t('App (%s) is not installed', [$appId]));
		}
		$marketInfo = $this->getAppInfo($appId);
		if ($marketInfo === null) {
			throw new AppNotFoundException($this->l10n->t('App (%s) is not known at the marketplace.', [$appId]));
		}
		$currentVersion = (string) $info['version'];
		$major = $this->filterReleases($marketInfo, $currentVersion, true);
		$minor = $this->filterReleases($marketInfo, $currentVersion, false);
		return [
			'major' => $major,
			'minor' => $minor
		];
	}

	/**
	 * Choose between major and minor versions
	 * major is chosen only if it is allowed
	 * if it is allowed but does not exist - fallback to minor
	 *
	 * @param string[]|bool[] $updateVersions
	 * @param bool $isMajorAllowed
	 *
	 * @return string|false
	 */
	public function chooseCandidate($updateVersions, $isMajorAllowed) {
		$updateVersion = $isMajorAllowed
			? $updateVersions['major']
			: $updateVersions['minor'];
		// try to fallback to a minor release if there is no major release
		if ($isMajorAllowed === true && $updateVersion === false) {
			$updateVersion = $updateVersions['minor'];
		}
		return $updateVersion;
	}

	/**
	 * Verify if all requirements are met
	 *
	 * @param string [] $appInfo
	 *
	 * @return array[]
	 */
	public function getMissingDependencies($appInfo) {
		// bad hack - should use OCP
		$l10n = \OC::$server->getL10N('settings');
		$dependencyAnalyzer = new DependencyAnalyzer(new Platform($this->config), $l10n);

		return $dependencyAnalyzer->analyze($appInfo);
	}

	/**
	 * Get application data provided by marketplace
	 *
	 * @param string $appId
	 *
	 * @return mixed|null
	 */
	public function getAppInfo($appId) {
		$data = $this->getApps();
		$data = \array_filter(
			$data,
			function ($element) use ($appId) {
				return $element['id'] === $appId;
			}
		);
		if (empty($data)) {
			return null;
		}
		return \reset($data);
	}

	/**
	 * Get bundles data provided by marketplace
	 *
	 * @return array|mixed
	 * @throws AppManagerException
	 */
	public function getBundles() {
		if ($this->bundles !== null) {
			return $this->bundles;
		}
		$this->bundles = $this->httpService->getBundles();
		return $this->bundles;
	}

	/**
	 * Get categories data provided by marketplace
	 *
	 * @return array|mixed
	 *
	 * @throws AppManagerException
	 */
	public function getCategories() {
		if ($this->categories !== null) {
			return $this->categories;
		}
		$this->categories = $this->httpService->getCategories();
		return $this->categories;
	}

	/**
	 * Get app list from marketplace, optionally filtered by category
	 *
	 * @param string|null $category
	 *
	 * @return array|mixed
	 */
	public function listApps($category = null) {
		$apps = $this->getApps();
		if ($category !== null) {
			$apps = \array_filter(
				$apps,
				function ($app) use ($category) {
					return \in_array($category, $app['categories']);
				}
			);
		}
		return $apps;
	}

	public function getApiKey() {
		return $this->httpService->getApiKey();
	}

	public function startMarketplaceLogin() {
		$codeVerify = $this->rng->generate(
			64,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		);

		$codeChallenge = \base64_encode(\hash('sha256', $codeVerify));
		$this->config->setAppValue('market', 'code_verify', $codeVerify);
		$this->config->setAppValue('market', 'code_challenge', $codeChallenge);

		return $codeChallenge;
	}

	public function loginViaMarketplace($loginToken) {
		$codeVerify = $this->config->getAppValue('market', 'code_verify');
		$apiKey = $this->httpService->exchangeLoginTokenForApiKey($loginToken, $codeVerify);

		$this->setApiKey($apiKey);
		$this->config->deleteAppValue('market', 'code_verify');
		$this->config->deleteAppValue('market', 'code_challenge');

		return $apiKey;
	}

	/**
	 * Set api key
	 *
	 * @param string $apiKey
	 *
	 * @return bool
	 */
	public function setApiKey($apiKey) {
		if ($this->isApiKeyChangeableByUser()) {
			$this->config->deleteAppValue('market', 'key');
			$this->config->setAppValue('market', 'key', $apiKey);
			$this->httpService->invalidateCache();
			return true;
		}
		return false;
	}

	/**
	 * Check if ApiKey is valid
	 *
	 * @param string $apiKey
	 *
	 * @return bool
	 */
	public function isApiKeyValid($apiKey) {
		if ($apiKey === '') {
			return true;
		}
		try {
			$this->httpService->validateKey($apiKey);
			return true;
		} catch (\Exception $ex) {
			return false;
		}
	}

	/**
	 * ApiKey can only be changed by user if no key is configured in config.php
	 *
	 * @return bool
	 */
	public function isApiKeyChangeableByUser() {
		$configFileApiKey = $this->config->getSystemValue('marketplace.key', null);
		if ($configFileApiKey) {
			return false;
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasLicenseKey() {
		return $this->getLicenseKey() !== null;
	}

	/**
	 * @return string
	 *
	 * @throws LicenseKeyAlreadyAvailableException
	 * @throws MarketException
	 */
	public function requestLicenseKey() {
		if ($this->hasLicenseKey()) {
			throw new LicenseKeyAlreadyAvailableException();
		}

		$data = $this->httpService->getDemoKey();
		if (!\array_key_exists('license_key', $data)) {
			throw new MarketException('Marketplace did not return a demo license key.');
		}

		$demoLicenseKey = $data['license_key'];
		if (!$demoLicenseKey) {
			throw new MarketException('Marketplace returned an empty demo license key.');
		}

		$this->config->setAppValue('enterprise_key', 'license-key', $demoLicenseKey);
		$this->httpService->invalidateCache();
		return $demoLicenseKey;
	}

	public function invalidateCache() {
		$this->httpService->invalidateCache();
	}

	/**
	 * Returns the version for the app if an update is available
	 *
	 * @param string[][][] $marketInfo
	 * @param string $currentVersion
	 * @param bool $isMajorUpdate are major app updates allowed
	 *
	 * @return string|bool
	 *
	 * @throws AppNotFoundException
	 * @throws AppNotInstalledException
	 */
	private function filterReleases($marketInfo, $currentVersion, $isMajorUpdate) {
		$releases = $marketInfo['releases'];
		$releases = \array_filter(
			$releases,
			function ($r) use ($currentVersion, $isMajorUpdate) {
				$marketVersion = $r['version'];
				$isDifferentMajor = !$this->versionHelper->isSameMajorVersion(
					$marketVersion,
					$currentVersion
				);
				if ($isMajorUpdate !== $isDifferentMajor) {
					return false;
				}
				return \version_compare($marketVersion, $currentVersion, '>');
			}
		);
		\usort(
			$releases,
			function ($a, $b) {
				return \version_compare($a['version'], $b['version']);
			}
		);
		if (!empty($releases)) {
			return \array_pop($releases)['version'];
		}
		return false;
	}

	/**
	 * @return string|null
	 */
	private function getLicenseKey() {
		$licenseKey = $this->config->getSystemValue('license-key');
		if ($licenseKey) {
			return $licenseKey;
		}

		return $this->config->getAppValue('enterprise_key', 'license-key', null);
	}

	private function getApps() {
		if ($this->apps !== null) {
			return $this->apps;
		}
		$this->apps = $this->httpService->getApps();
		return $this->apps;
	}

	/**
	 * @param string $appId
	 * @param string | null $targetVersion
	 *
	 * @return string
	 *
	 * @throws AppManagerException
	 * @throws AppNotFoundException
	 * @throws AppUpdateNotFoundException
	 */
	private function downloadPackage($appId, $targetVersion = null) {
		$this->httpService->checkInternetConnection();
		$data = $this->getAppInfo($appId);
		if (empty($data)) {
			throw new AppNotFoundException($this->l10n->t('Unknown app (%s)', [$appId]));
		}

		$version = $this->versionHelper->getPlatformVersion();
		$release = \array_filter(
			$data['releases'],
			function ($element) use ($version, $targetVersion) {
				if ($targetVersion !== null
					&& $element['version'] !== $targetVersion
				) {
					return false;
				}
				$platformMin = $element['platformMin'];
				$platformMax = $element['platformMax'];
				$tooSmall = $this->versionHelper->compare($version, $platformMin, '<');
				$tooBig = $this->versionHelper->compare($version, $platformMax, '>');

				return $tooSmall === false && $tooBig === false;
			}
		);
		if (empty($release)) {
			throw new AppUpdateNotFoundException($this->l10n->t('No compatible version for %s', [$appId]));
		}
		\usort($release, function ($a, $b) {
			return \version_compare($b['version'], $a['version']);
		});
		$release = $release[0];
		$downloadLink = $release['download'];

		$pathInfo = \pathinfo($downloadLink);
		$extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
		$path = \OC::$server->getTempManager()->getTemporaryFile($extension);
		$this->httpService->downloadApp($downloadLink, $path);
		return $path;
	}
}
