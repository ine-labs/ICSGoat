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

(function(){

function TableCodeGeneratorSP () {
    this.generateTableRow = function(mount) {
        var raw = '<tr data-mid="' + escapeHTML(mount.mount_id).replace(/"/g, "&quot;")  + '">';
        raw += '<td>' + escapeHTML(mount.mount_point) + '</td>';
        raw += '<td>' + this.generateSelect(mount.applicables) + '</td>';
        raw += '<td>' + escapeHTML(mount.url) + '</td>';
        raw += '<td>' + escapeHTML(mount.list_name) + '</td>';
        raw += '<td class="td_sp_creds">';
        raw += '<select class="sp-select sp_top" style="width:20em;" disabled="disabled">';
        if(String(mount.auth_type) === "1"){
            raw += '<option value="1" selected="selected">'+escapeHTML(t('sharepoint', 'User credentials'))+'</option>';
        } else {
            raw += '<option value="1">'+escapeHTML(t('sharepoint', 'User credentials'))+'</option>';
        }
        if(String(mount.auth_type) === "2"){
            raw += '<option value="2" selected="selected">'+escapeHTML(t('sharepoint', 'Global credentials'))+'</option>';
        } else {
            raw += '<option value="2">'+escapeHTML(t('sharepoint', 'Global credentials'))+'</option>';
        }
        if(String(mount.auth_type) === "3"){
            raw += '<option value="3" selected="selected">'+escapeHTML(t('sharepoint', 'Custom credentials'))+'</option>';
        } else {
            raw += '<option value="3">'+escapeHTML(t('sharepoint', 'Custom credentials'))+'</option>';
        }
        if(String(mount.auth_type) === "4"){
            raw += '<option value="4" selected="selected">'+escapeHTML(t('sharepoint', 'Login credentials'))+'</option>';
        } else {
            raw += '<option value="4">'+escapeHTML(t('sharepoint', 'Login credentials'))+'</option>';
        }
        raw += '</select>';
        raw += '</select>';
        if(String(mount.auth_type) === "3"){
            raw += '<input type="text" name="guser" placeholder="' + escapeHTML(mount.user) + '" disabled="disabled" style="margin-left: 3px;"/>';
            raw += '<input type="password" name="gpass" placeholder="' + escapeHTML(t('sharepoint', 'Password')).replace(/"/g, "&quot;") + '" disabled="disabled" style="margin-left: 3px;"/>';
        }
        if(String(mount.auth_type) === "4"){
            raw += '<input type="text" name="SPdomain" placeholder="' + escapeHTML(mount.user) + '" disabled="disabled" style="margin-left: 3px;"/>';
        }
        raw += '</td><td>';
        raw += '<input type="button" name="edit" value="' + escapeHTML(t('sharepoint', 'Edit')).replace(/"/g, "&quot;")  + '" />';
        raw += '<input type="button" name="delete" value="' + escapeHTML(t('sharepoint', 'Delete')).replace(/"/g, "&quot;")  + '" /></td>';
        raw += '</tr>';
        return raw;
    };

    this.generateSelect = function(applicables) {
        var applicableList = $.map(applicables, function(obj){
            if (obj === 'global'){
                return 'All users';
            } else {
                return obj.replace(/\(users\)$/, '').trim();
            }
        });

        var input = '<input type="hidden" disabled="disabled" class="SPapplicableUsers" style="width:20em;" value="' + applicableList.join() + '"/>';
        return input;
    };

    this.editRow = function(trrow) {

        var td_creds = trrow.find('.td_sp_creds');
        td_creds.find('select').select2("enable", true);
        td_creds.find('div').hide();
        td_creds.find('select').select2({minimumResultsForSearch: -1, width: 'resolve' });
        td_creds.find('div').show();

        if(td_creds.find('select').val() === "3" || td_creds.find('select').val() === "4"){
            td_creds.find('input').prop( "disabled", false );
        }

        td_creds.find('select').change(function(){
            var thisElement = $(this);
            td_creds.find('input').remove();
            if(thisElement.val() === "3"){
                var usernameInput = '<input type="text" name="guser" placeholder="' + escapeHTML(t('sharepoint', 'Username')) + '" style="margin-left: 3px;"/>';
                var passwordInput = '<input type="password" name="gpass" placeholder="' + escapeHTML(t('sharepoint', 'Password')).replace(/"/g, "&quot;") + '" style="margin-left: 3px;"/>';
                td_creds.append(usernameInput + passwordInput);
            }
            if(thisElement.val() === "4"){
                var domainInput = '<input type="text" name="SPdomain" placeholder="' + t('sharepoint', 'Domain') + '" disabled="disabled" style="margin-left: 3px;"/>';
                td_creds.append(domainInput);
            }

        });
    };
}

function TableManagerSP(tbody) {
    this.tbody = tbody;
    this.spAjaxCalls = new SPAjaxCalls();
    this.codeGenerator = new TableCodeGeneratorSP();

    this.attachDeleteHandlers = function() {
        var sp = this.spAjaxCalls;
        this.tbody.find('input[type="button"][name="delete"]').click(function(){
            var trrow = $(this).parent().parent();
            sp.deleteMount(trrow.data('mid'), function(response){
                if (response.status === 'success') {
                    trrow.remove();
                }
            });
        });
    };

    this.attachEditHandlers = function() {
        var codeGen = this.codeGenerator;
        var sp = this.spAjaxCalls;
        var currentTableManager = this;
        this.tbody.find('input[type="button"][name="edit"]').on('click.edit', function(){
            var trrow = $(this).parent().parent();
            codeGen.editRow(trrow);
            $(this).off('click.edit')
                    .val(t('sharepoint', 'Update'))
                    .on('click.update', function(){
                            var user = trrow.find('input[name="guser"]').val();
                            var pwd = trrow.find('input[name="gpass"]').val();
                            var authType = trrow.find('.td_sp_creds').find('select').val();
                            if (authType == 4){
                                user = trrow.find('input[name="SPdomain"]').val();
                            }
                            sp.updateMount(trrow.data('mid'), user, pwd, authType, function(response){
                                    if (response.status === "success") {
                                        currentTableManager.reloadTable();
                                    } else {
                                        OC.dialogs.alert(response.data.message,
                                        escapeHTML(t('sharepoint', 'Error checking the SharePoint configuration')));
                                    }
                            });
                     });
        });
    };

    this.reloadTable = function() {
        var codeGen = this.codeGenerator;
        var targetBody = this.tbody;
        var currentTableManager = this;
        this.spAjaxCalls.getAllMounts(function(response){
            if (response.status === 'success') {
                var allRows = '';
                $.each(response.data, function(index, mount){
                    allRows += codeGen.generateTableRow(mount);
                });
                targetBody.empty().append(allRows);

                currentTableManager.SPaddSelect2(targetBody.find('.SPapplicableUsers'));
                currentTableManager.attachDeleteHandlers();
                currentTableManager.attachEditHandlers();
                targetBody.find("select.sp-select").select2({minimumResultsForSearch: -1, width: 'resolve' });
            }
        });
    };

    this.extraClearInputs = function(baseElement) {
        baseElement.find("input, select").each(function(){
            var thisElement = $(this);
            if (thisElement.is("select")) {
                thisElement.find('option').prop("selected", false)
                    .filter('[value="global"],[selected]')
                    .prop("selected", true);
                thisElement.select2({minimumResultsForSearch: -1, width: 'resolve' });
            } else if (thisElement.attr("type") === "text") {
                thisElement.val('').change();
            } else if (thisElement.attr("type") === "checkbox") {
                thisElement.prop("checked", false).change();
            }
        });
        $('#SPdomain').hide();
        $('#SPMountType').select2("val", "");
        baseElement.find("select:eq(0)").find('option').remove().end().append('<option value="0">No Document Library</option>');
        baseElement.find("select:eq(0)").select2("enable", false);
        baseElement.find("select:eq(0)").select2({minimumResultsForSearch: -1, width: 'resolve' });
    };

    this.saveData = function(baseElement) {
        var allData = {};

        baseElement.find("input, select").each(function(){
            var thisElement = $(this);
            if (thisElement.is("select") && thisElement.attr("multiple")) {
                var values = thisElement.val() || [];
                allData[thisElement.attr("name")] = values.join(",");
            } else if (thisElement.is("select")) {
                allData[thisElement.attr("name")] = thisElement.val();
            } else if (thisElement.attr("type") === "text" ||
                       thisElement.attr("type") === "password" ||
                       thisElement.attr("type") === "hidden") {
                allData[thisElement.attr("name")] = thisElement.val();
            } else if (thisElement.attr("type") === "checkbox") {
                allData[thisElement.attr("name")] = thisElement.prop("checked");
            }
        });

        allData.o = "addMountPoint";

        var currentTableManager = this;
        this.spAjaxCalls.save(allData, function(response){
            if (response.status === "success") {
                currentTableManager.extraClearInputs(baseElement);
                currentTableManager.reloadTable();
            } else {
                OC.dialogs.alert(response.data.message,
                        escapeHTML(t('sharepoint', 'Error checking the SharePoint configuration')));
            }
        });
    };

   this.SPaddSelect2 = function($elements) {
        var userListLimit = 30;
        if ($elements.length > 0) {
            $elements.select2({
                placeholder: t('sharepoint', 'All users. Type to select user or group.'),
                allowClear: true,
                multiple: true,
                //minimumInputLength: 1,
                ajax: {
                    url: OC.filePath('sharepoint', 'ajax', 'applicable.php'),
                    dataType: 'json',
                    quietMillis: 100,
                    data: function (term, page) { // page is the one-based page number tracked by Select2
                        return {
                            pattern: term, //search term
                            limit: userListLimit, // page size
                            offset: userListLimit*(page-1) // page number starts with 0
                        };
                    },
                    results: function (data, page) {
                        if (data.status === "success") {
                            var results = [];
                            var userCount = 0; // users is an object
                            // add groups
                            $.each(data.groups, function(i, group) {
                                results.push({name:group+'(group)', displayname:group, type:'group' });
                            });
                            // add users
                            $.each(data.users, function(id, user) {
                                userCount++;
                                results.push({name:id, displayname:user, type:'user' });
                            });

                            //Disable by now
                            //results.push({name:'global', displayname:'All users', type:'global' });

                            var more = (userCount >= userListLimit) || (data.groups.length >= userListLimit);
                            return {results: results, more: more};
                        } else {
                            //FIXME add error handling
                        }
                    }
                },
                initSelection: function(element, callback) {
                    var promises = [];
                    var results = [];

                    $(element.val().split(",")).each(function (i,userId) {
                        var def = new $.Deferred();
                        promises.push(def.promise());

                        var pos = userId.indexOf('(group)');
                        if (pos !== -1) {
                            //add as group
                            results.push({name:userId, displayname:userId.substr(0, pos), type:'group'});
                            def.resolve();
                        } else {
                            results.push({name:userId, displayname:userId, type:'user'});
                            def.resolve();
                        }
                    });
                    $.when.apply(undefined, promises).then(function(){
                        callback(results);
                    });
                },
                id: function(element) {
                    return element.name;
                },
                formatResult: function (element) {
                    var $result = $('<span><div class="avatardiv"/><span>'+escapeHTML(element.displayname)+'</span></span>');
                    var $div = $result.find('.avatardiv')
                                        .attr('data-type', element.type)
                                        .attr('data-name', element.name)
                                        .attr('data-displayname', element.displayname);
                    if (element.type === 'group') {
                        var url = OC.imagePath('core','places/contacts-dark'); // TODO better group icon
                        $div.html('<img width="32" height="32" src="'+url+'">');
                    }
                    return $result.get(0).outerHTML;
                },
                formatSelection: function (element) {
                    if (element.type === 'group') {
                        return '<span title="'+escapeHTML(element.name)+'" class="group">'+escapeHTML(element.displayname+' '+t('sharepoint', '(group)'))+'</span>';
                    } else {
                        return '<span title="'+escapeHTML(element.name)+'" class="user">'+escapeHTML(element.displayname)+'</span>';
                    }
                },
                escapeMarkup: function (m) {
                    // we escape the markup in formatResult and formatSelection
                    return m;
                },
                dropdownCssClass: 'SP-Select2-Dropdown'
            }).on("select2-loaded", function() {
                $.each($(".avatardiv"), function(i, div) {
                    $div = $(div);
                    if ($div.data('type') === 'user') {
                        $div.avatar($div.data('name'),32);
                    }
                });
            });
        }
    };
}


$(document).ready(function(){

    $('select.sp-select').select2({minimumResultsForSearch: -1, width: 'resolve' });

    $('#SPSaveCredentialsButton').click(function(){
        $.post( OC.filePath('sharepoint', 'ajax', 'settingsAdminAJAX.php'),
                {o: 'setAdminGlobalCredentials', u: $('#SPGlobalUsername').val(), p: $('#SPGlobalPassword').val(), c: true},
                function(raw) {
                    if(raw.status === "error"){
                        showAlert(t('sharepoint', 'There was an error saving your credentials'));
                        return;
                    }
                    showAlert(t('sharepoint', 'Credentials stored'));
                });
    });


    var showAlert = function(message){
        var row = OC.Notification.showHtml(message);
        setTimeout(function() {
            OC.Notification.hide(row);
        }, 3000);
    };

    var tableManagerSP = new TableManagerSP($('#SPadminMountPoints table tbody'));

    $('#SPSaveButton').click(function() {
        var pattern = /^((http|https):\/\/)/;
        if(!$('#SPMountPoint').val()) {
            OC.dialogs.alert(t("sharepoint", "Please enter a local folder name"), t("sharepoint", "SharePoint"));
            return;
        } else if(RegExp(/[\\\/<>:"|?*]/).test($('#SPMountPoint').val())) {
            OC.dialogs.alert(t("sharepoint", "Local Folder Name is not valid. Characters \\, \/, <, >, :, \", |, ? and * are not allowed."), t("sharepoint", "SharePoint"));
            return;
        } else if(!$('#SPUrl').val()) {
            OC.dialogs.alert(t("sharepoint", "Please enter a SharePoint site URL"), t("sharepoint", "SharePoint"));
            return;
        } else if(!pattern.test($('#SPUrl').val())){
            OC.dialogs.alert(t("sharepoint", "Enter correct URL, starting with http:// or https://"), t("sharepoint", "SharePoint"));
            return;
        } else if($('#selectList').val() === "0" || $('#selectList').val() === "") {
            OC.dialogs.alert(t("sharepoint", "Please select a SharePoint Document Library"), t("sharepoint", "SharePoint"));
            return;
        }
        tableManagerSP.saveData($(this).parent().parent());
        // base element should be the tfoot's tr
    });

    $('#authType').change(function(){
        var thisElement = $(this);
        if($('#authType').val() === "4"){
            $('#SPdomain').show();
        } else {
            $('#SPdomain').hide();
        }
        if($('#authType').val() !== "3"){
            $('#SPGuser').hide();
            $('#SPGpass').hide();
        }
        else{
            $('#SPGuser').show();
            $('#SPGpass').show();
        }
    });


    var SPactivationData = {'SPActivatePersonal': {key: 'setPersonalMounts',
                                                    message: t('sharepoint', 'Are you sure you want to disable personal mounts? This will remove existing personal mount points for all users.')},
                        'SPActivateSharing': {key: 'setGlobalSharing',
                                                message: t('sharepoint', 'Are you sure you want to disable shared mount points? This will remove all existing shared mount points for all users.')}};



    $('#SPActivatePersonal, #SPActivateSharing').change(function(){
        var value = this.checked;
        var thisElement = $(this);
        var v = ($(this).prop('checked'))? 1: 0;
        var sendRequest = function(value) {
            $.post(OC.filePath('sharepoint', 'ajax', 'settingsAdminAJAX.php'),
                    {o: SPactivationData[thisElement.attr('id')].key, value: v},
                    function(response) {
                        if (typeof response.status !== "undefined" && response.status !== "success") {
                            OC.dialogs.alert(response.data.message,
                                    escapeHTML(t('sharepoint', 'Error')));
                        }
                    });
            };

        if (!this.checked) {
            thisElement.prop('checked', true);
            OC.dialogs.info(SPactivationData[thisElement.attr('id')].message, t('sharepoint', 'Please confirm'), function(){
                sendRequest(value);
                thisElement.prop('checked', false);
            }, true);
        } else {
            sendRequest(value);
        }
    });

    $('#refreshList').on('click', function() {

            var user="anon", password="anon";
            var pattern = /^((http|https):\/\/)/;

            if(!$('#SPMountPoint').val()) {
                OC.dialogs.alert(t("sharepoint", "Please enter a local folder name"), t("sharepoint", "SharePoint"));
            }
            else if(!pattern.test($('#SPUrl').val())){
                OC.dialogs.alert(t("sharepoint", "Enter correct URL, starting with http:// or https://"), t("sharepoint", "SharePoint"));
                return;
            }
            else{
                //For listing purposes we always use listing credentials
                user = $('#SPlistingUsername').val();
                password = $('#SPlistingPassword').val();
                //Disable select
                $('#selectList').select2('enable', false);
                $('#selectList').select2({minimumResultsForSearch: -1, width: 'resolve' });

                $('#refreshListAdminLoader').show();
                $('#s2id_selectList').hide();
                $.ajax({ type: "POST",
                         url: OC.filePath('sharepoint', 'ajax', 'settingsAdminAJAX.php'),
                         data: {o: 'getDocumentList', u: user, p: password, url: $('#SPUrl').val()},
                         dataType: "json",
                         timeout: 10000, // in milliseconds
                         success: function(raw) {
                                if(raw.status === "error"){
                                    OC.dialogs.alert(t("sharepoint", "An error occurred. Check credentials or server address."), t("sharepoint", "SharePoint"));
                                    return;
                                }
                                $('#selectList').empty().append('<option selected="selected" value=""></option>');
                                for (var i = 0; i < raw.message.length; ++i) {
                                    $('#selectList').append(new Option(raw.message[i].title, raw.message[i].title));
                                }
                                $('#selectList').select2('enable', true);
                                $('#selectList').select2({minimumResultsForSearch: -1, width: 'resolve' });
                            },
                         error: function(request, status, err) {
                                    if (request.status === 302){
                                        OC.dialogs.alert(t("sharepoint", "An error occurred. Redirection detected: "+request.status+" "+request.statusText), t("sharepoint", "SharePoint"));
                                    } else if(status == "timeout") {
                                        OC.dialogs.alert(t("sharepoint", "An error occurred. Check credentials, server address, slow connection."), t("sharepoint", "SharePoint"));
                                    } else {
                                        OC.dialogs.alert(t("sharepoint", "An error occurred."), t("sharepoint", "SharePoint"));
                                    }
                                },
                        complete: function(request){
                                    $('#refreshListAdminLoader').hide();
                                    $('#s2id_selectList').show();
                            }
                        });
            }
        });

    tableManagerSP.SPaddSelect2($('#SPadminMountPoints table .SPapplicableUsers'));
    tableManagerSP.attachDeleteHandlers();
    tableManagerSP.attachEditHandlers();

    tableManagerSP.spAjaxCalls.preajaxCallback = function(){
        if (typeof this.showTimes === "undefined") {
            this.showTimes = 0;
        }
        if (this.showTimes > 0) {
            this.showTimes++;
            return;
        }

        var spBaseDiv = $('#SPadminMountPoints');
        if (spBaseDiv.children('img.loading').length === 0) {
            spBaseDiv.append('<img class="loading" style="display:none ; position:absolute" src="' + OC.imagePath('core', 'loading.gif') + '" />');
        }
        var loadingGif = $('#SPadminMountPoints > img.loading');
        loadingGif.show().position({my: 'center center',
                            at: 'center center',
                            of: '#SPadminMountPoints table.grid'});
        this.showTimes++;
    };
    tableManagerSP.spAjaxCalls.postajaxCallback = function(){
        if (this.showTimes > 1) {
            this.showTimes--;
            return;
        }
        $('#SPadminMountPoints > img.loading').hide();
        this.showTimes--;
    };
});

})();
