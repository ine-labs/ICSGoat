<?php
	script('windows_network_drive', 'settings');
	/** @var array $_ */
?>
<div id='filesExternalGlobalCredentials' class="hidden">
	<form autocomplete="false" class="section" action="#"
		  id="global_credentials">
		<p><?php p($l->t('Global credentials for external storage')); ?></p>
		<input type="text" name="username"
			   autocomplete="false"
			   value="<?php p($_['globalCredentials']['user']); ?>"
			   placeholder="<?php p($l->t('Username')) ?>"/>
		<input type="password" name="password"
			   autocomplete="false"
			   value="<?php p($_['globalCredentials']['password']); ?>"
			   placeholder="<?php p($l->t('Password')) ?>"/>
		<input type="hidden" name="uid"
			   value="<?php p($_['globalCredentialsUid']); ?>"/>
		<input type="submit" value="<?php p($l->t('Save')) ?>"/>
	</form>
</div>
