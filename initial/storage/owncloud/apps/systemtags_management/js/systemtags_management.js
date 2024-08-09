/**
 * ownCloud System Tags Management
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
(function () {
	OCA.SystemTags_Management = _.extend(OCA.SystemTags_Management || {}, {

		$form: null,
		$tagSelecter: null,
		collection: null,

		init: function(collection) {
			var self = this;
			this.collection = collection;
			this.$form = $('#tagmanager_form');

			this.$form.find('#tagmanager_submit').click(_.bind(this._onSubmitForm, this));
			this.$form.find('#tagmanager_reset').click(_.bind(this._onResetForm, this));
			this.$form.find('.tagmanager-delete a').click(_.bind(this._onDeleteTag, this));
			this.$form.find('#tagmanager_namespace').change(_.bind(this._onSelectNamespace, this));

			this.$tagSelecter = $('#tagmanager_tag_id');
			this.$tagSelecter.select2({
				placeholder: t('systemtags_management', 'Edit an existing tag'),
				allowClear: false,
				multiple: false,
				separator: false,
				query: _.bind(this._queryTagsAutocomplete, this),

				id: function(tag) {
					return tag.id;
				},

				initSelection: function(element, callback) {
					var tag = self.collection.get($(element).val());

					if (!_.isUndefined(tag)) {
						callback(tag.toJSON());
					} else {
						callback($(element).val());
					}
				},

				formatResult: function (tag) {
					return OC.SystemTags.getDescriptiveTag(tag);
				},

				formatSelection: function (tag) {
					return OC.SystemTags.getDescriptiveTag(tag);
				},

				escapeMarkup: function(m) {
					// prevent double markup escape
					return m;
				}
			});

			this.$tagSelecter.on('select2-selecting', _.bind(this._onSelectTag, this));
		},

		/**
		 * @param {Object} data
		 * @private
		 */
		_onSelectTag: function (data) {
			var tag = this.collection.get(data.val);

			if (_.isUndefined(tag)) {
				OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'Tag does not exist anymore'));
				return;
			}

			if (this.$form.attr('data-tag-id') === 'undefined') {
				this._toggleForm(this.$form);
			}

			this.$form.attr('data-tag-id', data.val);
			this.$form.find('#tagmanager_name').val(tag.get('name'));

			var namespace = (tag.get('userVisible')) ? '1_' : '0_';
			namespace += (tag.get('userAssignable')) ? '1_' : '0_';
			namespace += (tag.get('userEditable')) ? '1' : '0';
			this.$form.find('#tagmanager_namespace').val(namespace).change();

			this.setSelectedGroups(tag.get('groups'));
		},

		_onSelectNamespace: function(event) {
			var $target = $(event.target);
			this.$form.find('.tagmanager_groups_container').toggleClass('hidden', $target.val() !== '1_0_0' && $target.val() !== '1_1_0');
			if (!this._groupSelectInitialized) {
				this._groupSelectInitialized = true;
				OC.Settings.setupGroupsSelect(this.$form.find('#tagmanager_groups'), {}, {excludeAdmins: true});
			}
			this.$form.find('#tagmanager_groups').val('').change();
		},

		/**
		 * Returns the selected groups, if the namespace is applicable
		 *
		 * @return group ids separated by pipe symbol
		 */
		getSelectedGroups: function() {
			var $el = this.$form.find('#tagmanager_groups');
			if ($el.hasClass('hidden') || $el.val() === '') {
				// group selection is not applicable
				return '';
			}
			return $el.val();
		},

		/**
		 * Sets the selected groups
		 *
		 * @param {String} groupIds group ids separated by pipe symbol
		 */
		setSelectedGroups: function(groupIds) {
			this.$form.find('#tagmanager_groups').val(groupIds).change();
		},

		/**
		 * Delete the selected tag
		 * @private
		 */
		_onDeleteTag: function () {
			var self = this,
				tagId = this.$form.attr('data-tag-id');
			if (tagId === 'undefined') {
				return;
			}

			var tag = this.collection.get(tagId);
			if (_.isUndefined(tag)) {
				OC.msg.finishedSuccess('#tagmanager_notifications_msg', t('systemtags_management', 'Tag successfully deleted'));
				return;
			}

			OC.msg.startAction('#tagmanager_notifications_msg', t('systemtags_management', 'Deleting…'));
			tag.destroy({
				success: function () {
					OC.msg.finishedSuccess('#tagmanager_notifications_msg', t('systemtags_management', 'Tag "{name}" successfully deleted', tag.toJSON(), 1, {'escape': false}));
					self._resetForm(self.$form);
					self.$tagSelecter.select2('val', '');
				},
				error: function (xhr) {
					if (xhr.status === 404) {
						OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'Tag "{name}" has already been deleted', tag.toJSON(), 1, {'escape': false}));
					} else {
						OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'Unknown error while deleting the tag'));
					}
					self._resetForm(self.$form);
				}
			});
		},

		/**
		 * Submit the form to add/update a tag
		 */
		_onSubmitForm: function () {
			var self = this,
				tagId = this.$form.attr('data-tag-id'),
				namespace = this.$form.find('#tagmanager_namespace').val().split('_'),
				tagData = {
					name: this.$form.find('#tagmanager_name').val().trim(),
					userVisible: namespace[0] === '1',
					userAssignable: namespace[1] === '1',
					userEditable: !(namespace[0] === '1' && namespace[1] === '1' && namespace[2] === '0'),
					groups: this.getSelectedGroups()
				};

			this.$form.find('#tagmanager_submit').prop('disabled', true);

			OC.msg.startSaving('#tagmanager_notifications_msg');
			if (tagData.name === '') {
				this.$form.find('#tagmanager_submit').prop('disabled', false);
				OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'No tag name supplied'));
			} else if (!tagData.userVisible && tagData.userAssignable) {
				this.$form.find('#tagmanager_submit').prop('disabled', false);
				OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'Invalid tag namespace'));
			} else if (tagId === 'undefined') {
				this.collection.create(tagData, {
					success: function () {
						self._resetForm(self.$form);
						OC.SystemTags.collection.add(self.collection.models, [{merge: true}]);
						OC.msg.finishedSuccess('#tagmanager_notifications_msg', t('systemtags_management', 'Tag "{name}" successfully created', tagData, 1, {'escape': false}));
					},
					error: function (collection, xhr) {
						if (xhr.status === 409) {
							OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'Tag already exists'));
						} else {
							OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'Unknown error while creating the tag'));
						}
						self.$form.find('#tagmanager_submit').prop('disabled', false);
					}
				});
			} else {
				var tag = this.collection.get(tagId);
				if (_.isUndefined(tag)) {
					OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'Tag "{name}" does not exist anymore', tagData, 1, {'escape': false}));
					this._resetForm(self.$form);
				} else {
					if (namespace[0] === '1' && namespace[1] === '0' && namespace[2] === '0') {
						tagData.userEditable = false;
					}
					tag.save(tagData, {
						success: function () {
							self._resetForm(self.$form);
							self.$tagSelecter.select2('val', tagId);
							OC.msg.finishedSuccess('#tagmanager_notifications_msg', t('systemtags_management', 'Tag "{name}" successfully updated', tagData, 1, {'escape': false}));
						},
						error: function (collection, xhr) {
							if (xhr.status === 409) {
								OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'Tag already exists'));
							} else {
								OC.msg.finishedError('#tagmanager_notifications_msg', t('systemtags_management', 'Unknown error while updating the tag'));
							}
							self.$form.find('#tagmanager_submit').prop('disabled', false);
						}
					});
				}
			}
		},

		/**
		 * Reset button has been pressed
		 * @private
		 */
		_onResetForm: function () {
			this._resetForm(this.$form);
			this.$tagSelecter.select2('val', '');
		},

		/**
		 * Reset the form again
		 *
		 * @param {jQuery} $form
		 * @private
		 */
		_resetForm: function ($form) {
			if ($form.attr('data-tag-id') !== 'undefined') {
				this._toggleForm($form);
				$form.attr('data-tag-id', 'undefined');
			}

			$form.find('#tagmanager_name').val('');
			$form.find('#tagmanager_submit').prop('disabled', false);
			$form.find('#tagmanager_namespace').val('1_1_1').change();
			$form.find('#tagmanager_groups').val('').change();
		},

		/**
		 * Toggle the form between edit and add
		 *
		 * @param {jQuery} $form
		 * @private
		 */
		_toggleForm: function ($form) {
			var $title = $form.find('h3'),
				toggleTitle = $title.attr('data-toggle-title');
			$title.attr('data-toggle-title', $title.text());
			$title.text(toggleTitle);

			var $button = $form.find('#tagmanager_submit'),
				toggleValue = $button.attr('data-toggle-value');
			$button.attr('data-toggle-value', $button.val());
			$button.val(toggleValue);

			$form.find('.tagmanager-delete').toggleClass('hidden');
			$form.find('#tagmanager_reset').toggleClass('hidden');
		},

		/**
		 * Autocomplete function for dropdown results
		 *
		 * @param {Object} query select2 query object
		 */
		_queryTagsAutocomplete: function(query) {
			var self = this;
			this.collection.fetch({
				success: function() {
					var results = self.collection.filterByName(query.term);

					query.callback({
						results: _.invoke(results, 'toJSON')
					});
				}
			});
		}
	});
})();

$(document).ready(function () {
	// TODO: lazy fetch
	var collection = new OCA.SystemTags_Management.SystemTagsAdminCollection();
	collection.fetch({
		success: function () {
			OCA.SystemTags_Management.init(collection);
		}
	});
});
