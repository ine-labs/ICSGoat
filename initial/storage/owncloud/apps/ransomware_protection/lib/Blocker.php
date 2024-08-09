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

namespace OCA\Ransomware_Protection;

use OC\AppFramework\Http\Request;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

class Blocker {
	public const LOCKING_ENABLED_DEFAULT = true;

	/** @var IRequest*/
	protected $request;

	/** @var IConfig */
	protected $config;

	/** @var IL10N */
	protected $l10n;

	/** @var IUserSession */
	protected $userSession;

	/**
	 *
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IL10N $l10n
	 * @param IUserSession $userSession
	 */
	public function __construct(IRequest $request, IConfig $config, IL10N $l10n, IUserSession $userSession) {
		$this->request = $request;
		$this->config = $config;
		$this->l10n = $l10n;
		$this->userSession = $userSession;
	}

	/**
	 * Check if account is locked by ransomware_protection
	 *
	 * @return bool
	 */
	public function isLocked() {
		// never lock occ
		if (\OC::$CLI) {
			return false;
		}
		$locked = (int)$this->config->getUserValue(
			$this->getUserId(),
			'ransomware_protection',
			'locked',
			0
		);
		return $locked > 0;
	}

	/**
	 * Check if account is locked by ransomware_protection
	 * and client is an ownCloud client
	 *
	 * @return bool
	 */
	public function isLockedAndClient() {
		if (!$this->isLocked()) {
			return false;
		}
		// no client, when this is an AJAX request
		// see https://github.com/owncloud/core/issues/22020#issuecomment-177996751
		if (\in_array('XMLHttpRequest', \explode(',', $this->request->getHeader('X-Requested-With')), true)) {
			return false;
		}
		return true;
	}

	/**
	 * Lock account
	 *
	 * @param string|array $reason (optional)
	 * @param string $userId (optional)
	 * @return bool
	 */
	public function lock($reason = '', $userId = null) {
		if (!list($reasonString, $userId) = $this->processBlockParams($reason, $userId)) {
			return false;
		}

		$this->config->setUserValue($userId, 'ransomware_protection', 'locked', \time());
		$this->config->setUserValue($userId, 'ransomware_protection', 'locked_reason', $reasonString);

		Activity::publishBlockerEvent(Activity::TYPE_LOCKED, $userId, $reason);

		return true;
	}

	/**
	 * Publish blocked file
	 *
	 * @param string|array $reason (optional)
	 * @param string $userId (optional)
	 * @return boolean
	 */
	public function block($reason = '', $userId = null) {
		if (!isset($userId)) {
			$userId = $this->getUserId();
			if ($userId === null) {
				return false;
			}
		}

		Activity::publishBlockerEvent(Activity::TYPE_BLOCKED, $userId, $reason);

		return true;
	}

	/**
	 * Process parameters for locking/blocking
	 *
	 * @param string|array $reason
	 * @param string|null $userId
	 * @return array|boolean
	 */
	protected function processBlockParams($reason, $userId) {
		if (!isset($userId) && (!$userId = $this->getUserId())) {
			return false;
		}

		$reasonString = '';
		// Blacklist match returns an array with pattern and path
		// which are passed by StorageWrapper
		if (isset($reason['pattern'], $reason['path'])) {
			$reasonString = $this->l10n->t(
				'Found pattern %s for path %s',
				[$reason['pattern'], $reason['path']]
			);
		} elseif (\is_string($reason)) {
			$reasonString = $reason;
		}

		return [$reasonString, $userId];
	}

	/**
	 * Unlock account
	 *
	 * @param string $userId (optional)
	 * @return bool
	 */
	public function unlock($userId = null) {
		if (!isset($userId)) {
			$userId = $this->getUserId();
			if ($userId === null) {
				return false;
			}
		}

		$this->config->deleteUserValue($userId, 'ransomware_protection', 'locked');
		$this->config->deleteUserValue($userId, 'ransomware_protection', 'locked_reason');

		$reason = \OC::$CLI
			? $this->l10n->t('Unlocked by occ')
			: $this->l10n->t('Unlocked in security settings');
		Activity::publishBlockerEvent(Activity::TYPE_UNLOCKED, $userId, $reason);

		return true;
	}

	/**
	 * Get locked message, used by exceptions
	 *
	 * @return string
	 */
	public function getLockedMessage() {
		return $this->l10n->t('Locked by Ransomware Protection app. Unlock your account in the security settings panel.');
	}

	/**
	 * Get blocked message, used by exceptions
	 *
	 * @param string $pattern
	 * @return string
	 */
	public function getBlockedMessage($pattern) {
		return $this->l10n->t('File blocked by Ransomware Protection app. Found pattern %s', [$pattern]);
	}

	/**
	 * Lent from OCA\DAV\Files\BrowserErrorPagePlugin
	 *
	 * @param IRequest $request
	 * @return bool
	 */
	public static function isBrowserRequest(IRequest $request) {
		if ($request->getMethod() !== 'GET') {
			return false;
		}
		return $request->isUserAgent([
				Request::USER_AGENT_IE,
				Request::USER_AGENT_MS_EDGE,
				Request::USER_AGENT_CHROME,
				Request::USER_AGENT_FIREFOX,
				Request::USER_AGENT_SAFARI,
		]);
	}

	/**
	 * Shortcut to get user id
	 *
	 * @return null|string
	 */
	protected function getUserId() {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}
		return $user->getUID();
	}
}
