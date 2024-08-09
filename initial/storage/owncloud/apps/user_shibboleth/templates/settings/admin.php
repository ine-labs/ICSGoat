<?php
/**
 * ownCloud
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright (C) 2014-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

/** @var $l \OCP\IL10N */
/** @var $_ array */

script('user_shibboleth', 'settings-admin');
style('user_shibboleth', 'shibboleth');

?>
<div class="section" id="shibboleth">
	<h2 class="app-name"><?php p($l->t('Shibboleth'));?></h2>

	<label for="shibboleth-mode"><?php p($l->t('App Mode'));?></label>
	<select id="shibboleth-mode" name="shibboleth-mode">
		<option value="notactive"><?php p($l->t('Not active'));?></option>
		<option value="autoprovision"><?php p($l->t('Autoprovision Users'));?></option>
		<option value="ssoonly"><?php p($l->t('Single sign-on only'));?></option>
	</select>

	<span class="msg"></span>

	<h3><?php p($l->t('Environment mapping:'));?></h3>
	<div id="shibboleth-env-source">
		<label for="shibboleth-env-source-shibboleth-session">
			<?php p($l->t('Use'));?>
			<select id="shibboleth-env-source-shibboleth-session" name="shibboleth-env-source-shibboleth-session">
				<option value=""></option>
				<?php foreach ($_['env'] as $k => $v) {
	?>
					<option value="<?php p($k)?>"><?php p($k)?></option>
				<?php
} ?>
			</select>
			<?php p($l->t('as Shibboleth session'));?>
		</label>
		<br>
		<label for="shibboleth-env-source-uid">
			<?php p($l->t('Use'));?>
			<select id="shibboleth-env-source-uid" name="shibboleth-env-source-uid">
				<option value=""></option>
				<?php foreach ($_['env'] as $k => $v) {
		?>
					<option value="<?php p($k)?>"><?php p($k)?></option>
				<?php
	} ?>
			</select>
			<?php p($l->t('as uid'));?>
		</label>
		<br>
		<label for="shibboleth-env-source-email">
			<?php p($l->t('Use'));?>
			<select id="shibboleth-env-source-email" name="shibboleth-env-source-email">
				<option value=""></option>
				<?php foreach ($_['env'] as $k => $v) {
		?>
					<option value="<?php p($k)?>"><?php p($k)?></option>
				<?php
	} ?>
			</select>
			<?php p($l->t('as email'));?>
		</label>
		<br>
		<label for="shibboleth-env-source-display-name">
			<?php p($l->t('Use'));?>
			<select id="shibboleth-env-source-display-name" name="shibboleth-env-source-display-name">
				<option value=""></option>
				<?php foreach ($_['env'] as $k => $v) {
		?>
					<option value="<?php p($k)?>"><?php p($k)?></option>
				<?php
	} ?>
			</select>
			<?php p($l->t('as display name'));?>
		</label>
		<br>
		<label for="shibboleth-env-source-quota">
			<?php p($l->t('Use'));?>
			<select id="shibboleth-env-source-quota" name="shibboleth-env-source-quota">
				<option value=""></option>
				<?php foreach ($_['env'] as $k => $v) {
		?>
					<option value="<?php p($k)?>"><?php p($k)?></option>
				<?php
	} ?>
			</select>
			<?php p($l->t('as quota'));?>
		</label>
	</div>

	<h3><?php p($l->t('Server Environment:'));?></h3>
	<table class="server-env">
		<?php foreach ($_['env'] as $k => $v) {
		?>
			<tr>
				<td><?php p($k)?></td>
				<td><?php p($v)?></td>
			</tr>
		<?php
	} ?>
	</table>
</div>
