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

if (!OCA.SP) {
    OCA.SP = {};
}
OCA.SP.sharepointUtils = {

    SharepointMountPoints: {},

    showAlert: function(message){
        if (!OC.Notification.isHidden()) {
            OC.Notification.hide();
            OC.Notification.showHtml(message);
        } else {
            OC.Notification.showHtml(message);
        }
        setTimeout(function() {
            if ($("#notification").text() === message) {
                OC.Notification.hide();
            }
        }, 5000);
    },

    getSharepointMountPoints: function() {
        var mounts = {mounts: Array(), personal: Array(), global: Array()};
        //We need to make sync request
        $.ajax({type: "POST",
                url: OC.filePath('sharepoint', 'ajax', 'common.php'),
                data: {o: 'getMountPoints'},
                success: function(raw) {

                            mounts.global = raw.mountPoint.global;
                            mounts.personal = raw.mountPoint.personal;

                            if(raw.status === 'error'){
                                this.showAlert(t('sharepoint', 'There was an error getting SharePoint mount points'));
                                return;
                            }
                            for(var i=0; i < raw.mountPoint.global.length ; i++){
                                mounts.mounts.push(raw.mountPoint.global[i].mount_point);
                            }
                            for(i=0; i < raw.mountPoint.personal.length ; i++){
                                mounts.mounts.push(raw.mountPoint.personal[i].mount_point);
                            }
                        },
                async: false
        });
        return mounts;
    },

    showSharepointIconFolder: function(sharepointFolder) {
        var imageUrl = "url(" + OC.imagePath('sharepoint', 'folder-sharepoint') + ")";
        this.changeSharepointFolderIcon(sharepointFolder, imageUrl);
    },

    showSharepointIconError: function(sharepointFolder, code, reason, type) {
        var bgColor = '#F2DEDE';
        var imageUrl = "url(" + OC.imagePath('sharepoint', 'folder-sharepoint') + ")";
        if (code === 401 || code === 412) {
            imageUrl = "url(" + OC.imagePath('sharepoint', 'folder-sharepoint-credentials') + ")";
        } else {
            imageUrl = "url(" + OC.imagePath('sharepoint', 'folder-sharepoint-timeout') + ")";
        }
        this.changeSharepointFolderIcon(sharepointFolder.mount_point, imageUrl);
        this.toggleSharepointLink(sharepointFolder, false, reason, type);
        $('#fileList tr[data-file=\"' + this.jqSelEscape(sharepointFolder.mount_point) + '\"]').css('background-color', bgColor);
    },

    disableSharepointFolder: function(sharepointFolder) {
        var bgColor = '#CCC';
        this.toggleSharepointLink(sharepointFolder, false, "checking", "all");
        $('#fileList tr[data-file=\"' + this.jqSelEscape(sharepointFolder.mount_point) + '\"]').css('background-color', bgColor);
    },

    enableSharepointFolder: function(sharepointFolder) {
        var bgColor = '#FFF';
        this.toggleSharepointLink(sharepointFolder, true, "checking", "all");
        $('#fileList tr[data-file=\"' + this.jqSelEscape(sharepointFolder.mount_point) + '\"]').css('background-color', bgColor);
    },

    changeSharepointFolderIcon: function(filename, route) {
        var jname = "#fileList tr[data-file=\"" + this.jqSelEscape(filename) + "\"] > td:first-child div.thumbnail";
        var file = $(jname);
        file.data('oldImage', file.css('background-image'));
        file.css('background-image', route);
    },

    toggleSharepointLink: function(sharepointFolder, active, reason, type) {
        var filename = sharepointFolder.mount_point;
        var link = $("#fileList tr[data-file=\"" + this.jqSelEscape(filename) + "\"] > td:first-child a.name");
        if (active) {
            link.off('click.connectivity');
            OCA.Files.App.fileList.fileActions.display(link.parent(), true, OCA.Files.App.fileList);
            this.showSharepointIconFolder(filename);
        } else {
            link.find('.fileactions, .nametext .action').remove();  // from files/js/fileactions (display)
            link.off('click.connectivity');
            link.on('click.connectivity', function(e){
                if(reason === "bad_credentials"){
                    OCA.SP.sharepointUtils.showCredentialsDialog(sharepointFolder, type);
                }
                else if(reason === "down"){
                    OC.dialogs.message(t('sharepoint', 'It seems that SharePoint server is down or your connection is broken'), t('sharepoint', 'SharePoint connection problem'));
                }
                e.preventDefault();
                return false;
            });
        }
    },

    showCredentialsDialog: function(mountPoint, type){
        if(String(mountPoint.auth_type)=== "4" && type === "global"){
            OC.dialogs.message(t('sharepoint', 'You don\'t have access to this mount point with your ownCloud credentials. Please contact your admin.'), t('sharepoint', 'SharePoint connection problem'));
            return;
        } else if(String(mountPoint.auth_type)!== "1" && type === "global"){
            OC.dialogs.message(t('sharepoint', 'This mount point has credentials provided by the admin, but it seems they are incorrect. Please contact your admin.'), t('sharepoint', 'SharePoint connection problem'));
            return;
        }
        $.ajax({type: 'GET', url: OC.filePath('sharepoint', 'ajax', 'dialog.php'), data:{'name': mountPoint.mount_point,
                                                                                         'target': 'saveCredentials.php',
                                                                                         'm': mountPoint.mount_id,
                                                                                         'a': mountPoint.auth_type,
                                                                                         't': type},
            success: function (data) {
                if (typeof data.status !== 'undefined' && data.status === 'success') {
                    $('body').append(data.form);

                    var sp_send_button_click_func = function () {
                        $('.oc-dialog-close').hide();
                        var dataToSend = {};
                        $('#sp_div_form').find('input').each(function(){
                            var thisElement = $(this);
                            if (thisElement.is('[type="checkbox"]')) {
                            dataToSend[thisElement.attr('id')] = thisElement.prop('checked');
                            } else {
                            dataToSend[thisElement.attr('id')] = thisElement.val();
                            }
                        });
                        $.ajax({type: 'POST',
                            url: OC.filePath('sharepoint', 'ajax', 'saveCredential.php'),
                            data: dataToSend,
                            success: function (data) {
                                var dialog = $('#sp_div_form');
                                if (typeof(data.status) !== 'undefined' && data.status === 'success') {
                                    dialog.ocdialog('close');
                                    //Check if credentials are correct
                                    OCA.SP.connectivityCheck.individualCheck(mountPoint.mount_point);
                                } else {
                                    $('.cond-close .ui-dialog-titlebar-close').show();
                                    $('.oc-dialog-close').show();
                                    dialog.ocdialog('option', 'title', t('sharepoint', 'SharePoint credentials validation failed'));
                                    var title = $('.oc-dialog-title');
                                    var color = title.css('background-color');
                                    title.css('background-color', 'red');
                                    title.animate({backgroundColor: color}, 5000);
                                }
                            },
                            error: function (){
                                $('.oc-dialog-close').show();
                            }});
                    };

                    var buttonList = [{text : t('sharepoint', 'Save'),
                                    click : sp_send_button_click_func,
                                    closeOnEscape : true}];
                    var ocdialogParams = {modal: true, buttons : buttonList,
                                            closeOnExcape : true};
                    $('#sp_div_form').ocdialog(ocdialogParams)
                                    .bind('ocdialogclose', function(){
                                        $('#sp_div_form').ocdialog('destroy').remove();
                                    });                }
            }});
    },

    isCorrectViewAndRootFolder: function() {
        // correct views = files & wndmounts
        if (OCA.Files.App.getActiveView() === 'files' || OCA.Files.App.getActiveView() === 'spmounts') {
            return OCA.Files.App.getCurrentAppContainer().find('#dir').val() === '/';
        }
        return false;
    },

    /* escape a selector expresion for jQuery */
    jqSelEscape: function(expression) {
        return expression.replace(/[!"#$%&'()*+,.\/:;<=>?@\[\\\]^`{|}~]/g, '\\$&');
    }
};

