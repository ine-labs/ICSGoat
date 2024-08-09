<?php

/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright (C) 2017 ownCloud, GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\sharepoint;

use OC\Group\Group;
use OCA\sharepoint\lib\ConfigMgmt;
use OCP\App;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Template;

class PersonalPanel implements ISettings
{

	/** @var App  */
	protected $app;
	/** @var IUserSession  */
	protected $userSession;
	/** @var IConfig  */
	protected $config;
	/** @var IGroupManager  */
	protected $groupManager;

	public function __construct(
		App $app,
		IUserSession $userSession,
		IConfig $config,
		IGroupManager $groupManager) {
		$this->app = $app;
		$this->userSession = $userSession;
		$this->config = $config;
		$this->groupManager = $groupManager;
	}

	public function getSectionID() {
		return 'storage';
	}

	public function getPriority() {
		return 20;
	}

	public function getPanel() {
		// Don't show real settings if in degraded state
		if (!class_exists('SoapClient') || !function_exists('curl_version')) {
			return null;
		}

		$adminMounts = array();
		$personalMounts = array();

		$mountList = ConfigMgmt::get_mounts_for_user(
			$this->userSession->getUser()->getUID(),
				array_keys($this->groupManager->getUserGroups($this->userSession->getUser())));
		if ($mountList !== false) {
			while(($row = $mountList->fetchRow()) !== false) {
				$adminMounts[] = $row;
			}
		}

		$personal_enabled = $this->config->getAppValue('sharepoint', 'allow_personal_mounts', false);
		if ($personal_enabled) {
			$personalMountList = ConfigMgmt::get_personal_mounts_per_user($this->userSession->getUser()->getUID());
			if ($personalMountList !== false) {
				while(($row = $personalMountList->fetchRow()) !== false) {
					$personalMounts[] = $row;
				}
			}
		}

		$tmpl = new Template('sharepoint', 'personal_settings');
		$tmpl->assign('isAdminPage', false);
		$tmpl->assign('user', $this->userSession->getUser());
		$tmpl->assign('globalMounts', $adminMounts);
		$tmpl->assign('personalMounts', $personalMounts);
		$tmpl->assign('personal_mounts_enabled', $this->config->getAppValue('sharepoint', 'allow_personal_mounts', false));

		$credentials = ConfigMgmt::get_password_for_user($this->userSession->getUser()->getUID());
		$user = '';
		$pass = '';
		if($credentials !== false){
			$user = $credentials[0];
			$pass = '';
			if($credentials[1] !== ''){
				$pass = '******';
			}
		}
		$tmpl->assign('credentials', array($user, $pass));
		return $tmpl;
	}

}
