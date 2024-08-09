<?php
/**
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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
?>
<div>
	<form action="<?php p($_['postAction']); ?>" name="register" method="post">
		<?php foreach($_['messages'] as $message): ?>
			<div class="warning">
				<?php p($message); ?><br>
			</div>
		<?php endforeach; ?>
		<div class="grouptop<?php if (!empty($_['invalidpassword'])) { ?> shake<?php } ?>">
			<label for="email"><?php p($l->t('Email')); ?></label>
			<input type="text" name="email" id="email"
					value="<?php p($_['email']); ?>"
					autocomplete="off" autocapitalize="off" autocorrect="off" required>
				
		</div>

		<div class="groupbottom<?php if (!empty($_['invalidpassword'])) { ?> shake<?php } ?>">
		<label for="password"><?php p($l->t('Password')); ?></label>
		<input type="password" name="password" id="password" value=""
				autocomplete="off" autocapitalize="off" autocorrect="off" required autofocus>
			
		</div>
		<div class="submit-wrap">
			<button type="submit" id="submit" class="login-button">
				<span><?php p($l->t('Set password')); ?></span>
				<div class="loading-spinner"><div></div><div></div><div></div><div></div></div>
			</button>

			<input type="hidden" name="token" value="<?php p($_['token']) ?>">
			<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
		</div>
	</form>
</div>
