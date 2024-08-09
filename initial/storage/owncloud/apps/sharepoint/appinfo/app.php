<?php
/**
 * ownCloud
 *
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

 $licenseManager = \OC::$server->getLicenseManager();
// check if license app is enabled
if ($licenseManager->checkLicenseFor('sharepoint')) {
    if (!class_exists('SoapClient') || !function_exists('curl_version')) {
        return;
    }
    $l = \OC::$server->getL10N('sharepoint');
    OCP\App::checkAppEnabled('sharepoint');

    // filesystem
    OCP\Util::connectHook('OC_Filesystem', 'post_initMountPoints', '\OCA\sharepoint\lib\Hooks', 'mount_storage');

    // Capture credentials
    OCP\Util::connectHook('OC_User', 'post_login', '\OCA\sharepoint\lib\Hooks', 'login');

    //Scripts
    \OCP\Util::addScript('sharepoint', 'app');
    \OCP\Util::addScript('sharepoint', 'sharepointUtils');
    \OCP\Util::addScript('sharepoint', 'rollingQueue');
    \OCP\Util::addScript('sharepoint', 'connectivity_check');

    \OCA\Files\App::getNavigationManager()->add(
        array(
            "id" => 'spmounts',
            "icon" => 'extstoragemounts',
            "appname" => 'sharepoint',
            "script" => 'list.php',
            "order" => 50,
            "name" => $l->t('SharePoint')
        )
    );
}
