<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */
script('firewall', 'query-builder');
vendor_script('firewall', 'es5-shim/es5-shim');
vendor_script('firewall', 'jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon');
vendor_script('firewall', 'jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-addon-i18n');

style('firewall', 'query-builder');
style('firewall', 'firewall');
vendor_style('firewall', 'font-awesome/css/font-awesome');
vendor_style('firewall', 'jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon');

script('firewall', 'admin');

script('core', [
	'oc-backbone-webdav',
	'systemtags/systemtags',
	'systemtags/systemtagmodel',
	'systemtags/systemtagsmappingcollection',
	'systemtags/systemtagscollection',
	'systemtags/systemtagsinputfield',
]);
?>
<form id="firewall" class="section">
	<h2><?php p($l->t('File Firewall')); ?></h2>

	<p>
		<?php p($l->t('Requests are checked against all groups of rules that are defined below.') . ' '); ?>
		<?php p($l->t('A request is blocked when at least one group matches the request.') . ' '); ?>
		<?php p($l->t('A group matches a request when all rule conditions in the group evaluate to true.')); ?>
	</p>

	<div class="container">
		<div id="rulebuilder"></div>

		<div class="actions">
			<br/>
			<div>
				<button class="saveRules"><?php p($l->t('Save Rules')); ?></button>
			</div>

			<div>
				<label for="firewallDebug"><?php p($l->t('Logging')); ?></label>
				<select name="debug" id="firewallDebug">
					<option value="1"><?php p($l->t('Off')); ?></option>
					<option value="2"><?php p($l->t('Blocked Requests Only')); ?></option>
					<option value="3"><?php p($l->t('All Requests')); ?></option>
				</select>
			</div>

			<div class="msg clear"></div>
		</div>
	</div>

	<p>
		<?php
			$linkHref = [
				'<a href="' . \OC::$server->getUrlGenerator()->linkToRouteAbsolute('settings.SettingsPage.getAdmin', ['sectionid' => 'general']) . '">',
				'</a>'
			];
			print_unescaped(
				$l->t('To log Blocked Requests Only the %s system log level %s must be set to include warnings.', $linkHref) . ' '
			);
			print_unescaped(
				$l->t('To log All Requests the %s system log level %s must be set to include info.', $linkHref) . ' '
			);
			p($l->t('Logging All Requests can generate a large amount of log data.') . ' ');
			p($l->t('It is recommended to only select All Requests for short-term checking of rule settings.'));
		?>
	</p>
</form>
