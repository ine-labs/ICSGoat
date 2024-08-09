/*
 * Copyright (c) 2013-2014 Lukas Reschke <lukas@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function (OCA) {

	OCA.FilesPdfViewer = OCA.FilesPdfViewer || {};

	/**
	 * @namespace OCA.FilesPdfViewer.PreviewPlugin
	 */
	OCA.FilesPdfViewer.PreviewPlugin = {

		/**
		 * @param fileList
		 */
		attach: function (fileList) {
			/*
			Since PDFjs dropped support for IE11 and old EDGE(not chromium based) up to version v2.7.570
			(https://github.com/mozilla/pdf.js/releases/tag/v2.7.570)
			We don't attach file actions on an unsupported browser,
			this falls back to download.
			*/
			if(!this.isSupportedBrowser()){
				console.warn('files_pdfviewer can not be attached due to browser incompatibility');
				return;
			}

			this._extendFileActions(fileList.fileActions);
		},

		hide: function () {
			$('#pdframe').remove();
			if ($('#isPublic').val() && $('#filesApp').val()) {
				$('#controls').removeClass('hidden');
				$('#content').removeClass('full-height');
				$('footer').removeClass('hidden');
			}

			FileList.setViewerMode(false);
			// replace the controls with our own
			$('#app-content #controls').removeClass('hidden');
		},

		/**
		 * @param fileName
		 * @param dir
		 */
		show: function (fileName, dir) {
			var self = this;
			var downloadUrl = this._getDownloadUrl(fileName, dir);
			var sharingToken = $("#sharingToken").val();
			var uri = 'apps/files_pdfviewer/candownload?path={path}';

			var params = {
				path : dir+"/"+fileName,
			};

			if(sharingToken){
				params.sharingToken = sharingToken;
				uri += '&sharingToken={sharingToken}';
			}

			$.get(OC.generateUrl(uri, params)).then(function (response) {
				if(!response.canDownload){
					OC.Notification.show(t('files_pdfviewer', 'This shared file does not have download permission and is possibly protected by secure view, please contact the owner of the file for granting permission or use a different viewer.'), {timeout : 7, type: 'error'});
				}else{
					self.renderPdfViewer(downloadUrl, true);
				}
			});
		},

		/**
		 * @param downloadUrl
		 * @param isFileList
		 */
		renderPdfViewer: function(downloadUrl, isFileList){
			var self = this;
			var $iframe;
			var isPdfVisible;
			var viewer = OC.generateUrl('/apps/files_pdfviewer/?file={file}', {
				file: downloadUrl
			});
			$iframe = $('<iframe id="pdframe" style="width:100%;height:100%;display:block;position:absolute;top:0;z-index:10;" src="' + viewer + '" sandbox="allow-downloads allow-scripts allow-same-origin allow-popups allow-modals allow-top-navigation" />');

			if (isFileList === true) {
				FileList.setViewerMode(true);
			}

			if ($('#isPublic').val()) {
				// force the preview to adjust its height
				$('#preview').append($iframe).css({
					height: '100%'
				});
				$('body').css({
					height: '100%'
				});
				$('#content').addClass('full-height');
				$('footer').addClass('hidden');
				$('#imgframe').addClass('hidden');
				$('.directLink').addClass('hidden');
				$('.directDownload').addClass('hidden');
				$('#controls').addClass('hidden');
			} else {
				$('#app-content').append($iframe);
			}

			$("#pageWidthOption").attr("selected", "selected");
			// replace the controls with our own
			$('#app-content #controls').addClass('hidden');

			// if a filelist is present, the PDF viewer can be closed to go back there
			$('#pdframe').on("load", function () {
				var iframe = $('#pdframe').contents();
				if ($('#fileList').length) {
					isPdfVisible = true;
					history.pushState({}, '', '#pdfviewer');
					iframe.find('#secondaryToolbarClose').click(function () {
						history.back()
					});
				} else {
					iframe.find("#secondaryToolbarClose").addClass('hidden');
				}
			});


			$(document).keyup(function (e) {
				if (isPdfVisible && e.keyCode == 27) {
					isPdfVisible = false;
					history.back();
				}
			});

			setTimeout(function () {
				$(window).one('popstate', function (e) {
					self.hide();
				});
			}, 0);
		},


		/**
		 * @param fileName
		 * @param dir
		 * @return string
		 * @private
		 */
		_getDownloadUrl: function(fileName, dir){
			var downloadUrl
			if ($('#isPublic').val()) {
				var sharingToken = $('#sharingToken').val();
				downloadUrl = OC.generateUrl('/s/{token}/download?files={files}&path={path}', {
					token: sharingToken,
					files: fileName,
					path: dir
				});
			} else {
				downloadUrl = Files.getDownloadUrl(fileName, dir);
			}

			return downloadUrl;
		},

		/**
		 * @param fileActions
		 * @private
		 */
		_extendFileActions: function (fileActions) {
			var self = this;
			fileActions.registerAction({
				name: 'FilesPdfViewer',
				displayName: t('files_pdfviewer', 'Open in PDF Viewer'),
				mime: 'application/pdf',
				iconClass: 'icon-toggle',
				permissions: OC.PERMISSION_READ,
				actionHandler: function (fileName, context) {
					self.show(fileName, context.dir);
				}
			});
			fileActions.setDefault('application/pdf', 'FilesPdfViewer');
		},

		isSupportedBrowser: function () {
			var userAgent = navigator.userAgent;
			var isInternetExplorer = userAgent.indexOf("MSIE ") > -1 || userAgent.indexOf("Trident/") > -1;
			var isOldEdge = userAgent.indexOf("edge") > -1;
			return !isInternetExplorer && !isOldEdge ;
		}
	};

})(OCA);

OC.Plugins.register('OCA.Files.FileList', OCA.FilesPdfViewer.PreviewPlugin);

// FIXME: Hack for single public file view since it is not attached to the fileslist
$(document).ready(function () {
	if ($('#isPublic').val() && $('#mimetype').val() === 'application/pdf') {
		var sharingToken = $('#sharingToken').val();
		var downloadUrl = OC.generateUrl('/s/{token}/download', {
			token: sharingToken
		});
		var viewer = OCA.FilesPdfViewer.PreviewPlugin;
		viewer.renderPdfViewer(downloadUrl, false);
	}
});
