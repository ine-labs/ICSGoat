$(document).ready(function() {

	var loadTemplate = function (theme, template) {
		if (template === '' || theme === '') {
			return;
		}
		//the first time we load a template show the editor
		$( '#mailTemplateSettings .templateEditor:hidden').show(400);
		$( '#mailTemplateSettings .templateEditor + .actions:hidden').show(400);
		$.get(
			OC.generateUrl('apps/templateeditor/settings/mailtemplate'),
			{ themeName: theme, template: template }
		).done(function( result ) {
			$( '#mailTemplateSettings textarea' ).val(result);
		}).fail(function( result ) {
			OC.dialogs.alert(result.responseJSON.message, t('templateeditor', 'Could not load template'));
		});
	};

	$( '#mts-template' ).change(
		function() {
			var theme = $( '#mts-theme' ).val();
			var template = $( this ).val();
			if (template) {
				loadTemplate(theme, template);
			} else {
				//hide editor
				$( '#mailTemplateSettings .templateEditor').hide(400);
				$( '#mailTemplateSettings .templateEditor + .actions').hide(400);
			}
		}
	);

	$( '#mts-theme' ).change(
		function() {
			var theme = $( this ).val();
			var template = $( '#mts-template' ).val();
			loadTemplate(theme, template);
		}
	);

	$( '#mailTemplateSettings .actions' ).on('click', '.save',
		function() {
			var theme = $( '#mts-theme' ).val();
			var template = $( '#mts-template' ).val();
			var content = $( '#mailTemplateSettings textarea' ).val();
			OC.msg.startSaving('#mts-msg');
			$.post(
				OC.generateUrl('apps/templateeditor/settings/mailtemplate'),
				{ theme: theme, template: template, content: content }
			).done(function() {
				OC.msg.finishedSuccess('#mts-msg', t('templateeditor', 'Saved'));
			}).fail(function(result) {
				OC.msg.finishedError('#mts-msg', result.responseJSON.message);
			});
		}
	);

	$( '#mailTemplateSettings .actions' ).on('click', '.reset',
		function() {
			var theme = $( '#mts-theme' ).val();
			var template = $( '#mts-template' ).val();
			OC.msg.startSaving('#mts-msg');
			$.ajax({
				type: "DELETE",
				url: OC.generateUrl('apps/templateeditor/settings/mailtemplate'),
				data: { theme: theme, template: template }
			}).done(function() {
				OC.msg.finishedSuccess('#mts-msg', t('files_sharing', 'Reset'));

				// load default template
				var theme = $( '#mts-theme' ).val();
				var template = $( '#mts-template' ).val();
				loadTemplate(theme, template);
			}).fail(function(result) {
				OC.msg.finishedError('#mts-msg', t('files_sharing', 'An error occurred'));
			});
		}
	);

});
