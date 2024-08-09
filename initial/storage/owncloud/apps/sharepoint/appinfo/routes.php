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


$this->create('sharepoint_list_applicable', 'ajax/applicable.php')
->actionInclude('sharepoint/ajax/applicable.php');
$this->create('sharepoint_common', 'ajax/common.php')
->actionInclude('sharepoint/ajax/common.php');
$this->create('sharepoint_dialog', 'ajax/dialog.php')
->actionInclude('sharepoint/ajax/dialog.php');
$this->create('sharepoint_credentials', 'ajax/saveCredential.php')
->actionInclude('sharepoint/ajax/saveCredential.php');
$this->create('sharepoint_setting_admin', 'ajax/settingsAdminAJAX.php')
->actionInclude('sharepoint/ajax/settingsAdminAJAX.php');
$this->create('sharepoint_settings_user', 'ajax/settingsUserAJAX.php')
->actionInclude('sharepoint/ajax/settingsUserAJAX.php');
$this->create('sharepoint_connectivityCheck', 'ajax/sharepointConnectivityCheck.php')
->actionInclude('sharepoint/ajax/sharepointConnectivityCheck.php');

OC_API::register('get', '/apps/sharepoint/api/v1/mounts',
array('\OCA\sharepoint\lib\Utils', 'getMountsForApi'),
'sharepoint');
