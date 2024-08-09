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
	/**
	 * @class OCA.CustomGroups.GroupsCollection
	 * @classdesc
	 *
	 */
	var MembersCollection = OC.Backbone.WebdavChildrenCollection.extend(
		/** @lends OCA.CustomGroups.MembersCollection.prototype */ {
		model: OCA.CustomGroups.MemberModel,

		url: function() {
			return OC.linkToRemote('dav') + '/customgroups/groups/' +
				encodeURIComponent(this.collectionNode.get('id')) + '/';
		}
	});

	OCA.CustomGroups = _.extend({}, OCA.CustomGroups);
	OCA.CustomGroups.MembersCollection = MembersCollection;

})(OC, OCA);

