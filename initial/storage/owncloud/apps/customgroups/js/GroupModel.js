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
	var NS = '{' + OC.Files.Client.NS_OWNCLOUD + '}';

	/**
	 * @class OCA.CustomGroups.GroupModel
	 * @classdesc
	 *
	 */
	var GroupModel = OC.Backbone.WebdavCollectionNode.extend(
		/** @lends OCA.CustomGroups.GroupModel.prototype */ {

		childrenCollectionClass: OCA.CustomGroups.MembersCollection,

		davProperties: {
			'displayName': NS + 'display-name',
			'role': NS + 'role'
		},

		initialize: function() {
			var self = this;
			// group is part of a GroupsCollection ?
			if (this.collection) {
				// get owner
				var userId = this.collection.getUserId();
				if (userId) {
					// detect removal of list owner
					this.getChildrenCollection().on('remove', function(membershipModel) {
						if (membershipModel.id === userId) {
							// remove current group from the list
							self.collection.remove(self);
						}
					});
				}
			}
		},

		url: function() {
			return OC.linkToRemote('dav') + '/customgroups/groups/' + encodeURIComponent(this.get('id'));
		}
	});

	OCA.CustomGroups = _.extend({}, OCA.CustomGroups);
	OCA.CustomGroups.GroupModel = GroupModel;

})(OC, OCA);

