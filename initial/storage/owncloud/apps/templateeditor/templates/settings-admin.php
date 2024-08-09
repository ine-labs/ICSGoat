<?php
	/** @var $l \OCP\IL10N */
	/** @var $_ array */
	script('templateeditor', 'settings-admin');
	style('templateeditor', 'settings-admin');
?>
<div class="section" id="mailTemplateSettings" >
	<h2 class="app-name"><?php p($l->t('Mail Templates'));?></h2>
	<?php if (count($_['themeNames'])): ?>
	<div class="actions">
		<div>
			<label for="mts-theme"><?php p($l->t('Theme'));?></label>
			<select id="mts-theme">
				<?php foreach($_['themeNames'] as $themeName): ?>
				<option value="<?php p($themeName); ?>"><?php p($themeName); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label for="mts-template"><?php p($l->t('Template'));?></label>
			<select id="mts-template">
				<option value="" selected><?php p($l->t('Please choose a template')); ?></option>
				<?php foreach($_['editableTemplates'] as $template => $caption): ?>
				<option value="<?php p($template); ?>"><?php p($caption); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="templateEditor">
		<textarea></textarea>
	</div>
	<div class="actions">
		<button class="reset"><?php p($l->t('Reset'));?></button>
		<button class="save"><?php p($l->t('Save'));?></button>
		<span id="mts-msg" class="msg"></span>
	</div>
	<?php else: ?>
		<p><?php p($l->t('You need to activate own theme to edit mail templates.')) ?></p>
		<p><?php print_unescaped($l->t('How to <a target="_blank" rel="noreferrer" href="%s">create a theme</a>.', [link_to_docs('developer-theming')])); ?></p>
	<?php endif; ?>
</div>
