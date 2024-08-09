/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

(function() {
	OCA.Workflow.AutoTaggingPlugin = {

		/**
		 * Format actions for display
		 *
		 * @param {String} type of the flow
		 * @param {Object} actions Array with the actions
		 * @param {jQuery} $actions jQuery handle which can be filled with descriptions for the actions
		 * @return {Object} Array with the actions
		 */
		getActions: function (type, actions, $actions) {
			if (type !== 'workflow_autotagging') {
				return actions;
			}

			return {
				setTags: $actions.find('.tags').select2('val')
			};
		},

		/**
		 * Format actions for display
		 *
		 * @param {String} type of the flow
		 * @param {Object} actions Array with the actions
		 * @param {jQuery} $actions jQuery handle which can be filled with descriptions for the actions
		 */
		formatActionsForDisplay: function (type, actions, $actions) {
			if (type !== 'workflow_autotagging') {
				return;
			}

			$actions.text(t('workflow', 'Add tags:') + ' ');
			var $tagList = $('<ul>').addClass('tags');

			_.each(actions.setTags, function(tagId) {
				var $element = $('<li>'),
					tag = OC.SystemTags.collection.get(tagId);
				$element.html(OC.SystemTags.getDescriptiveTag(tag));
				$tagList.append($element);
			});

			$actions.append($tagList);
		},

		/**
		 * Initialise the form to be ready for a new/edit action again
		 *
		 * @param {String} type the form is initialised with
		 * @param {Object} actions Array with the actions that have been set.
		 * @param {jQuery} $actions jQuery handle which can be filled with options for the actions
		 */
		initialiseForm: function (type, actions, $actions) {
			if (type !== 'workflow_autotagging') {
				return;
			}

			var $label = $('<strong>');
			$label.text(t('workflow', 'Add tags:') + ' ');
			$actions.append($label);

			var $input = $('<input>');
			$input.attr('type', 'text').addClass('tags');
			$actions.append($input);

			$input.select2(this._setTagsSelect2);
			if (!_.isUndefined(actions.setTags)) {
				$input.select2('val', actions.setTags);
			}
		},

		_setTagsSelect2: {
			placeholder: t('workflow', 'Select tags'),
			allowClear: false,
			multiple: true,
			separator: ',',
			query: _.bind(OCA.Workflow._queryTagsAutocomplete, this),

			id: function(tag) {
				return tag.id;
			},

			initSelection: function(element, callback) {
				var tagIds = $(element).val().split(','),
					tags = [];

				_.each(tagIds, function(tagId) {
					var tag = OC.SystemTags.collection.get(tagId);
					if (!_.isUndefined(tag)) {
						tags.push(tag.toJSON());
					} else {
						tags.push(tagId);
					}
				});

				callback(tags);
			},

			formatResult: function (tag) {
				return OC.SystemTags.getDescriptiveTag(tag);
			},

			formatSelection: function (tag) {
				return OC.SystemTags.getDescriptiveTag(tag)[0].outerHTML;
			},

			escapeMarkup: function(m) {
				// prevent double markup escape
				return m;
			}
		}
	};


})();

OC.Plugins.register('OCA.Workflow.Engine.Plugins', OCA.Workflow.AutoTaggingPlugin);
