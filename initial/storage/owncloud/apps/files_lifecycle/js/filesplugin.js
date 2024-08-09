/**
 * @author Tom Needham <tom@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
(function() {
  
  _.extend(OC.Files.Client, {
    PROPERTY_LIFECYCLE_ARCHIVING_TIME:	'{' + OC.Files.Client.NS_OWNCLOUD + '}archiving-time',
    PROPERTY_LIFECYCLE_ARCHIVED_TIME:	'{' + OC.Files.Client.NS_OWNCLOUD + '}archived-time',
    PROPERTY_LIFECYCLE_RESTORED_TIME:	'{' + OC.Files.Client.NS_OWNCLOUD + '}restored-time',
    PROPERTY_LIFECYCLE_EXPIRING_TIME:	'{' + OC.Files.Client.NS_OWNCLOUD + '}expiring-time'
  });

  OCA.Files_Lifecycle = _.extend({}, OCA.Files_Lifecycle);
	if (!OCA.Files_Lifecycle) {

    /**
     * @namespace
     */
    OCA.Files_Lifecycle = {};
  }

  /**
   * @namespace
   */
  OCA.Files_Lifecycle.FilesPlugin = {

    // Dont attach to the file client in these lists
    ignoreLists: [
      'files_trashbin',
      'files.public'
    ],

    attach: function(fileList) {
      var self = this;

      // Ignore if we are on another files list somewhere else
      if (this.ignoreLists.indexOf(fileList.id) >= 0) {
        return;
      }
  
      // Add the property to the JS client so that it is requested
      var oldGetWebdavProperties = fileList._getWebdavProperties;
      fileList._getWebdavProperties = function() {
        var props = oldGetWebdavProperties.apply(this, arguments);
        props.push(OC.Files.Client.PROPERTY_LIFECYCLE_ARCHIVED_TIME);
        props.push(OC.Files.Client.PROPERTY_LIFECYCLE_ARCHIVING_TIME);
        props.push(OC.Files.Client.PROPERTY_LIFECYCLE_EXPIRING_TIME);
        props.push(OC.Files.Client.PROPERTY_LIFECYCLE_RESTORED_TIME);
        return props;
      };

      // Parse the webdav property 
      fileList.filesClient.addFileInfoParser(function(response) {
        var data = {};
        var props = response.propStat[0].properties;
        var archivingTime = props[OC.Files.Client.PROPERTY_LIFECYCLE_ARCHIVING_TIME];
        if (!_.isUndefined(archivingTime) && archivingTime !== '') {
          data.archivingTime = new Date(archivingTime);
        }
        var archivedTime = props[OC.Files.Client.PROPERTY_LIFECYCLE_ARCHIVED_TIME];
        if (!_.isUndefined(archivedTime) && archivedTime !== '') {
          data.archivedTime = new Date(archivedTime);
        }
        var restoredTime = props[OC.Files.Client.PROPERTY_LIFECYCLE_RESTORED_TIME];
        if (!_.isUndefined(restoredTime) && restoredTime !== '') {
          data.restoredTime = new Date(restoredTime);
        }
        var expiringTime = props[OC.Files.Client.PROPERTY_LIFECYCLE_EXPIRING_TIME];
        if (!_.isUndefined(expiringTime) && expiringTime !== '') {
          data.expiringTime = new Date(expiringTime);
        }
        return data;
      });

      // Add the info view to the files app
      fileList.registerDetailView(new OCA.Files_Lifecycle.LifecycleInfoView());
    }

  }

 })();

OC.Plugins.register('OCA.Files.FileList', OCA.Files_Lifecycle.FilesPlugin);