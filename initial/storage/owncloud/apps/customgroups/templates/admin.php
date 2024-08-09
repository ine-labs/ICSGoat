<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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
 *
 */

script('customgroups', 'admin');
?>

<div id="customgroups-admin" class="section">
	<h2><?php p($l->t('Custom Groups'));?></h2>

	<p>
		<input type="checkbox" name="only_subadmin_can_create" id="onlySubAdminCanCreate" class="checkbox"
			   value="1" <?php if ($_['onlySubAdminCanCreate']) {
	print_unescaped('checked="checked"');
} ?> />
		<label for="onlySubAdminCanCreate">
			<?php p($l->t('Only group admins are allowed to create custom groups'));?>
		</label>
	</p>
	<p>
		<input type="checkbox" name="allow_duplicate_names" id="allowDuplicateNames" class="checkbox"
			   value="1" <?php if ($_['allowDuplicateNames']) {
	print_unescaped('checked="checked"');
} ?> />
		<label for="allowDuplicateNames">
			<?php p($l->t('Allow creating multiple groups with the same name'));?>
		</label>
	</p>
</div>
