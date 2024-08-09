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

OCA.SP.connectivityCheck = {
    //Is connectivity test running
    running: false,
    done: false,
    error: 0,

    mounts: null,
    points: null,

    status:{},

    check: function(force){
        if((!OCA.SP.connectivityCheck.running && OCA.SP.connectivityCheck.done === false) || force === true){
            // It comes to false on rolling queue callback
            OCA.SP.connectivityCheck.running = true;
            //Prepare list of ajax calls
            OCA.SP.connectivityCheck.initQueueList();
        }
    },

    start: function(force){
        force = typeof force !== 'undefined' ? force : false;
        // Fill status array with info, by default all is 0
        if ((OCA.SP.connectivityCheck.running === false && OCA.SP.connectivityCheck.done === false) || force === true){

            OCA.SP.connectivityCheck.mounts = OCA.SP.sharepointUtils.getSharepointMountPoints();
            OCA.SP.connectivityCheck.points = OCA.SP.connectivityCheck.mounts.global.concat(OCA.SP.connectivityCheck.mounts.personal);

            for (var i=0; i< OCA.SP.connectivityCheck.points.length; i++){

                var type ="global";
                if(i>=OCA.SP.connectivityCheck.mounts.global.length) type ="personal";

                OCA.SP.connectivityCheck.status[OCA.SP.connectivityCheck.points[i].mount_point] = {};
                OCA.SP.connectivityCheck.status[OCA.SP.connectivityCheck.points[i].mount_point].status = 0;
                OCA.SP.connectivityCheck.status[OCA.SP.connectivityCheck.points[i].mount_point].type = type;
                OCA.SP.connectivityCheck.status[OCA.SP.connectivityCheck.points[i].mount_point].raw = OCA.SP.connectivityCheck.points[i];
            }
        }
        // Draw mountPoint status in any moment
        OCA.SP.connectivityCheck.paintStatus();
        //Launch checking
        OCA.SP.connectivityCheck.check(force);
        },

    paintStatus: function(){
        $.each(OCA.SP.connectivityCheck.status, function(mp, ctx){
            var sharepointFolder = mp;
            OCA.SP.sharepointUtils.showSharepointIconFolder(mp);
            if (ctx.status === 0){
                OCA.SP.sharepointUtils.disableSharepointFolder(ctx.raw);
            } else if (ctx.status === 1){
                OCA.SP.sharepointUtils.enableSharepointFolder(ctx.raw);
            } else if (ctx.status === 2){
                OCA.SP.sharepointUtils.showSharepointIconError(ctx.raw, 401, "bad_credentials", ctx.type);
            } else if (ctx.status === 3){
                OCA.SP.sharepointUtils.showSharepointIconError(ctx.raw, 6, "down", ctx.type);
            } else {
                OCA.SP.sharepointUtils.showSharepointIconError(ctx.raw, 6, "down", ctx.type);
            }

        });
    },

    initQueueList: function(option){
        option = typeof option !== 'undefined' ? option : false;
        var ajaxQueue = [];
        $.each(OCA.SP.connectivityCheck.status, function(mp, ctx){
            var queueElement = {
                       funcName: $.ajax,
                       funcArgs: [
                                    {type : 'POST', timeout: 10000, url : OC.filePath('sharepoint', 'ajax', 'sharepointConnectivityCheck.php'), data : {m: mp, t: ctx.type, a: ctx.raw.auth_type},
                                        success: OCA.SP.connectivityCheck._successConnect,
                                        error: function(x, timeout, m){
                                            OCA.SP.connectivityCheck._errorConnect(x, timeout, m, mp, ctx.type);
                                        }
                                    }
                                 ]
                                };
            if(option === 'personal' && ctx.type === 'personal' && ctx.raw.auth_type === "2"){
                ajaxQueue.push(queueElement);
            } else if(!option){
                ajaxQueue.push(queueElement);
            }

        });
        var rolQueue = new OCA.SP.RollingQueue(ajaxQueue, 4, function(){
            OCA.SP.connectivityCheck.running = false;
            OCA.SP.connectivityCheck.done = true;
            //Show error if exists
            if(OCA.SP.connectivityCheck.error>0){
                OCA.SP.sharepointUtils.showAlert(t('sharepoint', 'Some of the configured SharePoint mounts are not connected. Please click on the red row(s) for more information'));
                OCA.SP.connectivityCheck.error = 0;
            }
        });
        rolQueue.runQueue();
    },

    individualCheck: function(mp){
        //If the mountPoint is personal and auth_type is 2, we rechech al mountPoints
        if(mp in OCA.SP.connectivityCheck.status){
            if(OCA.SP.connectivityCheck.status[mp].type === 'personal' && OCA.SP.connectivityCheck.status[mp].raw.auth_type === "2"){
                $.each(OCA.SP.connectivityCheck.status, function(mp, ctx){
                    if(ctx.type === 'personal' && ctx.raw.auth_type === "2"){
                        OCA.SP.sharepointUtils.disableSharepointFolder(ctx.raw);
                    }
                });
                OCA.SP.connectivityCheck.initQueueList('personal');
            } else if(OCA.SP.connectivityCheck.status[mp].type === 'personal' && OCA.SP.connectivityCheck.status[mp].raw.auth_type === "1"){
                OCA.SP.sharepointUtils.disableSharepointFolder(OCA.SP.connectivityCheck.status[mp].raw);
                $.ajax({type : 'POST', timeout: 10000, url : OC.filePath('sharepoint', 'ajax', 'sharepointConnectivityCheck.php'), data : {m: mp, t: OCA.SP.connectivityCheck.status[mp].type},
                    success: OCA.SP.connectivityCheck._successConnect,
                    error: function(x, timeout, m){
                        OCA.SP.connectivityCheck._errorConnect(x, timeout, m, mp, ctx.type);
                    },
                    complete: function(){
                        //Show error if exists
                        if(OCA.SP.connectivityCheck.error>0){
                            OCA.SP.sharepointUtils.showAlert(t('sharepoint', 'Some of the configured SharePoint mounts are not connected. Please click on the red row(s) for more information'));
                            OCA.SP.connectivityCheck.error = 0;
                        }
                    }
                });
            } else{
                OCA.SP.connectivityCheck.start(true);
            }
        }
    },

    _successConnect: function(response) {
        if(OCA.SP.sharepointUtils.isCorrectViewAndRootFolder()){
            if(response.code == 200) {
                OCA.SP.connectivityCheck.status[response.m].status = 1;
                OCA.SP.sharepointUtils.enableSharepointFolder(OCA.SP.connectivityCheck.status[response.m].raw);
            } else if (response.code == 401){
                OCA.SP.connectivityCheck.error++;
                OCA.SP.connectivityCheck.status[response.m].status = 2;
                OCA.SP.sharepointUtils.showSharepointIconError(OCA.SP.connectivityCheck.status[response.m].raw, 401, "bad_credentials", response.t);
            } else if (response.code == 412){
                OCA.SP.connectivityCheck.error++;
                OCA.SP.connectivityCheck.status[response.m].status = 2;
                OCA.SP.sharepointUtils.showSharepointIconError(OCA.SP.connectivityCheck.status[response.m].raw, 412, "bad_credentials", response.t);
            } else if (response.code == 6){
                OCA.SP.connectivityCheck.error++;
                OCA.SP.connectivityCheck.status[response.m].status = 3;
                OCA.SP.sharepointUtils.showSharepointIconError(OCA.SP.connectivityCheck.status[response.m].raw, 6, "down", response.t);
            } else {
                OCA.SP.connectivityCheck.error++;
                OCA.SP.connectivityCheck.status[response.m].status = 3;
                OCA.SP.sharepointUtils.showSharepointIconError(OCA.SP.connectivityCheck.status[response.m].raw, 6, "down", response.t);
            }
        }
    },
    _errorConnect: function(x, timeout, m, mp, type) {
        if(timeout==="timeout" && OCA.SP.sharepointUtils.isCorrectViewAndRootFolder()) {
            OCA.SP.connectivityCheck.status[mp].status = 3;
            OCA.SP.sharepointUtils.showSharepointIconError(OCA.SP.connectivityCheck.status[mp].raw, 6, "timeout", type);
            OCA.SP.sharepointUtils.showAlert('The connection with SharePoint is taking longer. This might be a temporary issue, but if it keeps showing, please contact your administrator');
        }
    }
};


(function(){
    $(document).ready(function(){

        if($('#filesApp').val()) {

            $('#app-content-files')
                .add('#app-content-spmounts')
                .on('changeDirectory', function(e){
                    if (e.dir === '/') {
                        var mount_point = e.previousDir.split('/', 2)[1];
                        OCA.SP.connectivityCheck.individualCheck(mount_point);
                }
            }).on('fileActionsReady', function(e){
                if ($.isArray(e.$files)) {
                    OCA.SP.connectivityCheck.start();
                }
            });
        }

    });

})();
