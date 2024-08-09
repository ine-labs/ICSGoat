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
	 * @class OCA.CustomGroups.GroupsCollection
	 * @classdesc
	 *
	 */
	var GroupsCollection = OC.Backbone.WebdavChildrenCollection.extend(
		/** @lends OCA.CustomGroups.GroupsCollection.prototype */ {

		model: OCA.CustomGroups.GroupModel,

		_userId: null,

		initialize: function(models, options) {
			options = options || {};

			this._userId = options.userId;
		},

		getUserId: function() {
			return this._userId;
		},

		url: function() {
			if (!this._userId) {
				return OC.linkToRemote('dav') + '/customgroups/groups/';
			} else {
				return OC.linkToRemote('dav') + '/customgroups/users/' + encodeURIComponent(this._userId) + '/';
			}
		}
	});

	OCA.CustomGroups = _.extend({}, OCA.CustomGroups);
	OCA.CustomGroups.GroupsCollection = GroupsCollection;

})(OC, OCA);

