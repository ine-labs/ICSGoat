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
(function(OCA) {

	/**
	 * @class OCA.Files_Lifecycle.LifecyleInfoView
	 * @classdesc
	 *
	 * Displays a file's system tags
	 *
	 */
	var LifecycleInfoView = OCA.Files.DetailFileInfoView.extend(
		/** @lends OCA.Files_Lifecycle.LifecycleInfoView.prototype */ {

	_rendered: false,
	
		_details: null,
		
		_client: null,

		className: 'lifecycleInfoView',

		initialize: function(options) {
			var self = this;
			options = options || {};
			this.client = new OC.Files.Client({
				host: OC.getHost(),
				root: OC.linkToRemoteBase('dav') + '/files/' + OC.getCurrentUser().uid,
				useHTTPS: OC.getProtocol() === 'https'
			});
		},

		setFileInfo: function(fileInfo) {
			var self = this;
			if (!this._rendered) {
				this.render();
			}
			self.$el.attr('data-color', 'none');
			self.$el.text('');
			if (fileInfo && fileInfo !== null && fileInfo.attributes.mimetype != 'httpd/unix-directory') {
				this.client.getFileInfo(
					fileInfo.attributes.path+'/'+fileInfo.attributes.name,
					{
						properties: [OC.Files.Client.PROPERTY_LIFECYCLE_ARCHIVING_TIME]
					}
					).then(function(status, data) {
						self.$el.attr('data-color', 'info');
						if (data != undefined) {
							if (!moment(new Date(data.archivingTime)).isAfter()) {
								self.$el.text(t('files_lifecycle', 'Scheduled for archive today!'));
								self.$el.attr('data-color', 'danger');
							} else {
								var remainingDays = moment().diff(new Date(data.archivingTime), 'days');
								var text = t('files_lifecycle', 'Scheduled for archive {diff}', {diff: moment(new Date(data.archivingTime)).fromNow()});
								self.$el.text(text);
								if (remainingDays >= -5) {
									self.$el.attr('data-color', 'warning');
								}
							}
						} else {
							self.$el.text(t('files_lifecycle', 'Not scheduled for archive'));
						}
					});
			}
		},
		remove: function() {
			this._details.remove();
		}
	});

	OCA.Files_Lifecycle.LifecycleInfoView = LifecycleInfoView;

})(OCA);

