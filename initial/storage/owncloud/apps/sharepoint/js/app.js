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
OCA.SP.App = {

    fileList: null,

    initList: function($el) {
        if (this.fileList) {
            return this.fileList;
        }

        this.fileList = new OCA.SP.FileList(
            $el,
            {
                scrollContainer: $('#app-content'),
                fileActions: this._createFileActions()
            }
        );

        this._extendFileList(this.fileList);
        this.fileList.appName = t('sharepoint', 'SP storage');
        return this.fileList;
    },

    removeList: function() {
        if (this.fileList) {
            this.fileList.$fileList.empty();
        }
    },

    _createFileActions: function() {
        // inherit file actions from the files app
        var fileActions = new OCA.Files.FileActions();
        fileActions.registerDefaultActions();

        // when the user clicks on a folder, redirect to the corresponding
        // folder in the files app instead of opening it directly
        fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
            OCA.Files.App.setActiveView('files', {silent: true});
            OCA.Files.App.fileList.changeDirectory(context.$file.attr('data-path') + '/' + filename, true, true);
        });
        fileActions.setDefault('dir', 'Open');
        return fileActions;
    },

    _extendFileList: function(fileList) {
        // remove size column from summary
        fileList.fileSummary.$el.find('.filesize').remove();
    }
};

$(document).ready(function() {
    $('#app-content-spmounts').on('show', function(e) {
        OCA.SP.App.initList($(e.target));
    });
    $('#app-content-spmounts').on('hide', function() {
        OCA.SP.App.removeList();
    });
});
