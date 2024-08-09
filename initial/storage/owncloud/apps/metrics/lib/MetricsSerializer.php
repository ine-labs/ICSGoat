<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright (C) 2019 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Metrics;

class MetricsSerializer {
	/**
	 * @var string
	 */
	private $userId;
	/**
	 * @var string
	 */
	private $userDisplayName;
	/**
	 * @var int
	 */
	private $lastLogin;
	/**
	 * @var int
	 */
	private $quotaUsed;
	/**
	 * @var int
	 */
	private $quotaFree;
	/**
	 * @var int
	 */
	private $quotaTotal;
	/**
	 * @var int
	 */
	private $files;
	/**
	 * @var int
	 */
	private $sessions;
	/**
	 * @var int
	 */
	private $sharesUser;
	/**
	 * @var int
	 */
	private $sharesGroup;
	/**
	 * @var int
	 */
	private $sharesLink;
	/**
	 * @var int
	 */
	private $sharesGuest;
	/**
	 * @var int
	 */
	private $sharesFederated;

	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @param string $userId
	 */
	public function setUserId(string $userId): void {
		$this->userId = $userId;
	}

	/**
	 * @return string
	 */
	public function getUserDisplayName(): string {
		return $this->userDisplayName;
	}

	/**
	 * @param string $userDisplayName
	 */
	public function setUserDisplayName(string $userDisplayName): void {
		$this->userDisplayName = $userDisplayName;
	}

	/**
	 * @return int
	 */
	public function getLastLogin(): int {
		return $this->lastLogin;
	}

	/**
	 * @param int $lastLogin
	 */
	public function setLastLogin(int $lastLogin): void {
		$this->lastLogin = $lastLogin;
	}

	/**
	 * @return int
	 */
	public function getQuotaUsed(): int {
		return $this->quotaUsed;
	}

	/**
	 * @param int $quotaUsed
	 */
	public function setQuotaUsed(int $quotaUsed): void {
		$this->quotaUsed = $quotaUsed;
	}

	/**
	 * @return int
	 */
	public function getQuotaFree(): int {
		return $this->quotaFree;
	}

	/**
	 * @param int $quotaFree
	 */
	public function setQuotaFree(int $quotaFree): void {
		$this->quotaFree = $quotaFree;
	}

	/**
	 * @return int
	 */
	public function getQuotaTotal(): int {
		return $this->quotaTotal;
	}

	/**
	 * @param int $quotaTotal
	 */
	public function setQuotaTotal(int $quotaTotal): void {
		$this->quotaTotal = $quotaTotal;
	}

	/**
	 * @return int
	 */
	public function getFiles(): int {
		return $this->files;
	}

	/**
	 * @param int $files
	 */
	public function setFiles(int $files): void {
		$this->files = $files;
	}

	/**
	 * @return int
	 */
	public function getSessions(): int {
		return $this->sessions;
	}

	/**
	 * @param int $sessions
	 */
	public function setSessions(int $sessions): void {
		$this->sessions = $sessions;
	}

	/**
	 * @return int
	 */
	public function getSharesUser(): int {
		return $this->sharesUser;
	}

	/**
	 * @param int $sharesUser
	 */
	public function setSharesUser(int $sharesUser): void {
		$this->sharesUser = $sharesUser;
	}

	/**
	 * @return int
	 */
	public function getSharesGroup(): int {
		return $this->sharesGroup;
	}

	/**
	 * @param int $sharesGroup
	 */
	public function setSharesGroup(int $sharesGroup): void {
		$this->sharesGroup = $sharesGroup;
	}

	/**
	 * @return int
	 */
	public function getSharesLink(): int {
		return $this->sharesLink;
	}

	/**
	 * @param int $sharesLink
	 */
	public function setSharesLink(int $sharesLink): void {
		$this->sharesLink = $sharesLink;
	}

	/**
	 * @return int
	 */
	public function getSharesGuest(): int {
		return $this->sharesGuest;
	}

	/**
	 * @param int $sharesGuest
	 */
	public function setSharesGuest(int $sharesGuest): void {
		$this->sharesGuest = $sharesGuest;
	}

	/**
	 * @return int
	 */
	public function getSharesFederated(): int {
		return $this->sharesFederated;
	}

	/**
	 * @param int $sharesFederated
	 */
	public function setSharesFederated(int $sharesFederated): void {
		$this->sharesFederated = $sharesFederated;
	}
}
