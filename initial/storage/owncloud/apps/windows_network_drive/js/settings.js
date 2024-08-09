function initGlobalAuth($globalAuthEl) {
	$('#externalStorage').before($globalAuthEl);
	$globalAuthEl.removeClass('hidden');

	$globalAuthEl.find('form').on('submit', function () {
		var $form = $(this);
		var uid = $form.find('[name=uid]').val();
		var user = $form.find('[name=username]').val();
		var password = $form.find('[name=password]').val();
		var $submit = $form.find('[type=submit]');
		$submit.val(t('files_external', 'Saving...'));
		$.ajax({
			type: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				uid: uid,
				user: user,
				password: password
			}),
			url: OC.generateUrl('apps/windows_network_drive/globalcredentials'),
			dataType: 'json',
			success: function () {
				$submit.val(t('files_external', 'Saved'));
				setTimeout(function () {
					$submit.val(t('files_external', 'Save'));
				}, 2500);
			}
		});
		return false;
	});
}

$(document).ready(function() {
	initGlobalAuth($('#filesExternalGlobalCredentials'));
});
