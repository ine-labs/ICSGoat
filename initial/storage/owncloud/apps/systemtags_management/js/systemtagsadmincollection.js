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
	/**
	 * @class OCA.SystemTags_Management.SystemTagsAdminCollection
	 * @classdesc
	 *
	 * Collection of system tags
	 *
	 */
	var SystemTagsAdminCollection = OC.SystemTags.SystemTagsCollection.extend(
		/** @lends OC.SystemTags_Management.SystemTagsAdminCollection.prototype */ {

		model: OCA.SystemTags_Management.SystemTagsAdminModel
	});

	OCA.SystemTags_Management = OCA.SystemTags_Management || {};
	OCA.SystemTags_Management.SystemTagsAdminCollection = SystemTagsAdminCollection;
})(OC, OCA);

