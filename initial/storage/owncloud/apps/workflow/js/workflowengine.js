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
	OCA.Workflow.Engine = {
		$list: null,
		$newButton: null,
		$form: null,
		$typeSelection: null,
		flows: [],
		plugins: [],

		init: function() {
			var self = this;
			this.load();
			this.$list = $('div.workflows');
			this.$form = $('#workflow_form');
			this.$typeSelection = $('#workflow_types');
			this.$newButton = $('#new_workflow_button');

			this.$form.find('#workflow_submit').click(_.bind(this._onSubmitForm, this));
			this.$form.find('#workflow_cancel').click(_.bind(this._onCancelForm, this));
			this.$newButton.click(_.bind(this._toggleSubmitNewForm, this));
			this.$form.find('#workflow_add_condition').click(_.bind(this._addNewConditionClick, this));
			this.plugins = _.pluck(OC.Plugins.getPlugins('OCA.Workflow.Engine.Plugins'), 'plugin');

			/*
			 * Prefill the "Add condition" dropdown
			 */
			_.each(OCA.Workflow.Condition.availableConditions, function (description, value) {
				var $option = $('<option>').attr('value', value).text(description);
				self.$form.find('.available-conditions').append($option);
			});
		},

		_toggleSubmitNewForm: function() {
			this.$newButton.addClass('hidden');
			this.$form.removeClass('hidden');
			this.$typeSelection.addClass('hidden');
			if (this.$form.attr('data-flow-id') != 'undefined') {
				this._toggleForm(this.$form);
			}
			this._resetForm(this.$form, this.$typeSelection.val());
		},

		/**
		 * Add a new condition to the UI
		 */
		_addNewConditionClick: function() {
			var $dropDown = this.$form.find('.available-conditions');

			if ($dropDown.val()) {
				var $condition = OCA.Workflow.Condition.getCondition({
					rule: $dropDown.val(),
					value: undefined,
					operator: undefined
				});

				var $deleteButton = $('<a>');
				$deleteButton.addClass('icon-delete');
				$deleteButton.attr('title', t('workflow', 'Delete'));
				$deleteButton.click(function () {
					$(this).closest('div').remove();
				});

				$condition.append($deleteButton);
				this.$form.find('.conditions').append($condition);
			}

			$dropDown.val('');
		},

		/**
		 * Submit the form to add a new retention period
		 */
		_onSubmitForm: function () {
			var self = this;

			var conditions = [];
			_.each(this.$form.find('.conditions').children(), function (element) {
				var $element = $(element);
				var condition = {
					rule: $element.attr('data-condition-type'),
					value: OCA.Workflow.Condition.getValueFromCondition($element),
					operator: $element.find('[name=operator]').val()
				};
				conditions.push(condition);
			});

			var type = 'POST',
				url = OC.generateUrl('/apps/workflow/flow'),
				data = {
					name: this.$form.find('#workflow_name').val(),
					type: this.$form.attr('data-flow-type'),
					conditions: conditions,
					actions: []
				};

			_.each(this.plugins, function(plugin) {
				data.actions = plugin.getActions(data.type, data.actions, self.$form.find('.actions'));
			});

			if (data.actions == [] || data.actions == {}) {
				OC.Notification.showTemporary(t('workflow', 'Invalid action selected: {type}', data));
				return;
			}

			if (this.$form.attr('data-flow-id') != 'undefined') {
				type = 'PUT';
				url = OC.generateUrl('/apps/workflow/flow/' + this.$form.attr('data-flow-id'));
			}

			$.ajax({
				type: type,
				url: url,
				data: data
			}).done(function(response) {
				self._addFlow(response);
				if (type == 'PUT') {
					// Edited, convert the form back to "New workflow"
					self._toggleForm(self.$form);
				}
				self._resetForm(self.$form);
				self.$newButton.removeClass('hidden');
				self.$typeSelection.removeClass('hidden');
				self.$form.addClass('hidden');
				var $container = self.$list.find('.flow[data-flow-id=' + response.id + ']');
				self._scrollTo($container);
			}).fail(function (xhr) {
				OC.Notification.showTemporary(xhr.responseJSON.error);
			});
		},

		/**
		 * Cancel the form
		 */
		_onCancelForm: function () {
			var self = this,
				flowId = self.$form.attr('data-flow-id');

			self._resetForm(self.$form);
			self.$newButton.removeClass('hidden');
			self.$typeSelection.removeClass('hidden');
			self.$form.addClass('hidden');

			if (flowId != 'undefined') {
				var $container = self.$list.find('.flow[data-flow-id=' + flowId + ']');
				self._scrollTo($container);
			}
		},

		_setAvailableTypes: function (types) {
			var $types = $('#workflow_types');
			_.each(types, function(name, type) {
				var $option = $('<option>').attr('value', type).text(name);
				$types.append($option);
			});
		},

		/**
		 * Load the list of workflows from the server
		 */
		load: function () {
			var self = this;

			$.ajax({
				type: 'GET',
				url: OC.generateUrl('/apps/workflow/conditions')
			}).done(function(values) {
				OCA.Workflow.Condition.setConditionOptions(values.conditionValues);
				self._setAvailableTypes(values.types);
				self.loadFlows();
			}).fail(function () {
				OC.Notification.showTemporary(t('workflow', 'Could not load workflow types.'));
			});
		},

		/**
		 * Load the list of workflows from the server
		 */
		loadFlows: function () {
			var self = this;

			$.ajax({
				type: 'GET',
				url: OC.generateUrl('/apps/workflow/flow')
			}).done(function(flows) {
				_.each(flows, function(flow) {
					self._addFlow(flow);
				})
			}).fail(function () {
				OC.Notification.showTemporary(t('workflow', 'Could not load workflows.'));
			});
		},

		/**
		 * @param {Object} flow
		 */
		_addFlow: function (flow) {
			this.flows[flow.id] = flow;

			var $clone = this.$list.find('.flow[data-flow-id=' + flow.id + ']'),
				newElement = false;

			if ($clone.length == 0) {
				$clone = this.$list.find('.hidden').first().clone();
				newElement = true;
			}

			$clone.attr('data-flow-id', flow.id);
			$clone.attr('data-flow-type', flow.type);
			$clone.removeClass('hidden');
			if (flow.name) {
				$clone.find('h3 span').text(flow.name);
			} else {
				var $header = $('<em>');
				$header.text(t('workflow', 'No name given'));
				$clone.find('h3 span').html($header);
			}

			var $conditions = $clone.find('.conditions'),
				$actions = $clone.find('.actions');

			if (!newElement) {
				$conditions.text('');
			}

			if (!_.isArray(flow.conditions) || !flow.conditions.length) {
				var $element = $('<li>');
				$element.text(t('workflow', 'Always'));
				$conditions.append($element);
			} else {
				_.each(flow.conditions, function(condition) {
					var $element = $('<li>');
					$element.html(OCA.Workflow.Condition.translateCondition(condition));
					$conditions.append($element);
				});
			}

			_.each(this.plugins, function(plugin) {
				plugin.formatActionsForDisplay(flow.type, flow.actions, $actions);
			});

			if (newElement) {
				$clone.find('.workflow-edit').click(this._onEdit);
				$clone.find('.workflow-delete').click(this._onDelete);

				this.$list.append($clone);
			}
		},

		/**
		 * Populate the form with the data of this workflow
		 *
		 * @private
		 */
		_onEdit: function () {
			var $element = $(this).closest('div.flow'),
				self = OCA.Workflow.Engine,
				flow = self.flows[$element.attr('data-flow-id')],
				$conditions = self.$form.find('.conditions'),
				$actions = self.$form.find('.actions');

			if (self.$form.attr('data-flow-id') == 'undefined') {
				self._toggleForm(self.$form);
			}
			self._resetForm(self.$form);

			self.$newButton.removeClass('hidden');
			self.$typeSelection.removeClass('hidden');
			self.$form.removeClass('hidden');
			self.$form.attr('data-flow-id', flow.id);
			self.$form.attr('data-flow-type', flow.type);
			self.$form.find('#workflow_name').val(flow.name);
			_.each(flow.conditions, function(condition) {
				var $condition = OCA.Workflow.Condition.getCondition(condition);

				var $deleteButton = $('<a>');
				$deleteButton.addClass('icon-delete');
				$deleteButton.attr('title', t('workflow', 'Delete'));
				$deleteButton.click(function () {
					$(this).closest('div').remove();
				});

				$condition.append($deleteButton);
				$conditions.append($condition);
			});

			$actions.text('');
			_.each(self.plugins, function(plugin) {
				plugin.initialiseForm(flow.type, flow.actions, $actions);
			});

			self._scrollTo(self.$form);
		},

		/**
		 * Call back to delete a retention period
		 * @private
		 */
		_onDelete: function () {
			var $element = $(this).closest('div.flow'),
				flowId = $element.attr('data-flow-id');

			$.ajax({
				type: 'DELETE',
				url: OC.generateUrl('/apps/workflow/flow/' + flowId)
			}).done(function() {
				$element.slideUp(750);
				$element.remove();
			}).fail(function () {
				OC.Notification.showTemporary(t('workflow', 'Could not delete the workflow.'));
			});
		},

		/**
		 * Reset the form to be ready for a new/edit action again
		 * @param $form
		 * @param type
		 * @private
		 */
		_resetForm: function($form, type) {
			type = type || 'undefined';
			var $actions = $form.find('.actions');

			$form.attr('data-flow-id', 'undefined');
			$form.attr('data-flow-type', type);
			$form.find('#workflow_name').val('');
			$form.find('.conditions').text('');
			$actions.text('');

			_.each(this.plugins, function(plugin) {
				plugin.initialiseForm(type, [], $actions);
			});
		},

		/**
		 * Toggles the form between new and edit
		 *
		 * @param {jQuery} $form
		 * @private
		 */
		_toggleForm: function($form) {
			var $title = $form.find('h3'),
				toggleTitle = $title.attr('data-toggle-title');
			$title.attr('data-toggle-title', $title.text());
			$title.text(toggleTitle);

			var $button = $form.find('#workflow_submit'),
				toggleValue = $button.attr('data-toggle-value');
			$button.attr('data-toggle-value', $button.val());
			$button.val(toggleValue);
		},

		/**
		 * Scroll to a given element
		 *
		 * @param {jQuery} $container
		 * @private
		 */
		_scrollTo: function($container) {
			var $appContent = $('#app-content');

			$appContent.animate({
				scrollTop: $appContent.scrollTop() + $container.offset().top - 50
			}, 500);
		}
	};
})();
