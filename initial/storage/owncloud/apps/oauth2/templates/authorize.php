<?php
/**
 * @author Project Seminar "sciebo@Learnweb" of the University of Muenster
 * @copyright Copyright (c) 2017, University of Muenster
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

style('oauth2', 'authorization');
?>

<span class="error">
	<form class="form-inline" action="" method="post">
		<p>
			<b><?php p($l->t('The “%s“ application would like permission to access your account', [$_['client_name']])); ?></b>
		</p>
		<br>
		<p><?php print_unescaped($l->t('You are logged in as %s.', [$_['current_user']])); ?></p>
		<br>
		<p><?php p($l->t('The application will gain access to your username and will be allowed to manage files, folders and shares.')); ?></p>
		<br>
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
		<button type="submit" autofocus><?php p($l->t('Authorize')); ?></button>
	</form>
		<a href="<?php p($_['logout_url']); ?>">
			<button><?php p($l->t('Switch users to continue')); ?></button>
		</a>
</span>
