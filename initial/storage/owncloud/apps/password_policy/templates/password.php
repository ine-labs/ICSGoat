<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

script('password_policy', 'password');
style('password_policy', 'styles');

/**
 * @var array $_
 * @var \OCP\IL10N $l
 * @var \OC_Defaults $theme
 */
?>

<form id="password_policy" method="post">
	<fieldset>
		<h1 class="warning">
			<div>
				<?php
					if ($_['firstLogin'] === true) {
						p($l->t('Please set a new password'));
					} else {
						p($l->t('Your password has expired.'));
					}
				?>
			</div>
			<div>
				<?php
					if ($_['firstLogin'] !== true) {
						p($l->t('Please choose a new password.'));
					}
				?>
			</div>
		</h1>
		<?php if (isset($_['error'])) {
					?><div id="error" class="warning"><?php p($_['error']) ?></div> <?php
				} ?>
		<input type="hidden" name="redirect_url" value="<?php p($_['redirect_url']) ?>" />
		<input type="hidden" name="app" value="oca-password-policy" />
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" id="requesttoken">

		<div class="grouptop">
			<label for="current_password" class="infield"><?php p($l->t('Current password'));?></label>
			<input type="password" id="current_password" name="current_password" value="" autofocus placeholder="<?php p($l->t('Current password'));?>"/>
		</div>
		<div class="groupmiddle">
			<label for="new_password" class="infield"><?php p($l->t('New password'));?></label>
			<input type="password" id="new_password" name="new_password" value="" placeholder="<?php p($l->t('New password'));?>"/>
		</div>
		<div class="groupbottom">
			<label for="confirm_password" class="infield"><?php p($l->t('Confirm new password'));?></label>
			<input type="password" id="confirm_password" name="confirm_password" value="" placeholder="<?php p($l->t('Confirm new password'));?>"/>
		</div>
		<?php if ($_['password_requirements']) {?>
			<div id="password_hint" class="warning">
				<h1><?php p($l->t('Password requirements:'));?></h1>
				<ul>
					<?php if (isset($_['password_requirements']['spv_min_chars_value']) && (int)$_['password_requirements']['spv_min_chars_value'] > 0) {?>
						<li><?php p($l->n('At least one character', 'At least %n characters', (int)$_['password_requirements']['spv_min_chars_value']));?></li>
					<?php }?>
					<?php if (isset($_['password_requirements']['spv_lowercase_value']) && (int)$_['password_requirements']['spv_lowercase_value'] > 0) {?>
						<li><?php p($l->n('At least one lowercase letter', 'At least %n lowercase letters', (int)$_['password_requirements']['spv_lowercase_value']));?></li>
					<?php }?>
					<?php if (isset($_['password_requirements']['spv_uppercase_value']) && (int)$_['password_requirements']['spv_uppercase_value'] > 0) {?>
						<li><?php p($l->n('At least one uppercase letter', 'At least %n uppercase letters', (int)$_['password_requirements']['spv_uppercase_value']));?></li>
					<?php }?>
					<?php if (isset($_['password_requirements']['spv_numbers_value']) && (int)$_['password_requirements']['spv_numbers_value'] > 0) {?>
						<li><?php p($l->n('At least one number', 'At least %n numbers', (int)$_['password_requirements']['spv_numbers_value']));?></li>
					<?php }?>
					<?php if (isset($_['password_requirements']['spv_special_chars_value']) && (int)$_['password_requirements']['spv_special_chars_value'] > 0) {?>
						<li><?php p($l->n('At least one special character', 'At least %d special characters', (int)$_['password_requirements']['spv_special_chars_value']));?></li>
					<?php }?>
					<?php if (isset($_['password_requirements']['spv_def_special_chars_value'])) {?>
						<li><?php p($l->t('Only special characters "%s" are allowed', [$_['password_requirements']['spv_def_special_chars_value']]));?></li>
					<?php }?>
				</ul>
			</div>
		<?php }?>
		<div class="submit-wrap">
			<button id="submit" type="submit">
				<span><?php p($l->t('Save')); ?></span>
				<div class="loading-spinner"><div></div><div></div><div></div><div></div></div>
			</button>
		</div>
	</fieldset>
</form>
