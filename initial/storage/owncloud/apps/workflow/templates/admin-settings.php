<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

style('workflow', 'workflow');

script('workflow', [
	'admin',
	'workflowengine',
	'admin-condition',
	'admin-retention',
	'autotaggingplugin',
]);

script('core', [
	'oc-backbone-webdav',
	'systemtags/systemtags',
	'systemtags/systemtagmodel',
	'systemtags/systemtagsmappingcollection',
	'systemtags/systemtagscollection',
	'systemtags/systemtagsinputfield',
]);
?>
<div class="section workflow-anchor">
	<h2 class="app-name"><?php p($l->t('Workflow')); ?></h2>
</div>

<?php print_unescaped($this->inc('workflowengine')); ?>

<?php print_unescaped($this->inc('retention-settings')); ?>
