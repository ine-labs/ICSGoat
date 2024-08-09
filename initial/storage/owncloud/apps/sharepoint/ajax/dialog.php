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


OCP\JSON::checkAppEnabled('sharepoint');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = \OC::$server->getL10N('sharepoint');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $target = $_GET['target'];
    if (strpos($target, '..') !== false) {
        exit();
    }

    $id_username = (isset($_GET['iduser'])) ? OC_Util::sanitizeHTML($_GET['iduser']) : 'SPuser';
    $id_password = (isset($_GET['idpass'])) ? OC_Util::sanitizeHTML($_GET['idpass']) : 'SPpass';
    $id_mount = (isset($_GET['idmount'])) ? OC_Util::sanitizeHTML($_GET['idmount']) : 'SPMountId';

    $actionUrl = OCP\Util::linkTo('sharepoint', 'ajax/' . $target);

    $mountPoint = $_GET["m"];
    $type = $_GET['t'];
    $authType = $_GET['a'];
    $folder = $_GET['name'];

    $titleText = $l->t('SharePoint credentials validation');
    $usernameText = $l->t('Username');
    $passwordText = $l->t('Password');
    $enterCredentialsText = $l->t('Please enter correct credentials to mount %s folder', '%s');
    $extraInfo = "";
    if($type === "personal" && intval($authType) === 2){
        $extraInfo = $l->t('Warning: This credentials will be stored as default user credentials');
    }

    $formTitle = OC_Util::sanitizeHTML($titleText);
    $placeholderUser = OC_Util::sanitizeHTML($usernameText);
    $placeholderPass = OC_Util::sanitizeHTML($passwordText);
    $translatedText = OC_Util::sanitizeHTML($enterCredentialsText);
    $sanitizedFolder = OC_Util::sanitizeHTML($folder);
    $spanText = str_replace('%s', "<strong>$sanitizedFolder</strong>", $translatedText);
    $extra = str_replace("\n\n", '<br/>', OC_Util::sanitizeHTML($extraInfo));

    $html = <<<EOT
<div id="sp_div_form" title="${formTitle}" style="text-align:center;">
  <div>
    <span>${spanText}</span>
    <br/>
    <form method="post" action="${actionUrl}">
      <input type="text" id="${id_username}" name="${id_username}" placeholder="${placeholderUser}"/>
      <input type="password" id="${id_password}" name="${id_password}" placeholder="${placeholderPass}"/>
      <input type="hidden" id="${id_mount}" name="${id_mount}" value="${mountPoint}"/>
      <input type="hidden" id="type" name="type" value="${type}"/>
      <input type="hidden" id="folder" name="folder" value="${folder}"/>
      <input type="hidden" id="authType" name="authType" value="${authType}"/>
    </form>
  </div>
  <div>${extra}</div>
</div>
EOT;

    OCP\JSON::success(array("form" => $html));
}
