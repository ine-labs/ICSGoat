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

use OCA\sharepoint\lib\ConfigMgmt;
use OCA\sharepoint\lib\Utils;
use OCP\App;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Template;

class AdminPanel implements ISettings
{

	/** @var App  */
	protected $app;
	/** @var IConfig  */
	protected $config;

	public function __construct(App $app, IConfig $config) {
		$this->app = $app;
		$this->config = $config;
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

		$tmpl = new Template('sharepoint', 'settings');
		$tmpl->assign('isAdminPage', true);
		$tmpl->assign('globalMounts', ConfigMgmt::get_global_mounts());
		$tmpl->assign('SPglobalSharingActive', $this->config->getAppValue('sharepoint', 'global_sharing', false));
		$tmpl->assign('personalActive', $this->config->getAppValue('sharepoint', 'allow_personal_mounts', false));
		$tmpl->assign('IEversion', Utils::checkIE());

		$cred = ConfigMgmt::getAdminGlobalCredentialsCached();
		$pass = '';

		if($cred !== false){
			if(ConfigMgmt::decryptPassword($cred['password']) !== ''){
				$pass = '******';
			}
		}

		$tmpl->assign('credentials', array('user'=>$cred['user'], 'password' => $pass));
		return $tmpl;
	}

}
