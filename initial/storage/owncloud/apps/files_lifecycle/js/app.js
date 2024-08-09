/**
 * @author Tom Needham <tom@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
(function() {

	/**
	 * @namespace
	 */
	if (!OCA.Files_Lifecycle) {
		OCA.Files_Lifecycle = {};
	}

	OCA.Files_Lifecycle.App = {

			_initialized: false,
			_archiveFileList: null,

			// Setup the instance of the archive browser file list
			initialize: function($el) {
				if (this._initialized) {
					return;
				}
				this._initialized = true;
				// Instantiate
				var urlParams = OC.Util.History.parseUrlQuery();
				this._archiveFileList = new OCA.Files_Lifecycle.ArchiveFileList(
					$el,
					{
						id: 'lifecycle.archive',
						scrollContainer: $('#app-content'),
						fileActions: this._getActions(),
						dragOptions: false,
						detailsViewEnabled: false,
						folderDropOptions: false,
						scrollTo: urlParams.scrollto,
						config: OCA.Files.App.getFilesConfig(),
						filesClient: new OC.Files.Client({
							host: OC.getHost(),
							root: OC.linkToRemoteBase('dav') + '/archive/' + OC.getCurrentUser().uid + '/files/',
							useHTTPS: OC.getProtocol() === 'https'
						})
					}
				);
			
				// Hide the searchbox
				$('form.searchbox').hide();
				// Hide the size summary
				$('td.filesize').hide();
				

			},

			/**
			 * Defines the restore FileAction
			 */
			_restoreAction: function() {
				return {
					name: 'Restore',
					mime: 'all',
					permissions: OC.PERMISSION_READ,
					iconClass: 'icon-restore',
					type: OCA.Files.FileActions.TYPE_INLINE,
					actionHandler: function (filename, context) {
						var fileList = context.fileList;
						fileList.showFileBusyState(filename, true);
						var file = fileList.findFile(filename);
						$.ajax({
							method: "POST",
							url: OC.generateUrl('apps/files_lifecycle/restore'),
							data: { id: file.id }
						}).always(
							_.bind(fileList._restoreCallback, fileList, file)
						);
					},
					displayName: t('files_lifecycle', 'Restore') 
				};
			},

			/**
			 * Returns the FileActions for the archive file list rows
			 */
			_getActions: function() {
				var fileActions = new OCA.Files.FileActions();
				fileActions.clear();
				if(typeof oc_files_lifecycle !== 'undefined' && oc_files_lifecycle !== null) {
					var options = oc_files_lifecycle;
					// Show restore action if user can restore, or impersonator can, and we are impersonated
					if (options.userAllowedToRestore || (options.impersonatorAllowedToRestore && options.impersonated)) {
						fileActions.registerAction(this._restoreAction());
					}
				}
				fileActions.register('dir', 'open-in-archive', OC.PERMISSION_READ, '', function (filename, context) {
					var dir = context.$file.attr('data-path') || context.fileList.getCurrentDirectory();
					context.fileList.changeDirectory(OC.joinPaths(dir, filename), true, false, parseInt(context.$file.attr('data-id'), 10));
				});
				fileActions.setDefault('dir', 'open-in-archive');
				return fileActions;
			},

			/**
			 * In case the actions are updated later, make sure we only have the restore action still
			 */
			_onActionsUpdated: function(ev) {
				_.each([this._archiveFileList], function(list) {
					if (!list) {
						return;
					}

					list.fileActions.clear();
					list.fileActions.registerAction(this._restoreAction());

				});
			},

			emptyArchiveList: function() {
				if (this._archiveFileList) {
					this._archiveFileList.$fileList.empty();
				}
			},

			/**
			* Destroy the app
			*/
			destroy: function() {
				OCA.Files.fileActions.off('setDefault.app-lifecycle', this._onActionsUpdated);
				OCA.Files.fileActions.off('registerAction.app-lifecycle', this._onActionsUpdated);
				this.emptyArchiveList();
				this._archiveFileList = null;

				// Reset search
				$('from.searchbox').show();
				$('td.filesize').hide();
			},
	}
	
	if (!window.TESTING) {
		$(document).ready(function () {
			// Setup
			$('#app-content-archive').one('show', function() {
				var App = OCA.Files_Lifecycle.App;
				App.initialize($('#app-content-archive'));
			});
			// Shutdown
			$('#app-content-archive').one('hide', function() {
				var App = OCA.Files_Lifecycle.App;
				App.destroy();
			});
		});
	}
  
})();
