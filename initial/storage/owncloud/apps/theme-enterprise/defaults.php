<?php
/**
 * ownCloud
 *
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Jan-Christoph Borchardt <jan@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

class OC_Theme {
	private $l;
	private $config;

	private $enterpriseEntity;
	private $enterpriseName;
	private $enterpriseTitle;
	private $enterpriseBaseUrl;
	private $enterpriseDocBaseUrl;
	private $enterpriseSyncClientUrl;
	private $enterpriseSlogan;
	private $enterpriseLogoClaim;
	private $enterpriseMailHeaderColor;

	public function __construct() {
		$this->l = \OC::$server->getL10N('lib');
		$this->config = \OC::$server->getConfig();
		$version = \OCP\Util::getVersion();
		\OC::$server->getLicenseManager()->checkLicenseFor('theme-enterprise');

		$this->enterpriseEntity = "ownCloud GmbH";
		$this->enterpriseName = "ownCloud";
		$this->enterpriseHTMLName = "Current App";
		$this->enterpriseTitle = "ownCloud Enterprise Edition";
		$this->enterpriseBaseUrl = "https://owncloud.com";
		$this->enterpriseDocBaseUrl = "https://doc.owncloud.com";
		$this->enterpriseDocVersion = $version[0] . '.' . $version[1]; // used to generate doc links
		$this->enterpriseSyncClientUrl = "https://owncloud.com/products/desktop-clients";
		$this->enterpriseSlogan = "Store. Share. Work.";
		$this->enterpriseLogoClaim = "Enterprise Edition";
		$this->enterpriseMailHeaderColor = "#041e42";
	}

	public function getBaseUrl() {
		return $this->enterpriseBaseUrl;
	}

	public function getSyncClientUrl() {
		return $this->enterpriseSyncClientUrl;
	}

	public function getDocBaseUrl() {
		return $this->enterpriseDocBaseUrl;
	}

	public function getTitle() {
		return $this->enterpriseTitle;
	}

	public function getName() {
		return $this->enterpriseName;
	}

	public function getHTMLName() {
		return $this->enterpriseHTMLName;
	}

	public function getEntity() {
		return $this->enterpriseEntity;
	}

	public function getSlogan() {
		return $this->enterpriseSlogan;
	}

	public function getLogoClaim() {
		return $this->enterpriseLogoClaim;
	}

	public function getShortFooter() {
		$footer  = '<a href="' . $this->getBaseUrl() . '" target="_blank" rel="noreferrer">' . $this->getEntity() . '</a>';
		$footer .= ' &ndash; ' . $this->getSlogan();

		if ($this->getImprintUrl() !== '') {
			$footer .= '<span class="nowrap"> | <a href="' . $this->getImprintUrl() . '" target="_blank">' . $this->l->t('Imprint') . '</a></span>';
		}

		if ($this->getPrivacyPolicyUrl() !== '') {
			$footer .= '<span class="nowrap"> | <a href="'. $this->getPrivacyPolicyUrl() .'" target="_blank">'. $this->l->t('Privacy Policy') .'</a></span>';
		}

		return $footer;
	}

	public function getLongFooter() {
		$footer  = '<a href="' . $this->getBaseUrl() . '" target="_blank" rel="noreferrer">' . $this->getEntity() . '</a>';
		$footer .= ' &ndash; ' . $this->getSlogan();

		if ($this->getImprintUrl() !== '') {
			$footer .= '<span class="nowrap"> | <a href="' . $this->getImprintUrl() . '" target="_blank">' . $this->l->t('Imprint') . '</a></span>';
		}

		if ($this->getPrivacyPolicyUrl() !== '') {
			$footer .= '<span class="nowrap"> | <a href="'. $this->getPrivacyPolicyUrl() .'" target="_blank">'. $this->l->t('Privacy Policy') .'</a></span>';
		}

		return $footer;
	}

	public function getImprintUrl() {
		try {
			return $this->config->getAppValue('core', 'legal.imprint_url', '');
		} catch (\Exception $e) {
			return '';
		}
	}

	public function getPrivacyPolicyUrl() {
		try {
			return $this->config->getAppValue('core', 'legal.privacy_policy_url', '');
		} catch (\Exception $e) {
			return '';
		}
	}

	public function buildDocLinkToKey($key) {
		return $this->getDocBaseUrl() . '/server/' . $this->enterpriseDocVersion . '/go.php?to=' . $key;
	}

	public function getMailHeaderColor() {
		return $this->enterpriseMailHeaderColor;
	}
}
