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
?>
<div id="SPadmin" class="section">
    <h2><?php p($l->t('SharePoint Configuration'));?></h2>
    <div class="warning">
    <?php if (!class_exists('SoapClient')): ?>
        <p><?php p($l->t('The SharePoint application cannot locate the PHP SOAP library. Please ensure this library is properly installed.')); ?></p>
    <?php elseif (!function_exists('curl_version')): ?>
        <p><?php p($l->t('The SharePoint application cannot locate the PHP cURL library. Please ensure this library is properly installed.')); ?></p>
    <?php endif; ?>
    </div>
</div>
