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
<?php /** @var $l OC_L10N */ ?>
<div id="controls">
    <div id="file_action_panel"></div>
</div>
<div id='notification'></div>

<div id="emptycontent" class="hidden"><?php p($l->t( 'You don\'t have any SharePoint document libraries mounted' )); ?></div>

<input type="hidden" name="dir" value="" id="dir">

<table id="filestable">
    <thead>
        <tr>
            <th id='headerName' class="hidden column-name">
                <div id="headerName-container">
                    <a class="name sort columntitle" data-sort="name"><span><?php p($l->t( 'Name' )); ?></span><span class="sort-indicator"></span></a>
                </div>
            </th>
            <th id='headerSharepointSite' class="hidden column-site">
                <a class="site sort columntitle" data-sort="site"><span><?php p($l->t( 'SharePoint site' )); ?></span><span class="sort-indicator"></span></a>
            </th>
            <th id='headerSharepointDocumentList' class="hidden column-documentList">
                <a class="documentList sort columntitle" data-sort="documentList"><span><?php p($l->t( 'Document list' )); ?></span><span class="sort-indicator"></span></a>
            </th>
            <th id="headerScope" class="hidden column-scope">
                <a class="scope sort columntitle" data-sort="scope"><span><?php p($l->t('Type')); ?></span><span class="sort-indicator"></span></a>
            </th>
            <th id="headerAuthentication" class="hidden column-authType">
                <a class="authType sort columntitle" data-sort="authType"><span><?php p($l->t('Authentication')); ?></span><span class="sort-indicator"></span></a>
            </th>
        </tr>
    </thead>
    <tbody id="fileList">
    </tbody>
    <tfoot>
    </tfoot>
</table>
