/*
 * Copyright (c) 2016 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(OC, OCA) {

	/**
	 * @class OCA.CustomGroups.MembersView
	 * @classdesc
	 *
	 */
	var MembersView = OC.Backbone.View.extend(
		/** @lends OCA.CustomGroups.MembersView.prototype */ {
		sync: OC.Backbone.davSync,

		events: {
			'click .close': '_onClose',
			'click .action-delete-member': '_onDeleteMember',
			'click .action-change-member-role': '_onChangeMemberRole',
			'click .action-leave-group': '_onClickLeaveGroup',
			'change #custom-group-import-elem': '_onChangeCsvInput',
		},

		initialize: function(model) {
			this.model = model;
			this.collection = model.getChildrenCollection();

			this.collection.on('request', this._onRequest, this);
			this.collection.on('sync destroy error', this._onEndRequest, this);
			this.collection.on('add', this._onAddModel, this);
			this.collection.on('change', this._onChangeModel, this);
			this.collection.on('remove', this._onRemoveMember, this);

			this.model.on('change:displayName', this._renderHeader, this);

			this.membersInput = new OCA.CustomGroups.MembersInputView({
				groupUri: this.model.id
			});

			var self = this;
			this.collection.reset([], {silent: true});
			this.collection.fetch();
			_.defer(function() {
				self._toggleLoading(true);
			});

			_.bindAll(
				this,
				'_onDeleteMember',
				'_onChangeMemberRole',
				'_onClickLeaveGroup',
				'_onChangeCsvInput'
			);
		},

		remove: function() {
			this.collection.off('request', this._onRequest, this);
			this.collection.off('sync', this._onEndRequest, this);
			this.collection.off('add', this._onAddModel, this);
			this.collection.off('change', this._onChangeModel, this);
			this.collection.off('remove', this._onRemoveMember, this);
			this.model.off('change:displayName', this._renderHeader, this);

			this.$el.remove();
		},

		_toggleLoading: function(state) {
			this._loading = state;
			this.$('.loading-list').toggleClass('hidden', !state);
			this.$('.grid').toggleClass('hidden', state);
		},

		_onClose: function(ev) {
			ev.preventDefault();
			this.trigger('close');
		},

		_onRequest: function() {
			this.$('.empty').addClass('hidden');
		},

		_onEndRequest: function() {
			this._toggleLoading(false);
			this.$('.empty').toggleClass('hidden', !!this.collection.length);
		},

		_onChangeCsvInput: function () {
			var file = $('#custom-group-import-elem').prop('files')[0];
			var self = this;

			$.ajax({
				url: OC.getRootPath()
					+ '/remote.php/dav/customgroups/groups/'
					+  encodeURIComponent(this.model.get('id')),
				type: 'POST',
				contentType: 'text/csv',
				processData: false,
				data: file
			}).done(function (result) {
				$('#custom-group-import-elem').val(null);

				var failedUsers = [];

				$.each(result, function(user, res) {
					if (res !== 'success') {
						failedUsers.push(user);
					}
				});

				self.render();
				self.collection.reset([], {silent: true});
				self.collection.fetch();

				if (failedUsers.length) {
					OC.dialogs.info(
						failedUsers.join(', '),
						t('customgroups', 'The following users were not imported')
					);
				} else {
					OC.Notification.showTemporary(t('customgroups', 'All users were imported successfully'));
				}
			}).fail(function() {
				OC.Notification.showTemporary(t('customgroups', 'CSV import failed'));
			});
		},

		_onClickLeaveGroup: function() {
			var currentUserMembership = this.collection.get(OC.getCurrentUser().uid);
			OC.dialogs.confirm(
				t('customgroups', 'Are you sure that you want to leave the group "{name}" ?', {name: this.model.get('displayName')}),
				t('customgroups', 'Confirm leaving group'),
				function confirmCallback(confirmation) {
					if (confirmation) {
						currentUserMembership.destroy({
							wait: true,
							error: function(model, response) {
								if (response.status === 403) {
									OC.Notification.showTemporary(t('customgroups', 'Cannot leave group without an administrator'));
								} else {
									OC.Notification.showTemporary(t('customgroups', 'Could not leave group due to a server error'));
								}
							}
						});
					}
				},
				true
			);
		},

		_onAddModel: function(model, collection, options) {
			var $el = $(this.itemTemplate(this._formatMember(model)));
			if (!_.isUndefined(options.index) && collection.length > 1) {
				this.$container.find('.group-member').eq(options.index).before($el);
			} else {
				this.$container.append($el);
			}
			this._postProcessRow($el, model);
		},

		_onChangeModel: function(model) {
			var $el = $(this.itemTemplate(this._formatMember(model)));
			var index = this.collection.indexOf(model);
			this.$container.children().eq(index).replaceWith($el);
			this._postProcessRow($el, model);
		},

		_postProcessRow: function($el, model) {
			$el.find('[title]').tooltip({
				container: 'body'
			});
			/* jshint camelcase:false */
			if (OC.config.enable_avatars) {
				$el.find('.avatar').avatar(model.id, 32);
			}
		},

		_onRemoveMember: function(model, collection, options) {
			var $memberEl;
			if (_.isNumber(options.index)) {
				$memberEl = this.$('.grid .group-member:nth-child(' + (options.index + 1) + ')');
			} else {
				$memberEl = this.$('.grid .group-member').filterAttr('data-id', model.id);
			}
			$memberEl.remove();
		},

		_onAddMember: function(data) {
			var userId = data.userId;
			var $field = this.$('.member-input-field');

			if (!userId) {
				return false;
			}

			$field.prop('disabled', true);
			var $loading = this.$('.member-input-view .loading');
			$loading.removeClass('hidden');

			this.collection.create({
				id: userId,
				userDisplayName: data.displayName || userId
			},  {
				wait: true,
				success: function() {
					$loading.addClass('hidden');
					$field.prop('disabled', false);
					$field.val('').focus();
				},
				error: function(model, response) {
					$loading.addClass('hidden');
					$field.prop('disabled', false);
					$field.val('').focus();
					if (response.status === 412) {
						OC.Notification.showTemporary(t(
							'customgroups',
							'User "{userId}" does not exist',
							{userId: userId}
						));
					} else if (response.status === 409) {
						OC.Notification.showTemporary(t(
							'customgroups',
							'User "{userId}" is already a member of this group',
							{userId: userId}
						));
					} else {
						OC.Notification.showTemporary(t(
							'customgroups',
							'Could not add user to group'
						));
					}
				}
			});
			return false;
		},

		_onDeleteMember: function(ev) {
			ev.preventDefault();
			var $row = $(ev.target).closest('.group-member');
			var id = $row.attr('data-id');

			// deleting self ?
			if (id === OC.getCurrentUser().uid) {
				return this._onClickLeaveGroup();
			}

			var model = this.collection.findWhere({'id': id});

			if (!model) {
				return;
			}

			// TODO: use displayName once available
			OC.dialogs.confirm(
					t('customgroups', 'Are you sure that you want to remove the member "{name}" ?', {name: model.get('userDisplayName')}),
					t('customgroups', 'Confirm removal of member'),
				function confirmCallback(confirmation) {
					if (confirmation) {
						model.destroy();
					}
				},
				true
			);
		},

		_onChangeMemberRole: function(ev) {
			ev.preventDefault();
			var self = this;
			var $target = $(ev.target);
			var $row = $target.closest('.group-member');
			var id = $row.attr('data-id');
			var model = this.collection.findWhere({'id': id});

			if (!model) {
				return;
			}

			function action(rerender) {
				$target.next('.loading').removeClass('hidden');
				$target.tooltip('hide').remove();
				// swap permissions
				var newRole = (model.get('role') === OCA.CustomGroups.ROLE_ADMIN) ?
					OCA.CustomGroups.ROLE_MEMBER :
					OCA.CustomGroups.ROLE_ADMIN;

				model.save({
					role: newRole
				}, {
					wait: true,
					patch: true,
					success: function() {
						if (rerender) {
							// refresh permission actions
							self.render();
							self.collection.reset([], {silent: true});
							self.collection.fetch();
						}
					},
					error: function(model, response) {
						if (response.status === 403) {
							OC.Notification.showTemporary(t('customgroups', 'Cannot change role without another administrator'));
						} else {
							OC.Notification.showTemporary(t('customgroups', 'Could not change role'));
						}

						if (rerender) {
							self.render();
							self.collection.reset([], {silent: true});
							self.collection.fetch();
						}
					}
				});
			}

			// changing own permissions ?
			if (model.id === OC.getCurrentUser().uid) {
				OC.dialogs.confirm(
						t('customgroups', 'Are you sure that you want to change your own permissions for the group "{name}" ?', {name: this.model.get('displayName')}),
						t('customgroups', 'Confirm role change'),
					function confirmCallback(confirmation) {
						if (confirmation) {
							action(true);
						}
					},
					true
				);
			} else {
				action();
			}
		},

		_formatMember: function(member) {
			return {
				id: member.id,
				displayName: member.get('userDisplayName'),
				changeMemberRoleLabel: 
					(member.get('role') === OCA.CustomGroups.ROLE_ADMIN) ?
					t('customgroups', 'Change role to "member"'):
					t('customgroups', 'Change role to "group owner"'),
				deleteLabel: t('customgroups', 'Remove member'),
				canAdmin: OC.isUserAdmin() || this.model.get('role') === OCA.CustomGroups.ROLE_ADMIN,
				roleDisplayName: (member.get('role') === OCA.CustomGroups.ROLE_ADMIN) ?
					t('customgroups', 'Group owner') :
					t('customgroups', 'Member')
			};
		},

		template: function(data) {
			return OCA.CustomGroups.Templates.membersList(data);
		},

		headerTemplate: function(data) {
			return OCA.CustomGroups.Templates.membersListHeader(data);
		},

		itemTemplate: function(data) {
			return OCA.CustomGroups.Templates.membersListItem(data);
		},

		_renderHeader: function() {
			var data = {
				displayNameLabel: t('customgroups', 'Display name'),
				newMemberPlaceholder: t('customgroups', 'Add member'),
				newMemberSubmitLabel: t('customgroups', 'Add member'),
				leaveGroupLabel: t('customgroups', 'Leave this group'),
				exportCsvLabel: t('customgroups', 'Export as CSV'),
				importCsvLabel: t('customgroups', 'Import as CSV'),
				closeLabel: t('customgroups', 'Close'),
				downloadUrl: this.collection.url() + '?export',

				// super admin might not be member
				// not having a role means not being a member
				userIsMember: !_.isUndefined(this.model.get('role')),
				canAdmin: OC.isUserAdmin() || this.model.get('role') === OCA.CustomGroups.ROLE_ADMIN
			};
			if (this.model) {
				data.groupName = this.model.get('displayName');
			}
			var $header = this.$('.header');
			$header.html(this.headerTemplate(data));
			/* jshint camelcase:false */
			if (OC.config.enable_avatars) {
				$header.find('.avatar').imageplaceholder(
					// combine uri with display name for seed
					this.model.id + ':' + this.model.get('displayName'),
					this.model.get('displayName'),
					32
				);
			}

			this.membersInput.off('select', this._onAddMember, this);
			this.membersInput.render();
			this.membersInput.on('select', this._onAddMember, this);
			this.$('.add-member-container').append(this.membersInput.$el);
		},

		render: function() {
			this.$el.html(this.template({
				memberLabelHeader: t('customgroups', 'Member'),
				roleLabelHeader: t('customgroups', 'Role'),
			}));
			this.$('[title]').tooltip();
			this.$container = this.$('.group-member-list');
			this._renderHeader();
			this.delegateEvents();
		}
	});

	OCA.CustomGroups = _.extend({}, OCA.CustomGroups);
	OCA.CustomGroups.MembersView = MembersView;

})(OC, OCA);

