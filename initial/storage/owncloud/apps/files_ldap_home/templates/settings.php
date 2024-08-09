<?php
/**
 * ownCloud
 *
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

/** @var $l OCP\IL10N */
style('files_ldap_home', 'settings');
script('files_ldap_home', 'settings');
?>
<form id="filesLdapHome" action="#" method="post" class="section">
	<div id="filesLdapHomeSettings" class="personalblock">
		<h2><?php p($l->t('LDAP User Home')); ?></h2>
		<?php if (!$_['enabled']) {
	?>
			Disabled
		<?php
} else {
		?>
			<p>
				<label for="filesLdapHomeMountName"><?php p($l->t('Display folder as: ')); ?></label>
				<input type="text" id="filesLdapHomeMountName" name="filesLdapHomeMountName" title="<?php p($l->t('The name under which the user home folder is made available. Default: ') . $_['homeMountNameDefault']); ?>" value="<?php p($_['homeMountName']); ?>">
			</p>
			<?php if (($_['attributeMode'] === "uni") && (\count($_['serverHosts']) <= 1)) {
			?>
				<p>
					<label for="filesLdapHomeAttribute"><?php p($l->t('Attribute name: ')); ?></label>
					<input type="text" id="filesLdapHomeAttribute" name="filesLdapHomeAttribute" title="<?php p($l->t('The LDAP attribute that contains the user home folder path on the server ownCloud is running on.')); ?>" value="<?php p($_['attribute']); ?>" <?php if ($_['attributeMode'] !== "uni") {
				?>disabled="disabled"<?php
			} ?>>
				</p>
			<?php
		} else {
			?>
				<p>
					<input type="radio" name="filesLdapHomeAttributeMode" value="uni" id="filesLdapHomeAttributeModeUni" <?php if ($_['attributeMode'] === "uni") {
				?>checked="checked"<?php
			} ?>>
					<label for="filesLdapHomeAttributeModeUni">One attribute for all LDAP servers</label>
				</p>
				<p>
					<label></label>
					<input type="text" id="filesLdapHomeAttribute" name="filesLdapHomeAttribute" title="<?php p($l->t('The LDAP attribute that contains the user home folder path on the server ownCloud is running on.')); ?>" value="<?php p($_['attribute']); ?>" <?php if ($_['attributeMode'] !== "uni") {
				?>disabled="disabled"<?php
			} ?>>
				</p>
				<p>
					<input type="radio" name="filesLdapHomeAttributeMode" value="spec" id="filesLdapHomeAttributeModeSpec" <?php if ($_['attributeMode'] === "spec") {
				?>checked="checked"<?php
			} ?>>
					<label for="filesLdapHomeAttributeModeSpec">Special Settings per Server</label>
				</p>
				<?php foreach ($_['serverHosts'] as $serverHost) {
				?>
					<p>
						<label for="filesLdapHomeAttributeS-<?php p($serverHost['prefix']); ?>" class="filesLdapHomeIndent">
							<?php p($l->t('Attribute for Server ') . $serverHost['name'] . ':'); ?></label>
						<input type="text" id="filesLdapHomeAttributeS-<?php p($serverHost['prefix']); ?>" name="filesLdapHomeAttributeS-<?php p($serverHost['prefix']); ?>" value="<?php p($serverHost['attribute']); ?>"  <?php if ($_['attributeMode'] !== "spec") {
					?>disabled="disabled"<?php
				} ?>>
					</p>

				<?php
			} ?>

			<?php
		} ?>
		<?php
	}?>
		<input id="filesLdapHomeSubmit" type="submit" value="<?php p($l->t('Save')); ?>" />
	</div>
</form>
