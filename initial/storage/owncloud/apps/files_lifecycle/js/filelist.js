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
	 * @class OCA.Guest.ArchiveFileList
	 * @augments OCA.Files_Lifecycle.ArchiveFileList
	 *
	 * @classdesc Browsing the users archive
	 *
	 * @param $el container element with existing markup for the #controls
	 * and a table
	 * @param [options] map of options, see other parameters
	 */
	var ArchiveFileList = function($el, options) {
		this.initialize($el, options);
	};
	ArchiveFileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
		/** @lends OCA.Files_Lifecycle.ArchiveFileList.prototype */ {
			appName: t('files_lifecycle', 'Archived Files'),

			_allowSelection: false,

			initialize: function() {
				var result = OCA.Files.FileList.prototype.initialize.apply(this, arguments);
				// Set the empty template
				this.$el.find('#emptycontent').html('<div class="icon-archive"></div>' +
				'<h2>' + t('files_lifecycle', 'No archived files') + '</h2>' +
				'<p>' + t('files_lifecycle', 'Files and folders will appear here when they enter your archive') + '</p>');
				// Sort by mtime TODO sort by time in archive
				this.setSort('mtime', 'desc');
				OC.Plugins.attach('OCA.Files_Lifecycle.ArchiveFileList', this);
				return result;
			},

			_createRow: function(fileData) {
				// Add in the columns for the archive times
				var tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);
				// Remove the filesize
				tr.find('td.filesize').remove();
				// only show something if its not a folder
				if (fileData.mimetype !== 'httpd/unix-directory') {
					var archivedTime = $('<td>'+moment(fileData.archivedTime).fromNow()+'</td>');
					tr.find('td.date').before(archivedTime);
					var expiringTime = $('<td>'+moment(fileData.expiringTime).fromNow()+'</td>');
					tr.find('td.date').before(expiringTime);
				} else {
					var expiringTime = $('<td>N/A</td><td>N/A</td>');
					tr.find('td.date').before(expiringTime);
				}
				return tr;
			},

			setupUploadEvents: function() {
				// override and do nothing
			},
	
			getDownloadUrl: function() {
				// no downloads
				return '#';
			},
	
			updateStorageStatistics: function() {
				// no op because the trashbin doesn't have
				// storage info like free space / used space
			},
	
			linkTo: function(dir){
				return OC.linkTo('files', 'index.php')+"?view=archive&dir="+ encodeURIComponent(dir).replace(/%2F/g, '/');
			},
	
			/**
			 * Override to only return read permissions
			 */
			getDirectoryPermissions: function() {
				return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
			},

			/**
			 * Lazy load a file's preview.
			 *
			 * @param path path of the file
			 * @param mime mime type
			 * @param callback callback function to call when the image was loaded
			 * @param etag file etag (for caching)
			 */
			lazyLoadPreview : function(options) {
				var mime = options.mime;
				var ready = options.callback;
				var iconURL = OC.MimeType.getIconUrl(mime);
				ready(iconURL); // set mimeicon URL
			},

			_restoreCallback: function(file, data, success) {
				if (success !== "success") {
					OC.dialogs.alert(
						t('files_lifecycle', 'There was an error in the background whilst the files were beind restored. Please contact your system administrator if this message persists.'),
						t('files_lifecycle', 'Error restoring files!')
					);
					this.showFileBusyState(file.name, false);
					return;
				}
				this.remove(file.name, {updateSummary: true});
				this.updateEmptyContent();
				OC.Notification.showTemporary(data.message);
				this.showFileBusyState(file.name, false);
			},
		}
	);

	OCA.Files_Lifecycle.ArchiveFileList = ArchiveFileList;
})();
