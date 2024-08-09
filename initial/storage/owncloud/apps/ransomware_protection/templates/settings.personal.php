<?php
	script('ransomware_protection', 'settings.personal');
?>

<div class="section section-ransomware-protection">
	<h2><?php p($l->t('Ransomware Protection'));?></h2>
		<div id="ransomware-protection-msg-locked" class="warning" <?php if (empty($_['lockedTimestamp'])) {
	?> style="display:none;"<?php
} ?>>
			<p><strong><?php p($l->t('Your account was locked by Ransomware Protection app due to intrusion attempts.')); ?></strong></p>
			<ul>
				<li><?php p($l->t('- Locked on %s (server time), Timestamp: %s', [$l->l('datetime', $_['lockedTimestamp']), $_['lockedTimestamp']])); ?></li>
				<?php if (!empty($_['lockedReason'])) {
		?> <li><?php p($l->t('- Reason: %s', $_['lockedReason'])); ?></li><?php
	} ?>
			</ul>
			<p><?php p($l->t('Write access will only be re-enabled by clicking the button below.')); ?></p>
			<button id="ransomware-protection-unlock" class="button"><?php p($l->t('Unlock your account')); ?></button>
		</div>
		<div id="ransomware-protection-msg-unlocked" <?php if (!empty($_['lockedTimestamp'])) {
		?> style="display:none;"<?php
	} ?>>
			<p><?php p($l->t('Sync clients are enabled.')); ?></p>
			<button id="ransomware-protection-lock" class="button"><?php p($l->t('Lock your account')); ?></button>
		</div>
</div>
