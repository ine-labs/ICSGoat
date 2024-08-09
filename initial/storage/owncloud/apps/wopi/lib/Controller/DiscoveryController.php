<?php
/**
 * ownCloud Wopi
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Piotr Mrowczynski <piotr@owncloud.com>
 * @copyright 2021 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\WOPI\Controller;

use OCA\WOPI\Service\DiscoveryService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IRequest;

class DiscoveryController extends Controller {

	/** @var ILogger */
	private $logger;
	/** @var DiscoveryService */
	private $discoveryService;

	/**
	 * DiscoveryController constructor.
	 *
	 * @param string $appName
	 * @param ILogger $logger
	 * @param IRequest $request
	 * @param DiscoveryService $discoveryService
	 */
	public function __construct(
		string $appName,
		ILogger $logger,
		IRequest $request,
		DiscoveryService $discoveryService
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->discoveryService = $discoveryService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return JSONResponse
	 * @throws \Exception
	 */
	public function index(): JSONResponse {
		$this->logger->debug("WOPI server capabilities discovery index", ['app' => 'wopi']);
		return new JSONResponse($this->discoveryService->getDiscoveryData());
	}
}
