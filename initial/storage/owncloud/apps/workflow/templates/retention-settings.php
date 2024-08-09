<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */
?>
<div id="workflow_retention" class="section workflow">
	<h2><?php p($l->t('Retention periods')); ?></h2>

	<p><?php p($l->t('Delete files tagged with the following tags after the given time:')); ?></p>

	<table class="retention">
		<tr class="hidden" data-tag-id="undefined">
			<td class="tag_name">
				Tagname1 <em>(visible, assignable)</em>
			</td>
			<td>
				<input type="number" min="1" class="retention_period" value="24">
				<select class="retention_period_unit">
					<option value="days"><?php p($l->t('Days')); ?></option>
					<option value="weeks"><?php p($l->t('Weeks')); ?></option>
					<option value="months"><?php p($l->t('Months')); ?></option>
					<option value="years"><?php p($l->t('Years')); ?></option>
				</select>
				<button class="retention-update"><?php p($l->t('Save')); ?></button>
			</td>
			<td>
				<button class="retention-delete"><?php p($l->t('Delete')); ?></button>
			</td>
		</tr>
	</table>

	<button id="new-retention-button"><?php p($l->t('+ Add new rule')); ?></button>

	<form id="retention_new_period" class="hidden">
		<?php print_unescaped($l->t('Delete files tagged as %s after %s', [
			'<input type="text" id="retention_tag_id" value="">',
			'<input type="number" min="1" id="retention_period" value="24">
			<select id="retention_period_unit">
				<option value="days">' . $l->t('Days') . '</option>
				<option value="weeks">' . $l->t('Weeks') . '</option>
				<option value="months">' . $l->t('Months') . '</option>
				<option value="years">' . $l->t('Years') . '</option>
			</select>'
		])); ?>
		<input type="button" id="retention_submit" value="<?php p($l->t('Add rule')); ?>" name="submit" />
	</form>

	<?php if ($_['retention_engine'] === 'userbased'): ?>
	<br /><br />
	<input id="folder_retention" name="folder_retention" type="checkbox" class="checkbox"
		   value="1" <?php if ($_['folder_retention']): ?> checked="checked"<?php endif; ?> />
	<label for="folder_retention"><?php print_unescaped($l->t('Delete empty folders that have not been modified for %s days', [
			'<input type="number" min="1" id="folder_retention_period" value="' . $_['folder_retention_period'] . '">',
		])); ?></label>
	<?php endif; ?>
</div>
