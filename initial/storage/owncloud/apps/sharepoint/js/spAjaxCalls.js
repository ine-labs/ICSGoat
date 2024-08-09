/**
 * ownCloud
 *
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

function SPAjaxCalls() {
    this.preajaxCallback = null;
    this.postajaxCallback = null;
    this.save = function(dataToSend, successCallback) {
        var _self = this;
        if (typeof this.preajaxCallback === 'function') {
            this.preajaxCallback();
        }
        $.post(OC.filePath('sharepoint', 'ajax', 'settingsAdminAJAX.php'),
                dataToSend,
                function(response) {
                    successCallback(response);
                    if (typeof _self.postajaxCallback === 'function') {
                        _self.postajaxCallback();
                    }
                });
    };

    this.savePersonalMount = function(dataToSend, successCallback) {
        var _self = this;
        if (typeof this.preajaxCallback === 'function') {
            this.preajaxCallback();
        }
        $.post(OC.filePath('sharepoint', 'ajax', 'settingsUserAJAX.php'),
                dataToSend,
                function(response) {
                    successCallback(response);
                    if (typeof _self.postajaxCallback === 'function') {
                        _self.postajaxCallback();
                    }
                });
    };

    this.updatePersonalMount = function(id, user, passwd, authType, successCallback) {
        var _self = this;
        if (typeof this.preajaxCallback === 'function') {
            this.preajaxCallback();
        }
        $.post(OC.filePath('sharepoint', 'ajax', 'settingsUserAJAX.php'),
                {o: 'updatePersonalMountPoint',  SPMountId: id, SPGuser: user, SPGpass: passwd, authType: authType},
                function(response) {
                    successCallback(response);
                    if (typeof _self.postajaxCallback === 'function') {
                        _self.postajaxCallback();
                    }
                });
    };

    this.getAllMounts = function(successCallback) {
        var _self = this;
        if (typeof this.preajaxCallback === 'function') {
            this.preajaxCallback();
        }
        $.post(OC.filePath('sharepoint', 'ajax', 'settingsAdminAJAX.php'),
                {o: 'getGlobalMountPoints'},
                function(response) {
                    successCallback(response);
                    if (typeof _self.postajaxCallback === 'function') {
                        _self.postajaxCallback();
                    }
                });
    };

    this.getMountsForUser = function(type, successCallback) {
        var _self = this;
        if (typeof this.preajaxCallback === 'function') {
            this.preajaxCallback();
        }
        $.post(OC.filePath('sharepoint', 'ajax', 'settingsUserAJAX.php'),
                {o: 'getMountPointForUser', type: type},
                function(response) {
                    successCallback(response);
                    if (typeof _self.postajaxCallback === 'function') {
                        _self.postajaxCallback();
                    }
                });
    };

    this.deleteMount = function(id, successCallback) {
        var _self = this;
        if (typeof this.preajaxCallback === 'function') {
            this.preajaxCallback();
        }
        $.post(OC.filePath('sharepoint', 'ajax', 'settingsAdminAJAX.php'),
                {o:'deleteMountPoint', SPMountId: id},
                function(response) {
                    successCallback(response);
                    if (typeof _self.postajaxCallback === 'function') {
                        _self.postajaxCallback();
                    }
                });
    };

    this.deletePersonalMount = function(id, successCallback) {
        var _self = this;
        if (typeof this.preajaxCallback === 'function') {
            this.preajaxCallback();
        }
        $.post(OC.filePath('sharepoint', 'ajax', 'settingsUserAJAX.php'),
                {o: 'deletePersonalMount', SPMountId: id},
                function(response) {
                    successCallback(response);
                    if (typeof _self.postajaxCallback === 'function') {
                        _self.postajaxCallback();
                    }
                });
    };

    this.updateMount = function(id, user, passwd, authType, successCallback) {
        var _self = this;
        if (typeof this.preajaxCallback === 'function') {
            this.preajaxCallback();
        }
        $.post(OC.filePath('sharepoint', 'ajax', 'settingsAdminAJAX.php'),
                {SPMountId: id,
                    user: user,
                    pass: passwd,
                    authType: authType,
                    o: 'updateMountPoint' },
                function(response) {
                    successCallback(response);
                    if (typeof _self.postajaxCallback === 'function') {
                        _self.postajaxCallback();
                    }
                });
    };

    this.saveUserCredentials = function(id, user, passwd, successCallback) {
        var _self = this;
        if (typeof this.preajaxCallback === 'function') {
            this.preajaxCallback();
        }
        $.post(OC.filePath('sharepoint', 'ajax', 'settingsUserAJAX.php'),
                {o: 'saveUserCredentials', SPMountId: id, SPuser: user, SPpass: passwd},
                function(response) {
                    successCallback(response);
                    if (typeof _self.postajaxCallback === 'function') {
                        _self.postajaxCallback();
                    }
                });
    };
}
