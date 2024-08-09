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

(function() {

    /**
     * External storage file list
     */
    var FileList = function($el, options) {
        this.initialize($el, options);
    };

    FileList.prototype = _.extend({}, OCA.Files.FileList.prototype, {
        appName: 'sharepoint',

        initialize: function($el, options) {
            OCA.Files.FileList.prototype.initialize.apply(this, arguments);
            if (this.initialized) {
                return;
            }
        },

        _createRow: function(fileData) {
            // TODO: hook earlier and render the whole row here
            var $tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);

            var $siteColumn = $('<td class="filename"><span></span></td>');
            var $documentListColumn = $('<td class="column-documentList"><span></span></td>');
            var $scopeColumn = $('<td class="column-scope"><span></span></td>');
            var $authColumn = $('<td class="column-authType"><span></span></td>');

            var scopeText = t('sharepoint', 'Personal');
            if (fileData.scope === 'system') {
                scopeText = t('sharepoint', 'System');
            }
            $tr.find('.filesize,.date').remove();

            $siteColumn.find('span').text(fileData.site);
            $siteColumn.find('span').attr('title', fileData.site);

            $documentListColumn.find('span').text(fileData.documentList);
            $documentListColumn.find('span').attr('title', fileData.documentList);

            $scopeColumn.find('span').text(scopeText);
            $scopeColumn.find('span').attr('title', scopeText);

            $authColumn.find('span').text(fileData.authType);
            $authColumn.find('span').attr('title', fileData.authType);

            $tr.find('td.filename').after($authColumn).after($scopeColumn).after($documentListColumn).after($siteColumn);

            $tr.find('td.filename input:checkbox').remove();
            return $tr;
        },

        updateEmptyContent: function() {
            var dir = this.getCurrentDirectory();
            if (dir === '/') {
                // root has special permissions
                this.$el.find('#emptycontent').toggleClass('hidden', !this.isEmpty);
                this.$el.find('#filestable thead th').toggleClass('hidden', this.isEmpty);
            }
            else {
                OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
            }
        },

        getDirectoryPermissions: function() {
            return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
        },

        updateStorageStatistics: function() {
            // no op because it doesn't have
            // storage info like free space / used space
        },

        reload: function() {
            this.showMask();
            if (this._reloadCall) {
                this._reloadCall.abort();
            }
            this._reloadCall = $.ajax({
                url: OC.linkToOCS('apps/sharepoint/api/v1') + 'mounts',
                data: {
                    format: 'json'
                },
                type: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('OCS-APIREQUEST', 'true');
                }
            });
            var callBack = this.reloadCallback.bind(this);
            return this._reloadCall.then(callBack, callBack);
        },

        reloadCallback: function(result) {
            delete this._reloadCall;
            this.hideMask();

            if (result.ocs && result.ocs.data) {
                this.setFiles(this._makeFiles(result.ocs.data));
            }
            else {
                // TODO: error handling
            }
        },

        setSort: function(sort, direction) {
            var comparator = this.Comparators[sort] || this.Comparators.name;
            this._sort = sort;
            this._sortDirection = (direction === 'desc')?'desc':'asc';
            this._sortComparator = comparator;

            if (direction === 'desc') {
                this._sortComparator = function(fileInfo1, fileInfo2) {
                    return -comparator(fileInfo1, fileInfo2);
                };
            }
            this.$el.find('thead th .sort-indicator')
                .removeClass(this.SORT_INDICATOR_ASC_CLASS)
                .removeClass(this.SORT_INDICATOR_DESC_CLASS)
                .toggleClass('hidden', true)
                .addClass(this.SORT_INDICATOR_DESC_CLASS);

            this.$el.find('thead th.column-' + sort + ' .sort-indicator')
                .removeClass(this.SORT_INDICATOR_ASC_CLASS)
                .removeClass(this.SORT_INDICATOR_DESC_CLASS)
                .toggleClass('hidden', false)
                .addClass(direction === 'desc' ? this.SORT_INDICATOR_DESC_CLASS : this.SORT_INDICATOR_ASC_CLASS);
        },

        /**
         * Converts the OCS API  response data to a file info
         * list
         * @param OCS API mounts array
         * @return array of file info maps
         */
        _makeFiles: function(data) {
            var files = _.map(data, function(fileData) {
                fileData.icon = OC.imagePath('core', 'filetypes/folder-external');
                fileData.mountType = 'external';
                return fileData;
            });

            files.sort(this._sortComparator);

            return files;
        }
    });

    FileList.prototype.Comparators = {
        /**
         * Compares two file infos by name, making directories appear
         * first.
         *
         * @param fileInfo1 file info
         * @param fileInfo2 file info
         * @return -1 if the first file must appear before the second one,
         * 0 if they are identify, 1 otherwise.
         */
        name: function(fileInfo1, fileInfo2) {
            if (fileInfo1.type === 'dir' && fileInfo2.type !== 'dir') {
                return -1;
            }
            if (fileInfo1.type !== 'dir' && fileInfo2.type === 'dir') {
                return 1;
            }
            return fileInfo1.name.localeCompare(fileInfo2.name);
        },
        site: function(fileInfo1, fileInfo2) {
            if (fileInfo1.site === fileInfo2.site) {
                return FileList.prototype.Comparators.name(fileInfo1, fileInfo2);
            } else {
                return fileInfo1.site.localeCompare(fileInfo2.site);
            }
        },
        documentList: function(fileInfo1, fileInfo2) {
            if (fileInfo1.documentList === fileInfo2.documentList) {
                return FileList.prototype.Comparators.name(fileInfo1, fileInfo2);
            } else {
                return fileInfo1.documentList.localeCompare(fileInfo2.documentList);
            }
        },
        authType: function(fileInfo1, fileInfo2) {
            if (fileInfo1.authType === fileInfo2.authType) {
                return FileList.prototype.Comparators.name(fileInfo1, fileInfo2);
            } else {
                return fileInfo1.authType.localeCompare(fileInfo2.authType);
            }
        },
        scope: function(fileInfo1, fileInfo2) {
            if (fileInfo1.scope === fileInfo2.scope) {
                return FileList.prototype.Comparators.name(fileInfo1, fileInfo2);
            } else {
                return fileInfo1.scope.localeCompare(fileInfo2.scope);
            }
        }
    };

    OCA.SP.FileList = FileList;
})();
