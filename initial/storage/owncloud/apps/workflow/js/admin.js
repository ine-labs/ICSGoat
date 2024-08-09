/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2015 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
(function () {
	if (!OCA.Workflow) {
		OCA.Workflow = {
			Engine: null,
			Condition: null,
			Retention: null,

			init: function() {
				OC.SystemTags.collection.fetch({
					success: function () {
						OCA.Workflow.Engine.init();
						OCA.Workflow.Retention.init();
					}
				});
			},

			/**
			 * Autocomplete function for dropdown results
			 *
			 * @param {Object} query select2 query object
			 */
			_queryTagsAutocomplete: function(query) {
				OC.SystemTags.collection.fetch({
					success: function() {
						var results = OC.SystemTags.collection.filterByName(query.term);

						query.callback({
							results: _.invoke(results, 'toJSON')
						});
					}
				});
			}
		};
	}
})();

$(document).ready(function () {
	OCA.Workflow.init();
});
