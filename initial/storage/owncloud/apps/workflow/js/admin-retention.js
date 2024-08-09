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
	OCA.Workflow.Retention = {

		retentionTags: [],
		$list: null,
		$newForm: null,

		init: function() {
			this.load();
			this.$list = $('table.retention');
			this.$newForm = $('#retention_new_period');

			this.$newForm.find('#retention_submit').click(_.bind(this._onSubmitNewForm, this));
			$('#new-retention-button').click(_.bind(this._toggleSubmitNewForm, this));

			this._initFolderRetention();

			this.$newForm.find('#retention_tag_id').select2({
				placeholder: t('workflow', 'Select a tag'),
				allowClear: false,
				multiple: false,
				separator: false,
				query: _.bind(this._queryTagsAutocomplete, this),

				id: function(tag) {
					return tag.id;
				},

				initSelection: function(element, callback) {
					var tag = OC.SystemTags.collection.get($(element).val());

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
		},

		_initFolderRetention: function() {
			var $folderRetention = $('#folder_retention'),
				$folderRetentionPeriod = $('#folder_retention_period');
			$folderRetention.on('change', function () {
				var $option = $(this);
				if (!$option.prop('checked')) {
					OC.AppConfig.setValue('workflow', 'folder_retention', 0);
					$folderRetentionPeriod.prop('disabled', true);
				} else {
					OC.AppConfig.setValue('workflow', 'folder_retention', 1);
					var period = Math.max(1, parseInt($folderRetentionPeriod.val(), 10));
					OC.AppConfig.setValue('workflow', 'folder_retention_period', period);
					$folderRetentionPeriod.prop('disabled', false);
				}
			});

			if (!$folderRetention.prop('checked')) {
				$folderRetentionPeriod.prop('disabled', true);
			}

			$folderRetentionPeriod.on('change', function () {
				OC.AppConfig.setValue('workflow', 'folder_retention_period', parseInt($(this).val(), 10));
			});
		},

		/**
		 * Autocomplete function for dropdown results
		 *
		 * @param {Object} query select2 query object
		 */
		_queryTagsAutocomplete: function(query) {
			var self = this;
			OC.SystemTags.collection.fetch({
				success: function() {
					var results = OC.SystemTags.collection.filterByName(query.term);

					// Filter out tags that already have a retention period
					results = _.filter(results, function (tag) {
						return self.retentionTags.indexOf(tag.id) === -1; // False = kill
					});

					query.callback({
						results: _.invoke(results, 'toJSON')
					});
				}
			});
		},

		_toggleSubmitNewForm: function() {
			$('#new-retention-button').toggleClass('hidden');
			$('#retention_new_period').toggleClass('hidden');
		},

		/**
		 * Submit the form to add a new retention period
		 */
		_onSubmitNewForm: function () {
			var self = this,
				tagId = this.$newForm.find('#retention_tag_id').val();

			if (!tagId) {
				OC.Notification.showTemporary(t('workflow', 'No tag selected'));
				return;
			}

			$.ajax({
				type: 'POST',
				url: OC.generateUrl('/apps/workflow/retention/' + tagId),
				data: {
					numUnits: this.$newForm.find('#retention_period').val(),
					unit: this.$newForm.find('#retention_period_unit').val()
				}
			}).done(function(response) {
				self._addPeriod(response);
				self.$newForm.find('#retention_tag_id').select2('val', '');
				self._toggleSubmitNewForm();
			}).fail(function (xhr) {
				OC.Notification.showTemporary(xhr.responseJSON.error);
			});
		},

		/**
		 * Load the list of retention periods from the server
		 */
		load: function () {
			var self = this;

			$.ajax({
				type: 'GET',
				url: OC.generateUrl('/apps/workflow/retention')
			}).done(function(retentionPeriods) {
				_.each(retentionPeriods, function(period) {
					self._addPeriod(period);
				})
			}).fail(function () {
				OC.Notification.showTemporary(t('workflow', 'Could not load retention periods.'));
			});
		},

		/**
		 * @param period
		 */
		_addPeriod: function (period) {
			var $clone = this.$list.find('.hidden').first().clone();

			$clone.attr('data-tag-id', period.tagId);
			$clone.removeClass('hidden');

			var tag = OC.SystemTags.collection.get(period.tagId);
			if (!_.isUndefined(tag)) {
				// Display the tag name with the details
				var $tag = OC.SystemTags.getDescriptiveTag(tag);
				$clone.find('.tag_name').html($tag);

				this.retentionTags.push(period.tagId);
			} else {
				// Error: Tag does not exist anymore
				var $error = OC.SystemTags.getDescriptiveTag(period.tagId);
				$clone.find('.tag_name').html($error);
			}

			$clone.find('.retention_period').val(period.numUnits).on('change', function () {
				$clone.find('.retention-update').removeClass('hidden');
			});
			$clone.find('.retention_period_unit').val(period.unit).on('change', function () {
				$clone.find('.retention-update').removeClass('hidden');
			});

			$clone.find('.retention-delete').click(this._onDelete);
			$clone.find('.retention-update').addClass('hidden');
			$clone.find('.retention-update').click(this._onUpdate);

			this.$list.append($clone);
		},

		/**
		 * Call back to delete a retention period
		 * @private
		 */
		_onDelete: function () {
			var $element = $(this).closest('tr'),
				tagId = $element.attr('data-tag-id'),
				self = OCA.Workflow.Retention;
			$element.find('.retention-delete').addClass('hidden');
			$element.find('.retention-update').addClass('hidden');

			$.ajax({
				type: 'DELETE',
				url: OC.generateUrl('/apps/workflow/retention/' + tagId)
			}).done(function() {
				$element.slideUp(750);
				$element.remove();

				self.retentionTags = _.filter(self.retentionTags, function (element) {
					return element != tagId;
				});
			}).fail(function () {
				$element.find('.retention-delete').removeClass('hidden');
				OC.Notification.showTemporary(t('workflow', 'Could not delete the retention period.'));
			});
		},

		/**
		 * Call back to update a retention period
		 * @private
		 */
		_onUpdate: function () {
			var $element = $(this).closest('tr'),
				tagId = $element.attr('data-tag-id');
			$element.find('.retention-delete').addClass('hidden');
			$element.find('.retention-update').addClass('hidden');

			$.ajax({
				type: 'PUT',
				url: OC.generateUrl('/apps/workflow/retention/' + tagId),
				data: {
					numUnits: $element.find('.retention_period').val(),
					unit: $element.find('.retention_period_unit').val()
				}
			}).done(function() {
				$element.find('.retention-delete').removeClass('hidden');
			}).fail(function () {
				$element.find('.retention-delete').removeClass('hidden');
				$element.find('.retention-update').removeClass('hidden');
				OC.Notification.showTemporary(t('workflow', 'Could not update the retention period.'));
			});
		}
	};
})();
