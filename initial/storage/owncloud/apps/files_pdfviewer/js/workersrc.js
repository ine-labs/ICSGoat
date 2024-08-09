/**
 * Checks if the page is displayed in an iframe. If not redirect to /.
 **/

function redirectIfNotDisplayedInFrame () {
	try {
		if (window.frameElement || location.href.indexOf('?file=blob') !== -1) {
			return;
		}
	} catch (e) {}

	window.location.href = '/';
}
redirectIfNotDisplayedInFrame();

function deferredViewerConfig() {
	if (location.href.indexOf('?file=blob') !== -1) {
		document.getElementById('secondaryToolbarClose').addEventListener(
			'click',
			function() {
				window.close();
			}
		);
	}
	try {
		PDFViewerApplicationOptions.set('disablePreferences', true);
		PDFViewerApplicationOptions.set('workerSrc', document.getElementsByTagName('head')[0].getAttribute('data-workersrc'));
		PDFViewerApplicationOptions.set('locale', getSanitizedCurrentLocale());
		PDFViewerApplicationOptions.set('cMapUrl', document.getElementsByTagName('head')[0].getAttribute('data-cmapurl'));
		PDFViewerApplicationOptions.set('sandboxBundleSrc', document.getElementsByTagName('head')[0].getAttribute('data-sandbox'));
		PDFViewerApplicationOptions.set('printResolution', 300);
		PDFViewerApplicationOptions.set('externalLinkTarget', pdfjsLib.LinkTarget.BLANK);
		PDFViewerApplicationOptions.set('isEvalSupported', false);
	} catch (e) {
	}
}

function getSanitizedCurrentLocale(){
	return parent.OC.getLocale().replace('_', '-');
}

// Wait until viewer is ready and patch it on the fly
parent.document.addEventListener('webviewerloaded', deferredViewerConfig, true);
parent.document.documentElement.lang = getSanitizedCurrentLocale();