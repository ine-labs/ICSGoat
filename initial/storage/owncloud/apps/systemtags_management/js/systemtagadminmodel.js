/**
 * ownCloud System Tags Management
 *
 * @author Vincent Petry <pvince81@owncloud.com>
 * @copyright 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

(function(OC, OCA) {
	var NS_OWNCLOUD = 'http://owncloud.org/ns';
	/**
	 * @class OCA.SystemTags.SystemTagsAdminModel
	 * @classdesc
	 *
	 * System tag
	 *
	 */
	var SystemTagsAdminModel = OC.SystemTags.SystemTagModel.extend(
		/** @lends OC.SystemTags_Management.SystemTagsAdminModel.prototype */ {

		defaults: {
			userVisible: true,
			userAssignable: true
		},

		davProperties: {
			'id': '{' + NS_OWNCLOUD + '}id',
			'name': '{' + NS_OWNCLOUD + '}display-name',
			'userVisible': '{' + NS_OWNCLOUD + '}user-visible',
			'userEditable': '{' + NS_OWNCLOUD + '}user-editable',
			'userAssignable': '{' + NS_OWNCLOUD + '}user-assignable',
			'groups': '{' + NS_OWNCLOUD + '}groups'
		},

		parse: function(data) {
			var parsedData = OC.SystemTags.SystemTagModel.prototype.parse.apply(this, arguments);
			// keep them as string because serialization can't make them a proper array...
			parsedData.groups = data.groups || '';
			return parsedData;
		}
	});

	OCA.SystemTags_Management = OCA.SystemTags_Management || {};
	OCA.SystemTags_Management.SystemTagsAdminModel = SystemTagsAdminModel;
})(OC, OCA);

