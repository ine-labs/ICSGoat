/**
 * ownCloud
 *
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
$(document).ready(function() {
	var button = $('#filesLdapHomeSubmit');
	button.button();
	button.click(function(event) {
		event.preventDefault();
		$.post(
			OC.filePath('files_ldap_home','ajax','set.php'),
			$('#filesLdapHome').serialize(),
			function (result) {
				var bgcolor = button.css('background');
				if (result.status == 'success') {
					//the dealing with colors is a but ugly, but the jQuery version in use has issues with rgba colors
					button.css('background', '#fff');
					button.effect('highlight', {'color':'#A8FA87'}, 5000, function() {
						button.css('background', bgcolor);
					});
				} else {
					button.css('background', '#fff');
					button.effect('highlight', {'color':'#E97'}, 5000, function() {
						button.css('background', bgcolor);
					});
				}
			}
		);
	});

	//Behavior for radio button change
	$('#filesLdapHomeAttributeModeSpec').change(function() {
		if($(this).prop('checked', true)) {
			$('.filesLdapHomeIndent + input').each(function() {
				$(this).removeAttr('disabled');
			});
			$('#filesLdapHomeAttribute').attr('disabled', 'disabled');
		}
	});

	//Behavior for radio button change
	$('#filesLdapHomeAttributeModeUni').change(function() {
		if($(this).prop('checked', true)) {
			$('.filesLdapHomeIndent + input').each(function() {
				$(this).attr('disabled', 'disabled');
			});
			$('#filesLdapHomeAttribute').removeAttr('disabled');
		}
	});
});
