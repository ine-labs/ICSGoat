/*
 * Copyright (c) 2017 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(OC, OCA) {
	var NS = '{' + OC.Files.Client.NS_OWNCLOUD + '}';

	/**
	 * @class OCA.CustomGroups.GroupModel
	 * @classdesc
	 *
	 */
	var MemberModel = OC.Backbone.WebdavNode.extend(
		/** @lends OCA.CustomGroups.MemberModel.prototype */ {

		defaults: {
			'role': OCA.CustomGroups.ROLE_MEMBER
		},

		url: function() {
			return OC.linkToRemote('dav') + '/customgroups/groups/' +
				encodeURIComponent(this.collection.collectionNode.get('id')) + '/' +
				encodeURIComponent(this.id);
		},

		davProperties: {
			'role': NS + 'role',
			'userDisplayName': NS + 'user-display-name'
		}
	});

	OCA.CustomGroups = _.extend({}, OCA.CustomGroups);
	OCA.CustomGroups.MemberModel = MemberModel;

})(OC, OCA);

