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

namespace OCA\User_Shibboleth\Controller;

use OCA\User_Shibboleth\UserBackendFactory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;

/**
 * Class AdminController is used to handle configuration changes on the admin
 * settings page
 *
 * @package OCA\User_Shibboleth\Controller
 */
class AdminController extends Controller {

	/**
	 * @var IConfig
	 */
	private $config;

	public function __construct($AppName, IRequest $request, IConfig $config) {
		parent::__construct($AppName, $request);
		$this->config = $config;
	}

	/**
	 * AJAX handler for getting the user_shibboleth mode
	 *
	 * @return DataResponse with the current mode
	 */
	public function getMode() {
		$mode = $this->config->getAppValue(
			'user_shibboleth', 'mode', UserBackendFactory::MODE_NOT_ACTIVE
		);
		return new DataResponse($mode);
	}

	/**
	 * AJAX handler for setting the user_shibboleth mode
	 * @param $mode string 'notactive', 'autoprovision' or 'ssoonly',
	 *        see UserBackendFactory constants
	 * @return DataResponse
	 */
	public function setMode($mode) {
		if ($mode === UserBackendFactory::MODE_NOT_ACTIVE
		 || $mode === UserBackendFactory::MODE_AUTOPROVISION
		 || $mode === UserBackendFactory::MODE_SSO_ONLY) {
			$this->config->setAppValue(
				'user_shibboleth', 'mode', $mode
			);
			return new DataResponse();
		} else {
			return new DataResponse(
				['message' => 'Could not change mode'],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
	}

	/**
	 * AJAX handler for getting the environment source config
	 *
	 * @return DataResponse with the current environment source config
	 */
	public function getEnvSourceConfig() {
		$envSourceShibbolethSession = $this->config->getAppValue(
			'user_shibboleth', 'env-source-shib-session', 'Shib-Session-ID'
		);
		$envSourceUid = $this->config->getAppValue(
			'user_shibboleth', 'env-source-uid', 'eppn'
		);
		$envSourceEmail = $this->config->getAppValue(
			'user_shibboleth', 'env-source-email', 'eppn'
		);
		$envSourceDisplayName = $this->config->getAppValue(
			'user_shibboleth', 'env-source-displayname', 'eppn'
		);
		$envSourceQuota = $this->config->getAppValue(
			'user_shibboleth', 'env-source-quota', 'quota'
		);
		return new DataResponse([
			'envSourceShibbolethSession' => $envSourceShibbolethSession,
			'envSourceUid' => $envSourceUid,
			'envSourceEmail' => $envSourceEmail,
			'envSourceDisplayName' => $envSourceDisplayName,
			'envSourceQuota' => $envSourceQuota,
		]);
	}
	/**
	 * AJAX handler for setting the environment source config
	 *
	 * @param $envSourceShibbolethSession string
	 * @param $envSourceUid string
	 * @param $envSourceEmail string
	 * @param $envSourceDisplayName string
	 * @param $envSourceQuota string
	 * @return DataResponse
	 */
	public function setEnvSourceConfig(
		$envSourceShibbolethSession,
		$envSourceUid,
		$envSourceEmail,
		$envSourceDisplayName,
		$envSourceQuota) {
		$this->config->setAppValue(
			'user_shibboleth', 'env-source-shib-session', $envSourceShibbolethSession
		);
		$this->config->setAppValue(
			'user_shibboleth', 'env-source-uid', $envSourceUid
		);
		$this->config->setAppValue(
			'user_shibboleth', 'env-source-email', $envSourceEmail
		);
		$this->config->setAppValue(
			'user_shibboleth', 'env-source-displayname', $envSourceDisplayName
		);
		$this->config->setAppValue(
			'user_shibboleth', 'env-source-quota', $envSourceQuota
		);
		return new DataResponse();
	}
}
