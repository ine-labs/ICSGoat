$(function () {

	if (!OCA.Firewall) {
		OCA.Firewall = {};
	}

	OCA.Firewall = {

		validationError: false,

		ruleValues: {
			deviceType: {},
			userGroup: {},
			request: {}
		},

		setValidationError: function(error) {
			this.validationError = error;
		},

		getValidationError: function() {
			return this.validationError;
		},

		getClientDeviceOptions: function($rule) {
			return this.getSelectOptions(
				$rule.find('.userDevices'),
				this.ruleValues.deviceType
			);
		},

		getUserGroupOptions: function($rule) {
			return this.getSelectOptions(
				$rule.find('.userGroups'),
				this.ruleValues.userGroup
			);
		},

		getRequestTypeOptions: function($rule) {
			return this.getSelectOptions(
				$rule.find('.requestType'),
				this.ruleValues.request
			);
		},

		getSelectOptions: function($el, data) {
			var filterOptions = [];

			_.each(data, function(label, value) {
				filterOptions.push($('<option>', {
					value: value,
					text: label
				}));
			});

			$el.append(filterOptions);
		},

		getFilterIPv4: function(id, label, placeholder) {
			return this.getFilterIPvX(
				id, label, placeholder,
				/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/(\d|[1-2]\d|3[0-2]))$/
			);
		},

		getFilterIPv6: function(id, label, placeholder) {
			return this.getFilterIPvX(
				id, label, placeholder,
				/^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$|^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*(\/(\d|\d\d|1[0-1]\d|12[0-8]))$/
			);
		},

		getFilterIPvX: function(id, label, placeholder, validationRegex) {
			return {
				id: id,
				label: label,
				type: 'string',
				validation: {
					format: validationRegex
				},
				placeholder: placeholder,
				operators: ['equal', 'not_equal']
			};
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

	// Cache Rulebuilder selector
	var $ruleBuilder = $('#rulebuilder');


	// Set localization for timepicker
	$.timepicker.setDefaults($.timepicker.regional[$('html').prop('lang')]);

	// Extend and add new methods
	$.extend($.fn.queryBuilder.constructor.prototype, {

		/**
		 * Perform action when rule's filter is changed
		 * @param $rule {jQuery} (<li> element)
		 * @param filterId {string}
		 */
		updateRuleFilter: function ($rule, filterId) {
			var filter = filterId != '-1' ? this.getFilterById(filterId) : null;

			var $regexValue = $rule.find('input[name=regexValue]');
			if (filterId !== 'regex' && $regexValue) {
				$regexValue.remove();
			}

			this.createRuleOperators($rule, filter);
			this.createRuleInput($rule, filter);

			$rule.data('queryBuilder.filter', filter);
		},
		/**
		 *
		 * @returns {*|QueryBuilder.DEFAULTS.filters|Array|QueryBuilder.filters|Sizzle.selectors.filters|fb.selectors.filters}
		 */
		getFilters: function () {
			return this.filters;
		},
		/**
		 * Get an object representing current rules
		 * @return {object}
		 */
		getRules: function () {
			this.markRuleAsError(this.$el.find('.rule-container'), false);

			var $groups = this.$el.find('>.rules-group-container'),
				mainout = [],
				that = this;

			$groups.each(function (k, group) {
				mainout.push((function parse (group) {
					var out = {},
						$group = $(group),
						$elements = $group.find('>.rules-group-body>.rules-list>*');

					out.name = $group.find('.groupName').val();
					out.rules = [];

					for (var i = 0, l = $elements.length; i < l; i++) {
						var $rule = $elements.eq(i),
							rule;

						if ($rule.hasClass('rule-container')) {

							var filterId = that.getRuleFilter($rule);

							if (filterId == '-1') {
								continue;
							}

							var filter = that.getFilterById(filterId),
								operator = that.getOperatorByType(that.getRuleOperator($rule)),
								value = null;

							if (operator.accept_values !== 0) {
								value = that.getRuleValue($rule, filter, operator);


								if (filter.valueParser) {
									value = filter.valueParser.call(this, $rule, value, filter, operator);
								}

								var valid = that.validateValue($rule, value, filter, operator);
								if (valid !== true) {
									that.markRuleAsError($rule, true);
									that.triggerValidationError(valid, $rule, value, filter, operator);
									return {};
								}
							}

							rule = {
								id: filter.id,
								field: filter.field,
								type: filter.type,
								input: filter.input,
								operator: operator.type,
								value: value
							};

							out.rules.push(rule);
						}
						else {
							rule = parse($rule);
							if (!$.isEmptyObject(rule)) {
								out.rules.push(rule);
							}
							else {
								return {};
							}
						}
					}

					if (out.rules.length === 0) {
						that.triggerValidationError('empty_group', $group, null, null, null);

						return {};
					}

					return out;
				}(group)));
			});
			return mainout;

		},
		/**
		 * Set rules from object
		 * @param data {object}
		 */
		setRules: function (data) {
			this.clear();

			var $container = this.$el,
				that = this;

			$(data).each(function (k, data) {
				if (!data || !data.rules || data.rules.length === 0) {
					$.error('Incorrect data object passed');
				}

				(function add (data, $container) {
					var $group = that.addGroup($container, false),
						$ul = $group.find('>.rules-group-body>.rules-list');

					$group.find('.groupName').val(data.name);

					$.each(data.rules, function (i, rule) {
						if (rule.rules && rule.rules.length > 0) {
							if (!that.settings.allow_groups) {
								$.error('Groups are disabled');
							}
							else {
								add(rule, $container);
							}
						}
						else {
							if (rule.id === undefined) {
								$.error('Missing rule field id');
							}
							if (rule.value === undefined) {
								rule.value = '';
							}
							if (rule.operator === undefined) {
								rule.operator = 'equal';
							}

							var $rule = that.addRule($ul),
								filter = that.getFilterById(rule.id),
								operator = that.getOperatorByType(rule.operator);

							$rule.find('.rule-filter-container select[name$=_filter]').val(rule.id).trigger('change');
							$rule.find('.rule-operator-container select[name$=_operator]').val(rule.operator).trigger('change');

							if (operator.accept_values !== 0) {
								that.setRuleValue($rule, rule, filter, operator);
							}

							if (filter.onAfterSetValue) {
								filter.onAfterSetValue.call(that, $rule, rule.value, filter, operator);
							}
						}
					});

				}(data, $container));
			});
		},
		/**
		 * Returns group HTML
		 * @param group_id {string}
		 * @return {string}
		 */
		getGroupTemplate: function (group_id) {
			var h = '\
				<dl id="' + group_id + '" class="rules-group-container" ' + (this.settings.sortable ? 'draggable="true"' : '') + '> \
				  <dt class="rules-group-header"> \
				  	' + (this.settings.sortable ? '<div class="drag-handle"><i class="' + this.settings.icons.sort + '"></i></div>' : '') + ' \
					<div class="btn-group"> \
						<input type="text" name="groupName" class="groupName" placeholder="Group Name"/> \
					</div> \
				  </dt> \
				  <dd class=rules-group-body> \
					<ul class=rules-list></ul> \
				  </dd> \
				  <dt>\
					<div class="btn-group management-btns pull-right"> \
					<button type="button" class="btn btn-xs btn-success" data-add="rule"> \
					<i class="' + this.settings.icons.add_rule + '"></i> ' + this.lang.add_rule + ' \
					</button> \
							  ' + (this.settings.allow_groups ? '<button type="button" class="btn btn-xs btn-success" data-add="group"> \
					<i class="' + this.settings.icons.add_group + '"></i> ' + this.lang.add_group + ' \
					</button>' : '') + ' \
					<button type="button" class="btn btn-xs btn-danger" data-delete="group"> \
					<i class="' + this.settings.icons.remove_group + '"></i> ' + this.lang.delete_group + ' \
					</button> \
					</div> \
				</dt>\
				</dl>';

			return h;
		},

		/**
		 * Tries to delete a group after checks
		 * @param $group {jQuery}
		 */
		deleteGroup: function ($group) {
			if (this.settings.readonly_behavior.delete_group) {
				$group.remove();
			}

			var that = this,
			    keepGroup = false;

			$group.find('>.rules-group-body>.rules-list>li').each(function() {
				var $element = $(this);

				if ($element.hasClass('rule-container')) {
					if ($element.hasClass('disabled')) {
						keepGroup = true;
					}
					else {
						$element.remove();
					}
				}
				else {
					that.deleteGroup($element);
				}
			});

			if (!keepGroup) {
				$group.remove();
				if ($ruleBuilder.queryBuilder('getRules').length === 0) {
					this.addGroup(this.$el);
				}
			}
		}

	});

	var ruleCount = 1;


	$ruleBuilder.queryBuilder({
		sortable: false,
		lang: {
			"operator_equal": "is",
			"operator_not_equal": "is not"
		},
		icons: {
			add_group: 'fa fa-plus',
			add_rule: 'fa fa-plus',
			remove_group: 'fa fa-remove',
			remove_rule: 'fa fa-remove',
			sort: 'fa fa-exchange fa-rotate-90',
			error: 'fa fa-warning-sign'
		},
		onAfterAddGroup: function ($group) {
			// We need to reset the rule numbers for each group
			ruleCount = 1;
		},

		onAfterAddRule: function ($rule) {
			$rule.find('.drag-handle').append(ruleCount);
			ruleCount++;
		},

		onValidationError: function ($target, error) {
			OCA.Firewall.setValidationError(error);
		},

		filters: [
			/*
			 * basic
			 */
			{
				id: 'regex',
				label: 'Regular Expression',
				type: 'string',
				input: function ($rule, filter) {
					// Move text area before operator
					if (!$rule.find('[name=regexValue]').length) {
						$rule.find('.rule-filter-container').append('<input type="text" name="regexValue">');
						$rule.find('.rule-operator-container').append('<select class="regexRules"></select>');
						buildRegexSelect($rule.attr('id'));
					}
				},
				onAfterSetValue: function ($rule, value, filter, operator) {
					var parts = value.split('||');
					$rule.find('[name=regexValue]').val(parts[1]);
					$rule.find('.regexRules').val(parts[0]);
				},
				operators: ['equal', 'not_equal'],
				valueParser: function ($rule, value, filter, operator) {
					return $rule.find('.regexRules').val() + '||' + $rule.find('[name=regexValue]').val();

				}
			},

			/*
			 * Upload file type
			 */
			{
				id: 'filetype',
				label: 'File mimetype upload',
				type: 'string',
				placeholder: 'text/plain',
				operators: ['equal', 'not_equal', 'begins_with', 'not_begins_with', 'ends_with', 'not_ends_with']
			},

			/*
			 * Upload file size
			 */
			{
				id: 'sizeup',
				label: 'File size upload',
				type: 'string',
				input: 'text',
				placeholder: '10m',
				operators: ['less', 'less_or_equal', 'greater', 'greater_or_equal'],
				validation: {
					format: /(\d+(?:\.\d+)?)\s?(k|m|g|t)?b?/i
				},
				valueParser: function ($rule, value, filter, operator) {
					var powers = {'k': 1, 'm': 2, 'g': 3, 't': 4};

					var regex = /(\d+(?:\.\d+)?)\s?(k|m|g|t)?b?/i;

					var res = regex.exec(value);

					if (!res[2] && jQuery.isNumeric(res[1])) {
						return parseInt(res[1], 10);
					}

					return parseInt(res[1] * Math.pow(1024, powers[res[2].toLowerCase()]), 10);
				},
				onAfterSetValue: function ($rule, value) {
					var unit = 'b';
					if (value / Math.pow(1024, 4) > 1) {
						value = value / Math.pow(1024, 4);
						unit = 't';
					} else if (value / Math.pow(1024, 3) > 1) {
						value = value / Math.pow(1024, 3);
						unit = 'g';
					} else if (value / Math.pow(1024, 2) > 1) {
						value = value / Math.pow(1024, 2);
						unit = 'm';
					} else if (value / Math.pow(1024, 1) > 1) {
						value = value / Math.pow(1024, 1);
						unit = 'k';
					}

					value = parseFloat(value.toFixed(2));
					$rule.find('input').val(value + unit);
				}
			},

			// CIDR
			OCA.Firewall.getFilterIPv4('cidr', 'Client IP Subnet (IPv4)', '127.0.0.1/24'),
			OCA.Firewall.getFilterIPv6('cidr6', 'Client IP Subnet (IPv6)', '::1/124'),

			/*
			 * Request Type
			 */
			{
				id: 'request',
				label: 'Request Type',
				type: 'string',
				input: function ($rule, filter) {
					// Move text area before operator
					if (!$rule.find('.requestType').length) {
						$rule.find('.rule-operator-container').append('<select class="requestType"></select>');
						OCA.Firewall.getRequestTypeOptions($rule);
					}
				},
				onAfterSetValue: function ($rule, value) {
					$rule.find('.requestType').val(value);
				},
				operators: ['equal', 'not_equal'],
				valueParser: function ($rule) {
					return $rule.find('.requestType').val();
				}
			},

			/*
			 * Request URL
			 */
			{
				id: 'request-url',
				label: 'Request URL',
				type: 'string',
				placeholder: 'http://www.owncloud.org',
				operators: ['equal', 'not_equal', 'begins_with', 'not_begins_with', 'contains', 'not_contains', 'ends_with', 'not_ends_with']
			},

			/*
			 * Time
			 */
			{
				id: 'time',
				label: 'Request Time',
				type: 'time',
				plugin: 'timepicker',
				plugin_config: {
					showTimezone: true,
					timeFormat: 'hh:mm tt z',
					amNames: ['am', 'AM', 'a', 'A'],
					pmNames: ['pm', 'PM', 'p', 'P']
				},
				operators: ['between']
			},

			/*
			 * System Tag
			 */
			{
				id: 'systemTag',
				label: 'System file tag',
				type: 'integer',
				input: function ($rule, filter) {
					// Move text area before operator
					if (!$rule.find('.systemTag').length) {
						var $input = $('<input>').addClass('systemTag').attr('name', 'value').attr('type', 'hidden');
						$rule.find('.rule-operator-container').append($input);

						$rule.find('.systemTag').select2({
							placeholder: t('workflow', 'Select a tag'),
							allowClear: false,
							multiple: false,
							separator: false,
							query: _.bind(OCA.Firewall._queryTagsAutocomplete, this),

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
					}
				},
				onAfterSetValue: function ($rule, value) {
					$rule.find('.systemTag').select2('val', value);
				},
				valueParser: function ($rule) {
					return parseInt($rule.find('.systemTag[name=value]').val(), 10);
				},
				operators: ['equal', 'not_equal']
			},

			/*
			 * User Agent
			 */
			{
				id: 'userAgent',
				label: 'User Agent',
				type: 'string',
				operators: ['equal', 'not_equal']
			},

			/*
			 * User Device Type
			 */
			{
				id: 'deviceType',
				label: 'User Device',
				type: 'string',
				input: function ($rule, filter) {
					// Move text area before operator
					if (!$rule.find('.userDevices').length) {
						$rule.find('.rule-operator-container').append('<select class="userDevices"></select>');
						OCA.Firewall.getClientDeviceOptions($rule);
					}
				},
				onAfterSetValue: function ($rule, value) {
					$rule.find('.userDevices').val(value);
				},
				operators: ['equal', 'not_equal'],
				valueParser: function ($rule) {
					return $rule.find('.userDevices').val();
				}
			},

			/*
			 * User Group
			 * todo: add select 2
			 */
			{
				id: 'userGroup',
				label: 'User Group',
				type: 'string',
				input: function ($rule, filter) {
					// Move text area before operator
					if (!$rule.find('.userGroups').length) {
						$rule.find('.rule-operator-container').append('<select class="userGroups"></select>');
						OCA.Firewall.getUserGroupOptions($rule);
					}
				},
				onAfterSetValue: function ($rule, value) {
					$rule.find('.userGroups').val(value);
				},
				operators: ['equal', 'not_equal'],
				valueParser: function ($rule) {
					return $rule.find('.userGroups').val();
				}
			}
		]
	});

	// Get ui data and bind them to gui
	$.getJSON(OC.generateUrl('/apps/firewall/ajax/getUIData'), function (data) {
		try {
			var rules = $.parseJSON(data.rules);
			var debugLevel = $.parseJSON(data.debugLevel);

			// Save the available rule values for the dropdowns
			OCA.Firewall.ruleValues = data.ruleValues;

			// Add rules to GUI
			OC.SystemTags.collection.fetch({
				success: function() {
					if (!jQuery.isEmptyObject(rules)) {
						$ruleBuilder.queryBuilder('setRules', rules);
					}
				}
			});

			// Set the debug level
			$('#firewallDebug').val(debugLevel);
		} catch (e) {
			OC.Notification.show(e);
		}
		if (data.validationError !== false) {
			OC.msg.finishedError('#firewall .msg', data.validationError);
			OC.Notification.show(data.validationError);
		}
	}).fail(function () {
		OC.Notification.show('Failed to fetch firewall rules.');
	});


	// Have to build regex select box down here after all other filters are declared
	function buildRegexSelect (id) {
		var filters = $ruleBuilder.queryBuilder('getFilters');
		var filterOptions = [];
		var regexFilters = ['filetype', 'cidr', 'cidr6', 'request-url', 'userAgent', 'userGroup'];
		var $list = $(filters);

		$list.each(function (index, value) {
			if (regexFilters.indexOf(value.id) !== -1) {
				filterOptions.push($('<option>', {
					value: value.id,
					text: value.label
				}));
			}
		});

		$ruleBuilder.find('#' + id + ' .regexRules').append(filterOptions);
	}

	/*
	 * Event Handlers
	 */

	$('.saveRules').on('click', function (e) {
		e.preventDefault();
		OC.msg.startSaving('#firewall .msg');

		OCA.Firewall.setValidationError(false);
		var rules = $ruleBuilder.queryBuilder('getRules');
		if (OCA.Firewall.getValidationError()) {
			// Saving 1 empty group is allowed
			if (rules.length === 1 && OCA.Firewall.getValidationError() === 'empty_group') {
			} else {
				OC.msg.finishedError('#firewall .msg', t('firewall', 'At least one of the given rules is invalid.'));
				return;
			}
		}

		$.ajax({
			type: "POST",
			dataType: "json",
			url: OC.generateUrl('/apps/firewall/ajax/save'),
			data: {rules: JSON.stringify(rules)}
		}).done(function () {
			OC.msg.finishedSuccess('#firewall .msg', t('firewall', 'Firewall rules saved.'));
		}).fail(function (xhr) {
			if (xhr.responseJSON.message) {
				OC.msg.finishedError('#firewall .msg', xhr.responseJSON.message);
			} else {
				OC.msg.finishedError('#firewall .msg', t('firewall', 'An error occurred while saving the rules.'));
			}
		});
	});

	$('#firewallDebug').on('change', function (e) {
		OC.msg.startSaving('#firewall .msg');

		$.ajax({
			type: "POST",
			dataType: "json",
			url: OC.generateUrl('/apps/firewall/ajax/debug'),
			data: {
				level: $(this).find('option:selected').val()
			}
		}).done(function () {
			OC.msg.finishedSuccess('#firewall .msg', t('firewall', 'Firewall debug level saved.'));
		}).fail(function (xhr) {
			if (xhr.responseJSON.message) {
				OC.msg.finishedError('#firewall .msg', xhr.responseJSON.message);
			} else {
				OC.msg.finishedError('#firewall .msg', t('firewall', 'An error occurred while saving debug level.'));
			}
		});
	});
});
