<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright (C) 2019 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Metrics\Controller;

use OC\AppFramework\Http;
use OCA\Metrics\WriteToCSV;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\ILogger;
use OCP\IRequest;

class DownloadController extends Controller {
	/** @var WriteToCSV */
	private $writeToCSV;
	/** @var ILogger */
	private $logger;

	/**
	 * DownloadController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param WriteToCSV $writeToCSV
	 * @param ILogger $logger
	 */
	public function __construct($appName, IRequest $request, WriteToCSV $writeToCSV, ILogger $logger) {
		parent::__construct($appName, $request);
		$this->writeToCSV = $writeToCSV;
		$this->logger = $logger;
	}

	/**
	 * Download user metrics results in a csv format. This endpoint requires an active admin user session.
	 *
	 * @AdminRequired
	 * @NoCSRFRequired
	 *
	 * @return Response
	 */
	public function downloadUserMetricsAsAdmin() {
		return $this->buildDownloadUserMetricsResponse();
	}

	/**
	 * Download user metrics results in a csv format. This endpoint requires a shared secret in the request header.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @SharedSecretRequired
	 *
	 * @return Response
	 */
	public function downloadUserMetricsAsGuest() {
		return $this->buildDownloadUserMetricsResponse();
	}

	/**
	 * Builds the user metrics csv data and returns it as a response object.
	 *
	 * @return Response
	 */
	private function buildDownloadUserMetricsResponse() {
		$result = $this->writeToCSV->getUsersCSVData();
		if ($result) {
			return new DataDownloadResponse($result, $this->writeToCSV->getAttachFileName('users'), 'text/csv');
		}

		$this->logger->error("Could not get csv data.");
		return new DataResponse(['Could not get csv data.'], Http::STATUS_INTERNAL_SERVER_ERROR);
	}

	/**
	 * Download system metrics results in a csv format. This endpoint requires an active admin user session.
	 *
	 * @AdminRequired
	 * @NoCSRFRequired
	 *
	 * @return Response
	 */
	public function downloadSystemMetricsAsAdmin() {
		return $this->buildDownloadSystemMetricsResponse();
	}

	/**
	 * Download system metrics results in a csv format. This endpoint requires a shared secret in the request header.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @SharedSecretRequired
	 *
	 * @return Response
	 */
	public function downloadSystemMetricsAsGuest() {
		return $this->buildDownloadSystemMetricsResponse();
	}

	/**
	 * Builds the system metrics csv data and returns it as a response object.
	 *
	 * @return Response
	 */
	private function buildDownloadSystemMetricsResponse() {
		$result = $this->writeToCSV->getSystemCSVData();
		if ($result) {
			return new DataDownloadResponse($result, $this->writeToCSV->getAttachFileName('system'), 'text/csv');
		}

		$this->logger->error("Could not get csv data.");
		return new DataResponse(['Could not get csv data.'], Http::STATUS_INTERNAL_SERVER_ERROR);
	}
}
