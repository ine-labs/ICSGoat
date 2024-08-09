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
	OCA.Workflow.Condition = {
		Operators: null,

		/*
		 * Array of available Condition options
		 */
		availableConditions: {
			cidr: t('workflow', 'Client IP Subnet (IPv4)'),
			cidr6: t('workflow', 'Client IP Subnet (IPv6)'),
			devicetype: t('workflow', 'Device type'),
			filesize: t('workflow', 'File size'),
			filetype: t('workflow', 'File mimetype'),
			time: t('workflow', 'Request time'),
			request: t('workflow', 'Request type'),
			requesturl: t('workflow', 'Request URL'),
			subnet: t('workflow', 'Server IP Subnet (IPv4)'),
			subnet6: t('workflow', 'Server IP Subnet (IPv6)'),
			systemtag: t('workflow', 'System file tag'),
			useragent: t('workflow', 'User agent'),
			usergroup: t('workflow', 'User group')
		},

		conditions: {
			devicetype: {},
			request: {}
		},

		operators: {
			bool: {
				equals: t('workflow', 'is'),
				not_equals: t('workflow', 'is not')
			},
			ip: {
				equals: t('workflow', 'matches'),
				not_equals: t('workflow', 'does not match')
			},
			size: {
				less: t('workflow', 'is less than'),
				less_or_equal: t('workflow', 'is less than or equal to'),
				greater: t('workflow', 'is greater than'),
				greater_or_equal: t('workflow', 'is greater than or equal to')
			},
			string: {
				equals: t('workflow', 'is'),
				not_equals: t('workflow', 'is not'),
				begins_with: t('workflow', 'begins with'),
				not_begins_with: t('workflow', 'does not begin with'),
				contains: t('workflow', 'contains'),
				not_contains: t('workflow', 'does not contain'),
				ends_with: t('workflow', 'ends with'),
				not_ends_with: t('workflow', 'does not end with')
			}
		},

		setConditionOptions: function (options) {
			this.conditions = options;

			this.conditions.times = [];
			for (var i = 0; i < 24; i++) {
				for (var j = 0; j < 4; j++) {
					var temp = moment().set('hour', i).set('minute', 15 * j),
						utc = temp.clone();
					utc.subtract(utc.utcOffset(), 'minutes');

					this.conditions.times.push({
						id: utc.format('hh:mm a'),
						text: t('workflow', '{time} - <em>{offset}</em>', {
							time: temp.local().format(t('workflow', 'h:mm a')),
							offset: temp.local().fromNow()
						})
					});
				}
			}
		},

		/**
		 * Get a string of the condition
		 * @param condition
		 * @returns {jQuery}
		 */
		translateCondition: function (condition) {
			if (condition.rule === 'cidr') {
				return t('workflow', 'IPv4 {operator} {value}', {
					operator: this.operators.ip[condition.operator],
					value: '<strong>' + escapeHTML(condition.value) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'cidr6') {
				return t('workflow', 'IPv6 {operator} {value}', {
					operator: this.operators.ip[condition.operator],
					value: '<strong>' + escapeHTML(condition.value) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'devicetype') {
				return t('workflow', 'Device type {operator} {value}', {
					operator: this.operators.bool[condition.operator],
					value: '<strong>' + escapeHTML(this.conditions.devicetype[condition.value]) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'filesize') {
				return t('workflow', 'File size {operator} {value}', {
					operator: this.operators.size[condition.operator],
					value: '<strong>' + escapeHTML(OC.Util.humanFileSize(condition.value, false)) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'filetype') {
				return t('workflow', 'File mimetype {operator} {value}', {
					operator: this.operators.string[condition.operator],
					value: '<strong>' + escapeHTML(condition.value) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'request') {
				return t('workflow', 'Request type {operator} {value}', {
					operator: this.operators.bool[condition.operator],
					value: '<strong>' + escapeHTML(this.conditions.request[condition.value]) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'requesturl') {
				return t('workflow', 'Request URL {operator} {value}', {
					operator: this.operators.string[condition.operator],
					value: '<strong>' + escapeHTML(condition.value) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'subnet') {
				return t('workflow', 'Server Subnet IPv4 {operator} {value}', {
					operator: this.operators.ip[condition.operator],
					value: '<strong>' + escapeHTML(condition.value) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'subnet6') {
				return t('workflow', 'Server Subnet IPv6 {operator} {value}', {
					operator: this.operators.ip[condition.operator],
					value: '<strong>' + escapeHTML(condition.value) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'systemtag') {
				var tag = OC.SystemTags.collection.get(condition.value),
					$tag = null;
				if (!_.isUndefined(tag)) {
					$tag = OC.SystemTags.getDescriptiveTag(tag);
					$tag = $('<strong>').append($tag.html());
				} else {
					$tag = OC.SystemTags.getDescriptiveTag(condition.value);
				}

				return t('workflow', 'Parent folder {operator} tagged with {value}', {
					operator: this.operators.bool[condition.operator],
					value: $tag[0].outerHTML
				}, undefined, {escape: false});

			} else if (condition.rule === 'time') {
				return t('workflow', 'Request time {operator} between {value1} and {value2}', {
					operator: this.operators.bool[condition.operator],
					value1: '<strong>' + escapeHTML(this.displayUTCTime(condition.value[0])) + '</strong>',
					value2: '<strong>' + escapeHTML(this.displayUTCTime(condition.value[1])) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'useragent') {
				return t('workflow', 'User agent {operator} {value}', {
					operator: this.operators.string[condition.operator],
					value: '<strong>' + escapeHTML(condition.value) + '</strong>'
				}, undefined, {escape: false});

			} else if (condition.rule === 'usergroup') {
				return t('workflow', 'User {operator} member of {value}', {
					operator: this.operators.bool[condition.operator],
					value: '<strong>' + escapeHTML(condition.value) + '</strong>'
				}, undefined, {escape: false});
			}
			return t('workflow', 'Unknown condition');
		},

		displayUTCTime: function (utcTime) {
			return moment.utc(utcTime, 'hh:mm a').local().format(t('workflow', 'h:mm a'));
		},

		/**
		 * Get the HTML for a condition
		 * @param condition
		 * @returns {jQuery}
		 */
		getCondition: function (condition) {
			if (condition.rule === 'cidr') {
				return this._getIPRule(condition, '127.0.0.1/24');
			} else if (condition.rule === 'cidr6') {
				return this._getIPRule(condition, '::1/124');
			} else if (condition.rule === 'devicetype') {
				return this._getDeviceType(condition);
			} else if (condition.rule === 'filesize') {
				return this._getFileSize(condition);
			} else if (condition.rule === 'filetype') {
				return this._getStringRule(condition);
			} else if (condition.rule === 'request') {
				return this._getRequestType(condition);
			} else if (condition.rule === 'requesturl') {
				return this._getStringRule(condition);
			} else if (condition.rule === 'subnet') {
				return this._getIPRule(condition, '255.255.255.0/24');
			} else if (condition.rule === 'subnet6') {
				return this._getIPRule(condition, '::ffff:ffff:ff00/124');
			} else if (condition.rule === 'systemtag') {
				return this._getSystemTag(condition);
			} else if (condition.rule === 'time') {
				return this._getRequestTime(condition);
			} else if (condition.rule === 'useragent') {
				return this._getStringRule(condition);
			} else if (condition.rule === 'usergroup') {
				return this._getUserGroup(condition);
			}
			OC.Notification.showTemporary(t('workflow', 'Unknown condition'));
		},

		getValueFromCondition: function ($condition) {
			if ($condition.attr('data-condition-type') === 'filesize') {
				var value = $condition.find('[name=value]').val().match(/^(\d+)( )?(t|g|m|k|tb|gb|mb|kb|b)$/i);
				if (value === null) return 0;

				var result = parseInt(value[1], 10);

				switch (value[3].toLowerCase()) {
					case 't':
					case 'tb':
						result = result * 1024;
					//noinspection FallthroughInSwitchStatementJS
					case 'g':
					case 'gb':
						result = result * 1024;
					//noinspection FallthroughInSwitchStatementJS
					case 'm':
					case 'mb':
						result = result * 1024;
					//noinspection FallthroughInSwitchStatementJS
					case 'k':
					case 'kb':
						result = result * 1024;
					//noinspection FallthroughInSwitchStatementJS
					case 'b':
				}

				return parseInt(result, 10);
			} else if ($condition.attr('data-condition-type') === 'time') {
				return [
					$condition.find('[name=value1]').val(),
					$condition.find('[name=value2]').val()
				];
			}

			return $condition.find('[name=value]').val();
		},

		/**
		 * IP checking
		 *
		 * @param condition
		 * @param placeholder
		 * @returns {jQuery}
		 * @private
		 */
		_getIPRule: function (condition, placeholder) {
			var $div = $('<div>').attr('data-condition-type', condition.rule),
				$input = $('<input>').attr('name', 'value').attr('type', 'text').val(condition.value);

			$input.attr('placeholder', placeholder);

			var $operators = this.getOperators(this.operators.ip, condition.operator);

			if (condition.rule === 'cidr') {
				$div.html(t('workflow', 'Client IP Subnet (IPv4) {operator} {value}', {
					operator: $operators[0].outerHTML,
					value: $input[0].outerHTML
				}, undefined, {escape: false}));
			} else if (condition.rule === 'cidr6') {
				$div.html(t('workflow', 'Client IP Subnet (IPv6) {operator} {value}', {
					operator: $operators[0].outerHTML,
					value: $input[0].outerHTML
				}, undefined, {escape: false}));
			} else if (condition.rule === 'subnet') {
				$div.html(t('workflow', 'Server Subnet (IPv4) {operator} {value}', {
					operator: $operators[0].outerHTML,
					value: $input[0].outerHTML
				}, undefined, {escape: false}));
			} else if (condition.rule === 'subnet6') {
				$div.html(t('workflow', 'Server Subnet (IPv6) {operator} {value}', {
					operator: $operators[0].outerHTML,
					value: $input[0].outerHTML
				}, undefined, {escape: false}));
			}

			$div.find('input').val(condition.value);

			return $div;
		},

		/**
		 * Device type
		 *
		 * @param condition
		 * @returns {jQuery}
		 * @private
		 */
		_getDeviceType: function (condition) {
			var $div = $('<div>').attr('data-condition-type', condition.rule),
				$select = $('<select>').attr('name', 'value').val(condition.value);

			var $operators = this.getOperators(this.operators.bool, condition.operator);

			for (var device in this.conditions.devicetype) {
				if (this.conditions.devicetype.hasOwnProperty(device)) {
					var $option = $('<option>');

					$option.attr('value', device);
					$option.attr('selected', device === condition.value);
					$option.text(this.conditions.devicetype[device]);

					$select.append($option);
				}
			}

			$div.html(t('workflow', 'Device type {operator} {value}', {
				operator: $operators[0].outerHTML,
				value: $select[0].outerHTML
			}, undefined, {escape: false}));

			return $div;
		},

		/**
		 * File size
		 *
		 * @param condition
		 * @returns {jQuery}
		 * @private
		 */
		_getFileSize: function (condition) {
			var $div = $('<div>').attr('data-condition-type', condition.rule),
				$input = $('<input>').attr('name', 'value').attr('type', 'text');

			$input.attr('placeholder', '5 MB');

			var $operators = this.getOperators(this.operators.size, condition.operator);

			$div.html(t('workflow', 'File size {operator} {value}', {
				operator: $operators[0].outerHTML,
				value: $input[0].outerHTML
			}, undefined, {escape: false}));

			if (condition.value) {
				$div.find('input').val(OC.Util.humanFileSize(condition.value, false));
			}

			return $div;
		},

		/**
		 * Request time
		 *
		 * @param condition
		 * @returns {jQuery}
		 * @private
		 */
		_getRequestTime: function (condition) {
			var $div = $('<div>').attr('data-condition-type', condition.rule),
				$input1 = $('<input>').attr('name', 'value1').attr('type', 'text'),
				$input2 = $input1.clone().attr('name', 'value2');

			var $operators = this.getOperators(this.operators.bool, condition.operator);

			$div.html(t('workflow', 'Request time {operator} between {value1} and {value2}', {
				operator: $operators[0].outerHTML,
				value1: $input1[0].outerHTML,
				value2: $input2[0].outerHTML
			}, undefined, {escape: false}));

			$div.find('input[name^=value]').select2({
				placeholder: t('workflow', 'Select a time'),
				data: this.conditions.times,
				escapeMarkup: function(m) { return m; }
			});

			if (condition.value) {
				$div.find('input[name=value1]').select2('val', condition.value[0]);
				$div.find('input[name=value2]').select2('val', condition.value[1]);
			}

			return $div;
		},

		/**
		 * Request type
		 *
		 * @param condition
		 * @returns {jQuery}
		 * @private
		 */
		_getRequestType: function (condition) {
			var $div = $('<div>').attr('data-condition-type', condition.rule),
				$select = $('<select>').attr('name', 'value').val(condition.value);

			var $operators = this.getOperators(this.operators.bool, condition.operator);

			for (var type in this.conditions.request) {
				if (this.conditions.request.hasOwnProperty(type)) {
					var $option = $('<option>');

					$option.attr('value', type);
					$option.attr('selected', type === condition.value);
					$option.text(this.conditions.request[type]);

					$select.append($option);
				}
			}

			$div.html(t('workflow', 'Request type {operator} {value}', {
				operator: $operators[0].outerHTML,
				value: $select[0].outerHTML
			}, undefined, {escape: false}));

			return $div;
		},

		/**
		 * String input field
		 *
		 * @param condition
		 * @returns {jQuery}
		 * @private
		 */
		_getStringRule: function (condition) {
			var $div = $('<div>').attr('data-condition-type', condition.rule),
				$input = $('<input>').attr('name', 'value').attr('type', 'text').val(condition.value);

			var $operators = this.getStringOperators(condition.operator);

			if (condition.rule === 'filetype') {
				$div.html(t('workflow', 'File mimetype {operator} {value}', {
					operator: $operators[0].outerHTML,
					value: $input[0].outerHTML
				}, undefined, {escape: false}));
			} else if (condition.rule === 'requesturl') {
				$div.html(t('workflow', 'Request URL {operator} {value}', {
					operator: $operators[0].outerHTML,
					value: $input[0].outerHTML
				}, undefined, {escape: false}));
			} else if (condition.rule === 'useragent') {
				$div.html(t('workflow', 'User agent {operator} {value}', {
					operator: $operators[0].outerHTML,
					value: $input[0].outerHTML
				}, undefined, {escape: false}));
			}

			$div.find('input').val(condition.value);

			return $div;
		},

		_getSystemTag: function (condition) {
			var $div = $('<div>').attr('data-condition-type', condition.rule),
				$input = $('<input>').attr('name', 'value').attr('type', 'hidden').val(condition.value);

			var $operators = this.getOperators(this.operators.bool, condition.operator);

			$div.html(t('workflow', 'One of the parent folders {operator} tagged with {value}', {
				operator: $operators[0].outerHTML,
				value: $input[0].outerHTML
			}, undefined, {escape: false}));

			$div.find('input').select2({
				placeholder: t('workflow', 'Select a tag'),
				allowClear: false,
				multiple: false,
				separator: false,
				query: OCA.Workflow._queryTagsAutocomplete,

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

			return $div;
		},

		/**
		 * User Groups
		 *
		 * @param condition
		 * @returns {jQuery}
		 * @private
		 */
		_getUserGroup: function (condition) {
			var $div = $('<div>').attr('data-condition-type', condition.rule),
				$input = $('<input>').attr('name', 'value').attr('type', 'hidden').val(condition.value);

			var $operators = this.getOperators(this.operators.bool, condition.operator);

			$div.html(t('workflow', 'User {operator} member of {value}', {
				operator: $operators[0].outerHTML,
				value: $input[0].outerHTML
			}, undefined, {escape: false}));

			$div.find('input').select2({
				placeholder: t('workflow', 'Select a group'),
				allowClear: false,
				multiple: false,
				separator: false,
				query: _.debounce(function(query) {
					var queryData = {
						format: 'json',
						limit: 15
					};
					if (query.term !== '') {
						queryData.search = query.term;
					}

					$.ajax({
						url: OC.linkToOCS('cloud/', 2) + 'groups',
						data: queryData,
						success: function(data) {
							var results = [];

							// add groups
							$.each(data.ocs.data.groups, function(i, group) {
								results.push({
									id: group,
									displayname: group
								});
							});

							query.callback({results: results});
						}
					});
				}, 250, true),
				id: function(group) {
					return group.id;
				},
				initSelection: function(element, callback) {
					var selection = {
						id: $(element).val(),
						displayname: $(element).val()
					};
					callback(selection);
				},
				formatResult: function (group) {
					return escapeHTML(group.displayname);
				},
				formatSelection: function (group) {
					return escapeHTML(group.displayname);
				},
				escapeMarkup: function(m) {
					// prevent double markup escape
					return m;
				}
			});

			return $div;
		},

		/**
		 * @param {string} selected Operator that is currently selected
		 * @returns {jQuery}
		 */
		getStringOperators: function (selected) {
			var self = this,
				$select = $('<select>');

			$select.attr('name', 'operator');
			for (var operator in this.operators.string) {
				if (this.operators.string.hasOwnProperty(operator)) {
					$select.append(self._getGenericOperator(operator, this.operators.string[operator], selected));
				}
			}

			return $select;
		},

		/**
		 * @param {object} operators List of operators to be created
		 * @param {string} selected Operator that is currently selected
		 * @returns {jQuery}
		 */
		getOperators: function (operators, selected) {
			var self = this,
				$select = $('<select>');

			$select.attr('name', 'operator');
			for (var operator in operators) {
				if (operators.hasOwnProperty(operator)) {
					$select.append(self._getGenericOperator(operator, operators[operator], selected));
				}
			}

			return $select;
		},

		/**
		 * @param {string} operator Operator that should be created
		 * @param {string} description Description of the operator
		 * @param {string} selected Operator that is currently selected
		 * @returns {jQuery}
		 * @private
		 */
		_getGenericOperator: function (operator, description, selected) {
			var $option = $('<option>');

			$option.attr('value', operator);
			$option.attr('selected', operator === selected);
			$option.text(description);

			return $option;
		}
	};
})();
