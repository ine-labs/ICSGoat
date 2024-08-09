$(document).ready(function () {
	$('#oauth2').on('click', '.grid .icon-delete', function (event) {
		event.preventDefault();
		OC.msg.startAction('#oauth2_save_msg', t('oauth2', 'Deleting...'));
		$.post(
			OC.generateUrl('apps/oauth2/clients/{id}/delete', {id: $(this).data('id')}),
			{},
			function (data) {
				OC.msg.finishedAction('#oauth2_save_msg', data);
				if (data.errorMessage) {
					OC.Notification.showTemporary(data.errorMessage);
				} else if (data.clientIdentifier) {
					$('.oauth2-identifier').filter(function () {
						return $(this).text() === data.clientIdentifier;
					}).parents('tr').first().remove();
					if ($('.grid .oauth2-identifier').length === 0) {
						$('#oauth2 .no-clients-message').removeClass('hidden');
						$('#oauth2 .grid').addClass('hidden');
					}
				}
			}
		);
	});
	$('#oauth2_submit').on('click', function (event){
		event.preventDefault();
		OC.msg.startAction('#oauth2_save_msg', t('oauth2', 'Saving...'));
		$.post(
			OC.generateUrl('apps/oauth2/clients'),
			$('#oauth2-new-client').serializeArray(),
			function (data) {
				OC.msg.finishedAction('#oauth2_save_msg', data);
				if (data.errorMessage) {
					OC.Notification.showTemporary(data.errorMessage);
				} else {
					$('#oauth2 .grid').removeClass('hidden');
					$('#oauth2 .no-clients-message').addClass('hidden');
					$('#oauth2 .grid tbody').append(data.rowHtml);
					$('#oauth2 input[name="name"]').val('');
					$('#oauth2 input[name="redirect_uri"]').val('');
					$('#oauth2 input[name="allow_subdomains"]').prop('checked', false);
				}
			}
		);
	});
});
