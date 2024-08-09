<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

style('systemtags_management', 'systemtags_management');

script('core', [
	'oc-backbone-webdav',
	'systemtags/systemtags',
	'systemtags/systemtagmodel',
	'systemtags/systemtagsmappingcollection',
	'systemtags/systemtagscollection',
	'systemtags/systemtagsinputfield',
]);

script('systemtags_management', [
	'systemtagadminmodel',
	'systemtagsadmincollection',
	'systemtags_management',
]);
?>

<div id="systemtags_management" class="section systemtags_management">
	<h2 class="app-name"><?php p($l->t('Collaborative tag management')); ?></h2>

	<input type="text" id="tagmanager_tag_id" value="">

	<form id="tagmanager_form" data-tag-id="undefined">
		<h3 data-toggle-title="<?php p($l->t('Edit tag')); ?>"><?php p($l->t('Add new tag')); ?></h3>

		<input type="text" id="tagmanager_name" value="" placeholder="<?php p($l->t('Name')); ?>">
		<span class="tagmanager-delete hidden">
			<a class="icon-delete" title="<?php p($l->t('Delete')); ?>"></a>
		</span>
		<br />

		<select id="tagmanager_namespace" name="tagmanager_namespace">
			<option value="1_1_1"><?php p($l->t('Visible (all users can see/rename/delete/assign/unassign)')); ?></option>
			<option value="1_0_0"><?php p($l->t('Restricted (only users in the specified groups can rename/delete/assign/unassign)')); ?></option>
			<option value="1_1_0"><?php p($l->t('Static (only users in the specified groups can assign/unassign)')); ?></option>
			<option value="0_0_0"><?php p($l->t('Invisible (only admins can see/rename/delete/assign/unassign)')); ?></option>
		</select>

		<div class="tagmanager_groups_container hidden">
			<input type="hidden" id="tagmanager_groups" name="tagmanager_groups" placeholder="<?php p($l->t('Allowed groups')); ?>"/>
		</div>
		<br />

		<input type="button" id="tagmanager_submit" data-toggle-value="<?php p($l->t('Update tag')); ?>" value="<?php p($l->t('Create tag')); ?>" name="submit" />
		<input type="button" id="tagmanager_reset" class="hidden" value="<?php p($l->t('Reset form')); ?>" name="submit" />
		<span id="tagmanager_notifications_msg" class="msg"></span>
	</form>
</div>

