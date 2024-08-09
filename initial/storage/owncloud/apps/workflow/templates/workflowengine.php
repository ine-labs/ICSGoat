<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */
?>
<div id="workflow_workflows" class="section workflow">
	<div class="workflows">
		<div class="flow hidden" data-flow-id="undefined">
			<h3>
				<span><?php p($l->t('Workflow name')); ?></span>
				<a class="icon-rename workflow-edit" title="<?php p($l->t('Edit')); ?>"></a>
				<a class="icon-delete workflow-delete" title="<?php p($l->t('Delete')); ?>"></a>
			</h3>

			<div>
				<strong><?php p($l->t('Conditions:')); ?></strong>
				<ul class="conditions">

				</ul>
			</div>

			<div>
				<strong><?php p($l->t('Action:')); ?></strong>
				<div class="actions">

				</div>
			</div>
		</div>
	</div>

	<select id="workflow_types"></select><button id="new_workflow_button"><?php p($l->t('+ Add new workflow')); ?></button>

	<form id="workflow_form" class="hidden" data-flow-id="undefined" data-flow-type="undefined">
		<h3 data-toggle-title="<?php p($l->t('Edit workflow')); ?>"><?php p($l->t('New workflow')); ?></h3>

		<input type="text" id="workflow_name" value="" placeholder="<?php p($l->t('Name')); ?>">

		<div>
			<strong><?php p($l->t('Conditions:')); ?></strong>
			<div class="conditions">

			</div>
			<select class="available-conditions">
				<option value=""><?php p($l->t('Select a condition')) ?></option>
			</select>
			<a class="icon-add" id="workflow_add_condition" title="<?php p($l->t('Add condition')); ?>"></a>
		</div>

		<div class="actions">

		</div>

		<input type="button" id="workflow_submit" data-toggle-value="<?php p($l->t('Update workflow')); ?>" value="<?php p($l->t('Add workflow')); ?>" name="submit" />
		<input type="button" id="workflow_cancel" value="<?php p($l->t('Cancel')); ?>" name="cancel" />
	</form>
</div>
