<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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
script('oauth2', 'switch-user');
?>

<div class="error">
	<p><b><?php p($l->t('Switch user')); ?></b></p>
	<br>
	<p><?php
		print_unescaped($l->t(
	'You are logged in as %s but the application requested access for user %s.',
	[$_['current_user'], $_['requested_user']]
)); ?>
	</p>
	<br>
	<a href="<?php p($_['logout_url']); ?>">
		<button autofocus><?php p($l->t('Switch users to continue')); ?></button>
	</a>
</div>
