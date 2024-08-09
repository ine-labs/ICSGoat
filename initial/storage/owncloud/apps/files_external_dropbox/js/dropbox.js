$(document).ready(function() {
	var backendId = 'files_external_dropbox';
	var backendUrl = OC.generateUrl('apps/' + backendId + '/oauth');

	$('#files_external').on('oauth_step1', '.files_external_dropbox .configuration', function (event, data) {
		if (data['backend_id'] !== backendId) {
			return;	// means the trigger is not for this storage adapter
		}

		OCA.External.Settings.OAuth2.getAuthUrl(backendUrl, data);
	})

	$('#files_external').on('oauth_step2', '.files_external_dropbox .configuration', function (event, data) {
		if (data['backend_id'] !== backendId || data['code'] === undefined) {
			return;		// means the trigger is not for this OAuth2 grant
		}
		
		OCA.External.Settings.OAuth2.verifyCode(backendUrl, data)
		.fail(function (message) {
			OC.dialogs.alert(message,
				t(backendId, 'Error verifying OAuth2 Code for ' + backendId)
			);
		})
	})
});
