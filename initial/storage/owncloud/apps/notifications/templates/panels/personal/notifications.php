<?php script('notifications', 'personal_settings'); ?>
<div id="email_notifications" class="section">
	<h2 class="app-name"><?php p($l->t('Mail Notifications'));?></h2>
	<?php if ($_['validUserObject']): ?>
	<p><?php p($l->t('You can choose to be notified about events via mail. Some events are informative, others require an action (like accept/decline). Select your preference below:')); ?></p>
	<select id="email_sending_option" name="email_sending_option">
		<?php foreach ($_['possibleOptions'] as $possibleValue => $data): ?>
		<option value="<?php p($possibleValue) ?>" <?php if ($data['selected']) {
	echo 'selected="selected"';
} ?>><?php p($data['visibleText']); ?></option>
		<?php endforeach; ?>
	</select>
	<span class="msg"></span>
	<?php else: ?>
	<p><?php p($l->t('It was not possible to get your session. Please, try reloading the page or logout and login again')); ?></p>
	<?php endif; ?>
	<p><?php p($l->t('To be able to receive mail notifications it is required to specify an email address for your account.')); ?></p>
</div>
