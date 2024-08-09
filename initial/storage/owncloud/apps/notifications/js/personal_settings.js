/**
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

(function(OC, OCA) {

	if (!OCA.Notifications) {
		OCA.Notifications = {};
	}

	if (!OCA.Notifications.Settings) {
		OCA.Notifications.Settings = {};
	}

	OCA.Notifications.Settings.Model = OC.Backbone.Model.extend({
		url: function() {
			return OC.generateUrl('/apps/notifications/settings/personal/notifications/options');
		},

		parse: function(data) {
			return data.data.options;
		}
	});
})(OC, OCA);

$(document).ready(function(){
	var model = new OCA.Notifications.Settings.Model();

	$('#email_sending_option').change(function(){
		var $element = $(this);
		var changeMap = {};
		changeMap[$element.prop('name')] = $element.val();

		OC.msg.startSaving('#email_notifications .msg');
		model.save(changeMap, {patch: true}).done(function(result){
			OC.msg.finishedSuccess('#email_notifications .msg', result.data.message);
		}).fail(function(result){
			OC.msg.finishedError('#email_notifications .msg', result.responseJSON.data.message);
		});
	}).prop('disabled', true);

	model.fetch().always(function(){
		$('#email_sending_option').prop('disabled', false);
	});
});
