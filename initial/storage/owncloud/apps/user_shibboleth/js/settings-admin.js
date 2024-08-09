/**
 * ownCloud
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright (C) 2015-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
(function() {

	$(document).ready(function () {
		var $section = $('#shibboleth');
		var $select = $section.find('#shibboleth-mode');
		var $messages = $section.find('.msg');

		var $envSourceShibbolethSession = $section.find('#shibboleth-env-source-shibboleth-session');
		var $envSourceUid = $section.find('#shibboleth-env-source-uid');
		var $envSourceEmail = $section.find('#shibboleth-env-source-email');
		var $envSourceDisplayName = $section.find('#shibboleth-env-source-display-name');
		var $envSourceQuota = $section.find('#shibboleth-env-source-quota');

		// load initial config

		OC.msg.startAction($messages, t('user_shibboleth', 'Loading…'));
		$.when(
			$.get(
					OC.generateUrl('apps/user_shibboleth/mode'),
					'',
					function (data) {
							$select.val(data);
						},
					'json'
				),
			$.get(
					OC.generateUrl('apps/user_shibboleth/envSourceConfig'),
					'',
					function (data) {
							setOption($envSourceShibbolethSession, data.envSourceShibbolethSession);
							setOption($envSourceUid, data.envSourceUid);
							setOption($envSourceEmail, data.envSourceEmail);
							setOption($envSourceDisplayName, data.envSourceDisplayName);
							setOption($envSourceQuota, data.envSourceQuota);
						},
					'json'
				)
		).then(function() {
			var data = { status: 'success',	data: {message: t('user_shibboleth', 'Loaded')} };
			OC.msg.finishedAction($messages, data);
		}, function(result) {
			var data = { status: 'error', data:{message:result.responseJSON.message} };
			OC.msg.finishedAction($messages, data);
		});

		// set up ui 'binding' for mode source config

		$select.on('change', function () {
			OC.msg.startSaving($messages);
			$.ajax({
				type: 'PUT',
				url: OC.generateUrl('apps/user_shibboleth/mode'),
				data: {mode: $select.val()},
				dataType: 'json'
			}).success(function() {
				var data = { status:'success', data:{message:t('user_shibboleth', 'Saved')} };
				OC.msg.finishedSaving($messages, data);
			}).fail(function(result) {
				var data = { status: 'error', data:{message:result.responseJSON.message} };
				OC.msg.finishedSaving($messages, data);
			});
		});

		// set up ui 'binding' for environment source config

		setOption = function($select, value) {
			//create the option on the fly
			if ($select.find('option[value="'+value+'"]').length == 0) {
				$select.append('<option value="'+value+'">'+value+'</option>');
			}

			$select.find('option').prop('selected', false);
			$select.find('option[value="'+value+'"]').prop('selected', true);
		};

		saveEnvSourceConfig = function () {
			OC.msg.startSaving($messages);
			$.ajax({
				type: 'PUT',
				url: OC.generateUrl('apps/user_shibboleth/envSourceConfig'),
				data: {
					envSourceShibbolethSession: $envSourceShibbolethSession.val(),
					envSourceUid: $envSourceUid.val(),
					envSourceEmail: $envSourceEmail.val(),
					envSourceDisplayName: $envSourceDisplayName.val(),
					envSourceQuota: $envSourceQuota.val()
				},
				dataType: 'json'
			}).success(function() {
				var data = { status:'success', data:{message:t('user_shibboleth', 'Saved')} };
				OC.msg.finishedSaving($messages, data);
			}).fail(function(result) {
				var data = { status: 'error', data:{message:result.responseJSON.message} };
				OC.msg.finishedSaving($messages, data);
			});
		};

		$('#shibboleth-env-source').change(saveEnvSourceConfig);
	});

})();