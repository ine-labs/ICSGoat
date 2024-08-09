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

    function PersonalTableCodeGeneratorSP () {
        this.generateTableRow = function(mount) {
            var raw = '<tr data-mid="' + escapeHTML(mount.mount_id).replace(/"/g, "&quot;") + '">';
            raw += '<td>' + escapeHTML(mount.mount_point) + '</td>';
            raw += '<td>' + escapeHTML(mount.url) + '</td>';
            raw += '<td>' + escapeHTML(mount.list_name) + '</td>';
            raw += '<td class="td_sp_creds">';
            if (parseInt(mount.auth_type, 10) === 1) {
                if (mount.crendential_username === null || mount.credential_password === null) {
                    raw += '<input type="text" id="SPuser' + escapeHTML(mount.mount_id).replace(/"/g, "&quot;") + '" name="SPuser" placeholder="' + escapeHTML(t('sharepoint', 'Username')).replace(/"/g, "&quot;") + '" />';
                    raw += '<input type="password" id="SPpass' + escapeHTML(mount.mount_id).replace(/"/g, "&quot;") + '" name="SPpass" placeholder="' + escapeHTML(t('sharepoint', 'Password')).replace(/"/g, "&quot;") + '" />';
                } else {
                    raw += '<span>' + escapeHTML(t('sharepoint', 'Username')) + ':</span> <span name="username">' + escapeHTML(mount.credential_username) + '</span>';
                }
            } else {
                raw += escapeHTML(t('sharepoint', 'Credentials provided by the admin'));
            }
            raw += '</td>';

            raw += '<td>';
            if (parseInt(mount.auth_type, 10) === 1) {
                if (mount.crendential_username === null || mount.credential_password === null) {
                    raw += '<input type="button" class="SaveButton" value="' + escapeHTML(t('sharepoint', 'Save')).replace(/"/g, "&quot;") + '" />';
                } else {
                    raw += '<input type="button" class="EditButton" value="' + escapeHTML(t('sharepoint', 'Edit')).replace(/"/g, "&quot;") + '" />';
                }
            }
            raw += '</td>';
            return raw;
        };

        this.editRow = function(trrow) {
            var currentUsername = trrow.find('.td_sp_creds span[name="username"]').text();
            var usernameInput = '<input type="text" name="guser" value="' + escapeHTML(currentUsername).replace(/"/g, "&quot;") + '" />';
            var passwordInput = '<input type="password" name="gpass" placeholder="' + escapeHTML(t('sharepoint', 'Password')).replace(/"/g, "&quot;") + '"/>';
            trrow.find('.td_sp_creds').empty()
                .append(usernameInput + passwordInput)
                .find('input[type="button"]')
                .click(function(){
                    trrow.find('.td_sp_creds input[name="guser"]')
                        .val($('#SPGlobalUsername').val());
                    trrow.find('.td_sp_creds input[name="gpass"]')
                        .val($('#SPGlobalPassword').val());
                });
        };
    }

    function PersonalTableManagerSP(tbody) {
        this.tbody = tbody;
        this.spAjaxCalls = new SPAjaxCalls();
        this.codeGenerator = new PersonalTableCodeGeneratorSP();

        this.attachEditHandlers = function() {
            var codeGen = this.codeGenerator;
            var sp = this.spAjaxCalls;
            var currentTableManager = this;
            this.tbody.find('input.EditButton[type="button"]').on('click.edit', function(){
                var trrow = $(this).parent().parent();
                codeGen.editRow(trrow);
                $(this).off('click.edit')
                    .val(t('sharepoint', 'Update'))
                    .on('click.update', function(){
                        var user = trrow.find('input[name="guser"]').val();
                        var pwd = trrow.find('input[name="gpass"]').val();
                        sp.saveUserCredentials(trrow.data('mid'), user, pwd, function(response){
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

        this.attachSaveHandlers = function() {
            var sp = this.spAjaxCalls;
            var currentTableManager = this;
            this.tbody.find('input.SaveButton[type="button"]').on('click.save', function(){
                currentTableManager.saveData($(this).parent().parent());
            });
        };

        this.reloadTable = function() {
            var codeGen = this.codeGenerator;
            var targetBody = this.tbody;
            var currentTableManager = this;
            this.spAjaxCalls.getMountsForUser('admin', function(response){
                if (response.status === 'success') {
                    var allRows = '';
                    $.each(response.data, function(index, mount){
                        allRows += codeGen.generateTableRow(mount);
                    });
                    targetBody.empty().append(allRows);

                    currentTableManager.attachEditHandlers();
                    currentTableManager.attachSaveHandlers();
                }
            });
        };

        this.saveData = function(baseElement) {
            var allData = {};

            baseElement.find("input, select").each(function(){
                var thisElement = $(this);
                if (thisElement.is("select")) {
                    var values = thisElement.val() || [];
                    allData[thisElement.attr("name")] = values.join(",");
                } else if (thisElement.attr("type") === "text" ||
                    thisElement.attr("type") === "password" ||
                    thisElement.attr("type") === "hidden") {
                    allData[thisElement.attr("name")] = thisElement.val();
                } else if (thisElement.attr("type") === "checkbox") {
                    allData[thisElement.attr("name")] = thisElement.prop("checked");
                }
            });

            var currentTableManager = this;
            this.spAjaxCalls.saveUserCredentials(baseElement.data('mid'), allData.SPuser, allData.SPpass, function(response){
                if (response.status === "success") {
                    currentTableManager.reloadTable();
                } else {
                    OC.dialogs.alert(response.data.message,
                        escapeHTML(t('sharepoint', 'Error checking the SharePoint configuration')));
                }
            });
        };
    }

    function PersonalMountTableCodeGeneratorSP () {
        this.generateTableRow = function(mount) {
            var raw = '<tr data-mid="' + escapeHTML(mount.mount_id).replace(/"/g, "&quot;")  + '">';
            raw += '<td>' + escapeHTML(mount.mount_point) + '</td>';
            raw += '<td>' + escapeHTML(mount.url) + '</td>';
            raw += '<td>' + escapeHTML(mount.list_name) + '</td>';
            raw += '<td class="td_sp_creds">';
            raw += '<select class="sp-select sp_top" style="width:20em;" disabled="disabled">';
            if(mount.auth_type === "1"){
                raw += '<option value="1" selected="selected">'+escapeHTML(t('sharepoint', 'Custom credentials'))+'</option>';
            } else {
                raw += '<option value="1">'+escapeHTML(t('sharepoint', 'Custom credentials'))+'</option>';
            }
            if(mount.auth_type === "2"){
                raw += '<option value="2" selected="selected">'+escapeHTML(t('sharepoint', 'Personal credentials'))+'</option>';
            } else {
                raw += '<option value="2">'+escapeHTML(t('sharepoint', 'Personal credentials'))+'</option>';
            }
            raw += '</select>';
            if(mount.auth_type === "1"){
                raw += '<input type="text" name="guser" placeholder="' + escapeHTML(mount.user) + '" disabled="disabled" style="margin-left: 3px;"/>';
                raw += '<input type="password" name="gpass" placeholder="' + escapeHTML(t('sharepoint', 'Password')).replace(/"/g, "&quot;") + '" disabled="disabled" style="margin-left: 3px;"/>';
            }
            raw += '</td><td>';
            raw += '<input type="button" name="edit" value="' + escapeHTML(t('sharepoint', 'Edit')).replace(/"/g, "&quot;")  + '" />';
            raw += '<input type="button" name="delete" value="' + escapeHTML(t('sharepoint', 'Delete')).replace(/"/g, "&quot;")  + '" /></td>';
            raw += '</tr>';
            return raw;
        };

        this.editRow = function(trrow) {
            var tdCreds = trrow.find('.td_sp_creds');
            tdCreds.find('select').select2("enable", true);
            tdCreds.find('div').hide();
            tdCreds.find('select').select2({minimumResultsForSearch: -1, width: 'resolve' });
            tdCreds.find('div').show();

            if(tdCreds.find('select').val() === "1"){
                tdCreds.find('input').prop( "disabled", false );
            }

            tdCreds.find('select').change(function(){
                var thisElement = $(this);
                tdCreds.find('input').remove();
                if(tdCreds.find('select').val() === "1"){
                    var usernameInput = '<input type="text" name="guser" placeholder="' + escapeHTML(t('sharepoint', 'Username')) + '" />';
                    var passwordInput = '<input type="password" name="gpass" placeholder="' + escapeHTML(t('sharepoint', 'Password')).replace(/"/g, "&quot;") + '"/>';
                    tdCreds.append(usernameInput + passwordInput);
                }
            });
        };
    }

    function PersonalMountTableManagerSP(tbody) {
        this.tbody = tbody;
        this.spAjaxCalls = new SPAjaxCalls();
        this.codeGenerator = new PersonalMountTableCodeGeneratorSP();

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
                        sp.updatePersonalMount(trrow.data('mid'), user, pwd, authType, function(response){
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

        this.attachDeleteHandlers = function() {
            var sp = this.spAjaxCalls;
            this.tbody.find('input[type="button"][name="delete"]').click(function(){
                var trrow = $(this).parent().parent();
                sp.deletePersonalMount(trrow.data('mid'), function(response){
                    if (response.status === 'success') {
                        trrow.remove();
                    }
                });
            });
        };

        this.reloadTable = function() {
            var codeGen = this.codeGenerator;
            var targetBody = this.tbody;
            var currentTableManager = this;
            this.spAjaxCalls.getMountsForUser('personal', function(response){
                if (response.status === 'success') {
                    var allRows = '';
                    $.each(response.data, function(index, mount){
                        allRows += codeGen.generateTableRow(mount);
                    });
                    targetBody.empty().append(allRows);
                    currentTableManager.attachEditHandlers();
                    currentTableManager.attachDeleteHandlers();
                    targetBody.find("select.sp-select").select2({minimumResultsForSearch: -1, width: 'resolve' });
                }
            });
        };

        this.extraClearInputs = function(baseElement) {
            baseElement.find("input, select").each(function(){
                var thisElement = $(this);
                if (thisElement.is("select")) {
                    thisElement.find('option').removeAttr("selected");
                    thisElement.find('option:first').attr("selected", "selected");
                    thisElement.select2({minimumResultsForSearch: -1, width: 'resolve' });
                } else if (thisElement.attr("type") === "text" || thisElement.attr("type") === "password") {
                    thisElement.val('');
                } else if (thisElement.attr("type") === "checkbox") {
                    thisElement.prop("checked", false);
                    thisElement.change();
                }
            });
            baseElement.find("select:first").find('option').remove().end().append('<option value="0">No Document Library</option>');
            baseElement.find("select:first").select2("enable", false);
            baseElement.find("select:first").select2({minimumResultsForSearch: -1, width: 'resolve' });
            //
            $("#authType").find('option').removeAttr("selected");
            $("#authType").find('option:eq(1)').attr("selected", "selected");
            $("#authType").select2({minimumResultsForSearch: -1, width: 'resolve' });
            $('#SPGuser').hide();
            $('#SPGpass').hide();
        };

        this.saveData = function(baseElement) {
            var allData = {};

            baseElement.find("input, select").each(function(){
                var thisElement = $(this);
                if (thisElement.is("select")) {
                    allData[thisElement.attr("name")] = thisElement.val();
                } else if (thisElement.attr("type") === "text" || thisElement.attr("type") === "password") {
                    allData[thisElement.attr("name")] = thisElement.val();
                } else if (thisElement.attr("type") === "checkbox") {
                    allData[thisElement.attr("name")] = thisElement.prop("checked");
                }
            });

            allData.o = "savePersonalMountPoint";

            var currentTableManager = this;
            this.spAjaxCalls.savePersonalMount(allData, function(response){
                if (response.status === "success") {
                    currentTableManager.extraClearInputs(baseElement);
                    currentTableManager.reloadTable();
                } else {
                    OC.dialogs.alert(response.data.message,
                        escapeHTML(t('sharepoint', 'Error checking the SharePoint configuration')));
                }
            });
        };
    }

    $(document).ready(function(){

        $('select.sp-select').select2({minimumResultsForSearch: -1, width: 'resolve' });


        $('#SPSaveCredentialsButton').click(function(){
            $.post( OC.filePath('sharepoint', 'ajax', 'settingsUserAJAX.php'),
                    {o: 'setUserGlobalCredentials', u: $('#SPGlobalUsername').val(), p: $('#SPGlobalPassword').val(), c: true},
                    function(raw) {
                        if(raw.status === "error"){
                            showAlert(t('sharepoint', 'There was an error saving your credentials'));
                            return;
                        }
                        showAlert(t('sharepoint', 'Credentials stored'));
                    });
        });


        $('#authType').change(function(){
            if($('#authType').val() === "2"){
                $('#SPGuser').hide();
                $('#SPGpass').hide();
            }
            else{
                $('#SPGuser').show();
                $('#SPGpass').show();
            }
        });

        var showAlert = function(message){
            var row = OC.Notification.showHtml(message);
            setTimeout(function() {
                OC.Notification.hide(row);
            }, 3000);
        };

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
            personalMountTableManagerSP.saveData($(this).parent().parent());
            // base element should be the tfoot's tr
        });

        $('#refreshList').on('click', function() {

            var user="anon", password="anon", authType="1";

            if(!$('#SPMountPoint').val()) {
                OC.dialogs.alert(t("sharepoint", "Please enter a local folder name"), t("sharepoint", "SharePoint"));
            }
            else if(!$('#SPUrl').val()) {
                OC.dialogs.alert(t("sharepoint", "Please enter a SharePoint site URL"), t("sharepoint", "SharePoint"));
            }
            else{
                if($('#authType').val() === "2"){
                    user = $('#SPGlobalUsername').val();
                    password = $('#SPGlobalPassword').val();
                    authType="2";
                } else{
                    user = $('#SPGuser').val();
                    password = $('#SPGpass').val();
                }
                //Disable select
                $('#selectList').select2("enable", false);
                $('#selectList').select2({minimumResultsForSearch: -1, width: 'resolve' });

                $('#refreshListAdminLoader').show();
                $('#s2id_selectList').hide();

                $.ajax({ type: "POST",
                    url: OC.filePath('sharepoint', 'ajax', 'settingsUserAJAX.php'),
                    data: {o: 'getDocumentList', u: user, p: password, url: $('#SPUrl').val(), a: authType},
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

        var tableManagerSP = new PersonalTableManagerSP($('#SPadminPersonalMountPoints table tbody'));

        tableManagerSP.attachSaveHandlers();
        tableManagerSP.attachEditHandlers();

        var personalMountTableManagerSP = new PersonalMountTableManagerSP($('#SPPersonalMountPoints table tbody'));
        personalMountTableManagerSP.attachEditHandlers();
        personalMountTableManagerSP.attachDeleteHandlers();

        var preajaxCallback = function(positionElement) {
            if (typeof this.showTimes === "undefined") {
                this.showTimes = 0;
            }
            if (this.showTimes > 0) {
                this.showTimes++;
                return;
            }

            var spBaseDiv = $('#SPpersonal');
            if (spBaseDiv.children('img.loading').length === 0) {
                spBaseDiv.append('<img class="loading" style="display:none ; position:absolute" src="' + OC.imagePath('core', 'loading.gif') + '" />');
            }
            var loadingGif = $('#SPpersonal > img.loading');
            loadingGif.show().position({my: 'center center',
                at: 'center center',
                of: positionElement});
            this.showTimes++;
        };

        var postajaxCallback = function() {
            if (this.showTimes > 1) {
                this.showTimes--;
                return;
            }
            $('#SPpersonal > img.loading').hide();
            this.showTimes--;
        };

        tableManagerSP.spAjaxCalls.preajaxCallback = function() { preajaxCallback.call(tableManagerSP.spAjaxCalls, '#SPadminPersonalMountPoints table.grid');};
        tableManagerSP.spAjaxCalls.postajaxCallback = postajaxCallback;
        personalMountTableManagerSP.spAjaxCalls.preajaxCallback = function() { preajaxCallback.call(personalMountTableManagerSP.spAjaxCalls, '#SPPersonalMountPoints table.grid');};
        personalMountTableManagerSP.spAjaxCalls.postajaxCallback = postajaxCallback;
    });

})();
