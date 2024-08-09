<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
$l = $_['l10n'];
?>
<div class="error error-wide">
	<?php p($l->t('Access to this resource has been forbidden by a file firewall rule.')); ?>
	<br/>
	<?php print_unescaped($l->t('If you feel this is an error, please contact your administrator or %slogout%s.', [
		'<a href="' . $_['logoutUrl'] . '">','</a>'
	])); ?>
</div>
