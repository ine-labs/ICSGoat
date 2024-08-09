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

script('sharepoint', ['settings', 'spAjaxCalls']);
style('sharepoint', 'settings');

vendor_script('core', 'select2/select2');
vendor_style('core', 'select2/select2');
?>
<div id="SPadmin" class="section">
    <h2><?php p($l->t('SharePoint Configuration'));?></h2>
    <div>
        <p><?php p($l->t('Listing credentials. These fields are only used to list available SharePoint document list.')); ?>
                          <strong>
                            <?php p($l->t('They are not stored.')); ?>
                        </strong>
        </p>
        <?php if ((int)$_['IEversion'] <= 9 && $_['IEversion'] !== Null ): ?>
            <label for="SPlistingUsername">Username</label>
            <input type="text" id="SPlistingUsername" name="SPlistingUsername"/>
        <?php else: ?>
        <input type="text" id="SPlistingUsername" name="SPlistingUsername"
               placeholder="<?php p($l->t('Username')); ?>" />
        <?php endif; ?>
        <?php if ((int)$_['IEversion'] <= 9 && $_['IEversion'] !== Null): ?>
            <label for="SPlistingPassword">Password</label>
            <input type="password" id="SPlistingPassword" name="SPlistingPassword"/>
        <?php else: ?>
        <input type="password" id="SPlistingPassword" name="SPlistingPassword"
               placeholder="<?php p($l->t('Password')); ?>"  />
        <?php endif; ?>
    </div>
    <div>
        <p><?php p($l->t('Global credentials. These fields can be used for each
                          of the SharePoint mounts')); ?></p>
        <?php if ((int)$_['IEversion'] <= 9 && $_['IEversion'] !== Null ): ?>
            <label for="SPGlobalUsername">Username</label>
            <input type="text" id="SPGlobalUsername" name="SPGlobalUsername"
               value="<?php p($_['credentials']['user']);?>" />
        <?php else: ?>
            <input type="text" id="SPGlobalUsername" name="SPGlobalUsername"
               placeholder="<?php p($l->t('Username')); ?>"
               value="<?php p($_['credentials']['user']);?>" />
        <?php endif; ?>
        <?php if ((int)$_['IEversion'] <= 9 && $_['IEversion'] !== Null ): ?>
        <label for="SPGlobalPassword">Password</label>
        <input type="password" id="SPGlobalPassword" name="SPGlobalPassword"
               value="<?php p($_['credentials']['password']);?>"/>
        <?php else: ?>
        <input type="password" id="SPGlobalPassword" name="SPGlobalPassword"
               placeholder="<?php p($l->t('Password')); ?>"
               value="<?php p($_['credentials']['password']);?>"/>
        <?php endif; ?>
        <input type="button" id="SPSaveCredentialsButton" value="<?php p($l->t('Save')); ?>" />
    </div>
    <div id="SPadminMountPoints" class="containing_grid">
    <h3><?php p($l->t('Mount points')); ?></h3>
    <table class="grid">
        <thead>
            <tr>
                <th><?php p($l->t('Local Folder Name')); ?></th>
                <th><?php p($l->t('Available for')); ?></th>
                <th><?php p($l->t('SharePoint Site URL')); ?></th>
                <th><?php p($l->t('Document Library')); ?></th>
                <th><?php p($l->t('Authentication credentials')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($_['globalMounts'] as $mount): ?>
            <tr data-mid="<?php p($mount['mount_id']); ?>">
                <td><?php p($mount['mount_point']); ?></td>
                <td class="applicable">
                    <input type="hidden" disabled="disabled"
                           class="SPapplicableUsers" style="width:20em;"
                           value="<?php p(implode(',', array_map(function($elem){ return trim(str_replace('global', 'All users', preg_replace('/\(users\)$/', '', $elem)));}, $mount['applicables']))) ?>" />
                </td>
                <td><?php p($mount['url']); ?></td>
                <td><?php p($mount['list_name']); ?></td>
                <td class="td_sp_creds">
                    <select class="sp-select sp_top" style="width:20em;"
                            disabled="disabled">
                        <?php if((string)$mount['auth_type'] === "1"): ?>
                            <option value="1" selected="selected">
                                <?php p($l->t('User credentials'));?>
                            </option>;
                        <?php else: ?>
                            <option value="1">
                                <?php p($l->t('User credentials'));?>
                            </option>;
                        <?php endif; ?>
                        <?php if((string)$mount['auth_type'] === "2"): ?>
                            <option value="2" selected="selected">
                                <?php p($l->t('Global credentials'));?>
                            </option>;
                        <?php else: ?>
                            <option value="2">
                                <?php p($l->t('Global credentials'));?>
                            </option>;
                        <?php endif; ?>
                        <?php if((string)$mount['auth_type'] === "3"): ?>
                            <option value="3" selected="selected">
                                <?php p($l->t('Custom credentials'));?>
                            </option>;
                        <?php else: ?>
                            <option value="3">
                                <?php p($l->t('Custom credentials'));?>
                            </option>;
                        <?php endif; ?>
                        <?php if((string)$mount['auth_type'] === "4"): ?>
                            <option value="4" selected="selected">
                                <?php p($l->t('Login credentials'));?>
                            </option>;
                        <?php else: ?>
                            <option value="4">
                                <?php p($l->t('Login credentials'));?>
                            </option>;
                        <?php endif; ?>
                    </select>
                    <?php if((string)$mount['auth_type'] === "3"): ?>
                        <input type="text" name="guser"
                               value="<?php p($mount['user']); ?>"
                               disabled="disabled"/>
                        <input type="password" name="gpass"
                               placeholder="<?php p($l->t('Password'));?>"
                               disabled="disabled"/>
                    <?php endif; ?>
                    <?php if((string)$mount['auth_type'] === "4"): ?>
                        <input type="text" name="SPdomain"
                               value="<?php p($mount['user']); ?>"
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
                <td>
                    <input id="SPMountType" name="SPMountType"
                           type="hidden" class="SPapplicableUsers"
                           style="width:20em;" value=""/>
                </td>
                <td><input type="text" id="SPUrl" name="SPUrl"
                           placeholder="<?php p($l->t('SharePoint Site URL')); ?>" /></td>
                <td>
                    <input type="image" name="refreshList" id="refreshList"
                           src="<?php p(image_path('core', 'actions/history.svg')); ?>"/>
                    <img id="refreshListAdminLoader"
                         src="<?php p(image_path('sharepoint', 'ajax-loader.gif')); ?>"
                         style="display:none;">
                    <select id="selectList" name="selectList" class="sp-select" disabled>
                        <option value="0">
                            <?php p($l->t('No Document Library')); ?>
                        </option>
                    </select>
                </td>
                <td>
                    <select class="sp-select sp_top" id="authType"
                            name="authType" style="width:20em;">
                        <option value="1" selected="selected">
                            <?php p($l->t('User credentials'));?>
                        </option>
                        <option value="2" >
                            <?php p($l->t('Global credentials'));?>
                        </option>
                        <option value="3" >
                            <?php p($l->t('Custom credentials'));?>
                        </option>
                        <option value=4 >
                            <?php p($l->t('Login credentials'));?>
                        </option>
                    </select>

                    <input type="text" id="SPdomain" name="SPdomain"
                           placeholder="<?php p($l->t('Domain')); ?>"
                           style="display:none" />
                    <input type="text" id="SPGuser" name="SPGuser"
                           placeholder="<?php p($l->t('Username')); ?>"
                           style="display:none" />
                    <input type="password" id="SPGpass" name="SPGpass"
                           placeholder="<?php p($l->t('Password')); ?>"
                           style="display:none" />
                </td>
                <td><input type="button" id="SPSaveButton"
                           value="<?php p($l->t('Save')); ?>" /></td>
            </tr>
        </tfoot>
    </table>
    </div>
    <br/>
    <div>
        <input type="checkbox" id="SPActivatePersonal" name="SPActivatePersonal"
                   <?php if ($_['personalActive']) p('checked="checked"'); ?> />
        <label for="SPActivatePersonal">
            <?php p($l->t('Allow users to mount their own
                           SharePoint document libraries')); ?>
        </label>
    </div>
     <div>
        <input type="checkbox" id="SPActivateSharing" name="SPActivateSharing"
                   <?php if ($_['SPglobalSharingActive']):
                        print_unescaped('checked="checked"');
                    endif; ?>/>
        <label for="SPActivateSharing">
            <?php p($l->t('Allow users to share content in SharePoint mount points')); ?>
        </label>
    </div>

</div>
