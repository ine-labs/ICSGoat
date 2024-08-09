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

script('sharepoint', ['personalSettings', 'spAjaxCalls']);
style('sharepoint', 'settings');

vendor_script('core', 'select2/select2');
vendor_style('core', 'select2/select2');
?>
<div id="SPpersonal" class="section" xmlns="http://www.w3.org/1999/html">
    <h2><?php p($l->t('SharePoint Personal Configuration'));?></h2>
    <div>
        <p><?php p($l->t('Personal Credentials. These fields can be used for each of the SharePoint storage mounts.')); ?></p>
        <p><strong><?php p($l->t('These personal credentials will be applied to all mounts where the Authentication credentials option is set to global user credentials')); ?></strong></p>
        <p><strong><?php p($l->t('For system mounts you have to enter them if required.')); ?><strong></p>
        <input type="text" id="SPGlobalUsername" name="SPGlobalUsername"
               placeholder="<?php p($l->t('Username')); ?>"
               value="<?php p($_['credentials'][0])?>" />
        <input type="password" id="SPGlobalPassword" name="SPGlobalPassword"
               placeholder="<?php p($l->t('Password')); ?>"
               value="<?php p($_['credentials'][1])?>" />
        <input type="button" id="SPSaveCredentialsButton" value="<?php p($l->t('Save')); ?>" />
    </div>
    <div id="SPadminPersonalMountPoints" class="containing_grid">
    <h3><?php p($l->t('Admin added mount points')); ?></h3>
    <table class="grid">
        <thead>
            <tr>
                <th><?php p($l->t('Local Folder Name')); ?></th>
                <th><?php p($l->t('SharePoint Site URL')); ?></th>
                <th><?php p($l->t('Document Library')); ?></th>
                <th><?php p($l->t('Authentication Credentials')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($_['globalMounts'] as $mount): ?>
            <tr data-mid="<?php p($mount['mount_id']); ?>">
                <td><?php p($mount['mount_point']); ?></td>
                <td><?php p($mount['url']); ?></td>
                <td><?php p($mount['list_name']); ?></td>
                <td class="td_sp_creds">
                <?php if((string)$mount['auth_type'] === "1"): ?>
                    <?php if(is_null($mount['credential_username']) ||
                             is_null($mount['credential_password'])): ?>
                        <input type="text" name="SPuser"
                               id="SPuser<?php p($mount['mount_id']) ?>"
                               placeholder="<?php p($l->t('Username')); ?>" />
                        <input type="password" name="SPpass"
                               id="SPpass<?php p($mount['mount_id']) ?>"
                               placeholder="<?php p($l->t('Password')); ?>" />
                    <?php else: ?>
                        <span><?php p($l->t('Username') . ': ') ?></span>
                        <span name="username">
                            <?php p($mount['credential_username']) ?>
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <?php p($l->t('Credentials provided by the admin')) ?>
                <?php endif; ?>
                </td>
                <td>
                <?php if((string)$mount['auth_type'] === "1"): ?>
                    <?php if(is_null($mount['credential_username']) ||
                             is_null($mount['credential_password'])): ?>
                        <input type="button" class="SaveButton"
                               value="<?php p($l->t('Save')) ?>" />
                    <?php else: ?>
                        <input type="button" class="EditButton"
                               value="<?php p($l->t('Edit')) ?>" />
                    <?php endif; ?>
                <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if($_['personal_mounts_enabled']): ?>
    <div id="SPPersonalMountPoints" class="containing_grid">
    <h3><?php p($l->t('Personal mount points')); ?></h3>
    <table class="grid">
        <thead>
            <tr>
                <th><?php p($l->t('Local Folder Name')); ?></th>
                <th><?php p($l->t('SharePoint Site URL')); ?></th>
                <th><?php p($l->t('Document Library')); ?></th>
                <th><?php p($l->t('Authentication credentials')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($_['personalMounts'] as $mount): ?>
            <tr data-mid="<?php p($mount['mount_id']); ?>">
                <td><?php p($mount['mount_point']); ?></td>
                <td><?php p($mount['url']); ?></td>
                <td><?php p($mount['list_name']); ?></td>
                <td class="td_sp_creds">
                    <select class="sp-select sp_top" style="width:20em;"
                            disabled="disabled">
                        <?php if((string)$mount['auth_type'] === "1"): ?>
                            <option value="1" selected="selected">
                                <?php p($l->t('Custom credentials'));?>
                            </option>
                        <?php else: ?>
                            <option value="1">
                                <?php p($l->t('Custom credentials'));?>
                            </option>;
                        <?php endif; ?>
                        <?php if((string)$mount['auth_type'] === "2"): ?>
                            <option value="2" selected="selected">
                                <?php p($l->t('Personal credentials'));?>
                            </option>
                        <?php else: ?>
                            <option value="2">
                                <?php p($l->t('Personal user credentials'));?>
                            </option>
                        <?php endif; ?>
                    </select>
                    <?php if((string)$mount['auth_type'] === "1"): ?>
                        <input type="text" name="guser"
                               value="<?php p($mount['user']); ?>"
                               disabled="disabled"/>
                        <input type="password" name="gpass"
                               placeholder="<?php p($l->t('Password'));?>"
                               disabled="disabled"/>
                    <?php endif; ?>
                </td>
                <td>
                    <input type="button" name="edit"
                           value="<?php p($l->t('Edit')); ?>" />
                    <input type="button" name="delete"
                           value="<?php p($l->t('Delete')); ?>" />
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td><input type="text" id="SPMountPoint" name="SPMountPoint"
                           placeholder="<?php p($l->t('Local Folder Name')); ?>" /></td>
                <td><input type="text" id="SPUrl" name="SPUrl"
                           placeholder="<?php p($l->t('SharePoint Site URL')); ?>" /></td>
                <td>
                    <input type="image" name="refreshList" id="refreshList"
                           src="<?php p(image_path('core', 'actions/history.svg')); ?>"/>
                    <img id="refreshListAdminLoader"
                         src="<?php p(image_path('sharepoint', 'ajax-loader.gif')); ?>"
                         style="display:none;">
                    <select id="selectList" name="selectList"
                            class="sp-select sp_top" disabled>
                        <option value="0">
                            <?php p($l->t('No Document Library')); ?>
                        </option>
                    </select>
                </td>
                <td>
                    <select class="sp-select sp_top" id="authType"
                            name="authType" style="width:20em;">
                        <option value="1">
                            <?php p($l->t('Custom credentials'));?>
                        </option>
                        <option value="2" selected="selected">
                            <?php p($l->t('Personal credentials'));?>
                        </option>
                    </select>

                    <input type="text" id="SPGuser" name="SPGuser"
                        placeholder="<?php p($l->t('Username')); ?>"
                        value=""
                        style="display: none;"/>
                    <input type="password" id="SPGpass" name="SPGpass"
                           placeholder="<?php p($l->t('Password')); ?>"
                           value=""
                           style="display: none;"/>
                </td>
                <td><input type="button" id="SPSaveButton"
                           value="<?php p($l->t('Save')); ?>" /></td>
            </tr>
        </tfoot>
    </table>
    </div>
    <?php endif; ?>
</div>
