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

namespace OCA\Ransomware_Protection\Controller;

use OCA\Ransomware_Protection\Blocker;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsController extends Controller {

	/** @var IConfig $config */
	protected $config;

	/** @var IL10N $l10n */
	protected $l10n;

	/** @var IUserSession */
	protected $userSession;

	/** @var Blocker $blocker */
	protected $blocker;

	/**
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IL10N $l10n
	 * @param IUserSession $userSession
	 * @param Blocker $blocker
	 */
	public function __construct(IRequest $request, IConfig $config, IL10N $l10n, IUserSession $userSession, Blocker $blocker) {
		parent::__construct('ransomware_protection', $request);
		$this->config = $config;
		$this->l10n = $l10n;
		$this->userSession = $userSession;
		$this->blocker = $blocker;
	}

	/**
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function lock() {
		$reason = $this->l10n->t('Triggered manually in Personal Settings');
		$this->blocker->lock($reason);
		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function unlock() {
		$this->blocker->unlock();
		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 * @param bool $status
	 * @return DataResponse
	 */
	public function lockingEnabled($status) {
		$value = $status ? '1' : '0';
		$this->config->setAppValue(
			'ransomware_protection',
			'lockingEnabled',
			$value
		);
		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 */
	public function personalSettings() {
		$userId = $this->userSession->getUser()->getUID();
		$lockedTimestamp = (int)$this->config->getUserValue(
			$userId,
			'ransomware_protection',
			'locked',
			0
		);
		$lockedReason = $this->config->getUserValue(
			$userId,
			'ransomware_protection',
			'locked_reason',
			''
		);
		$params = [
			'lockedTimestamp' => $lockedTimestamp,
			'lockedReason' => $lockedReason
		];
		return new TemplateResponse('ransomware_protection', 'settings.personal', $params, 'blank');
	}

	public function adminSettings() {
		$lockingEnabled = (bool)$this->config->getAppValue(
			'ransomware_protection',
			'lockingEnabled',
			Blocker::LOCKING_ENABLED_DEFAULT
		);
		$params = [
			'lockingEnabled' => $lockingEnabled ? true : false
		];
		return new TemplateResponse('ransomware_protection', 'settings.admin', $params, 'blank');
	}
}
