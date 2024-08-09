<?php
	script('ransomware_protection', 'settings.admin');
?>

<div class="section section-ransomware-protection">
	<h2><?php p($l->t('Ransomware Protection'));?></h2>
		<div>
			<input type="checkbox" id="ransomware-protection-locking-enabled" value="locking_enabled"<?php if ($_['lockingEnabled']) {
	?>checked="checked" <?php
} ?>/>
			<label for="ransomware-protection-locking-enabled"><?php p($l->t('Lock user accounts (read-only) for sync clients when a blacklist match occurred.'));?></label>
		</div>
</div>
