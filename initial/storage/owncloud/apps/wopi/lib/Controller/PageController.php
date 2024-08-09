<?php /** @noinspection HtmlUnknownTag */

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

use OC\HintException;
use OC\Security\CSP\ContentSecurityPolicy;
use OCA\WOPI\Service\DiscoveryService;
use OCA\WOPI\Service\FileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\ILogger;
use OCP\IRequest;
use OCP\Files\Node;

class PageController extends Controller {

	/** @var ILogger */
	private $logger;
	/** @var DiscoveryService */
	private $discoveryService;
	/** @var FileService */
	private $fileService;

	/**
	 * PageController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param DiscoveryService $discoveryService
	 * @param FileService $fileService
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		ILogger $logger,
		DiscoveryService $discoveryService,
		FileService $fileService
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->discoveryService = $discoveryService;
		$this->fileService = $fileService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param string $shareToken
	 * @param int|null $fileId
	 * @return TemplateResponse
	 * @throws HintException
	 */
	public function OfficePublicLink($shareToken, $fileId): TemplateResponse {
		$this->logger->debug("ShareFileIndex for $shareToken/$fileId", ['app' => 'wopi']);
		$file = $this->fileService->getByShareToken($shareToken, $fileId);

		return $this->getTemplateResponse('view', $file, $shareToken);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $_action
	 * @param int $fileId
	 * @return TemplateResponse
	 * @throws HintException
	 */
	public function Office($_action, $fileId): TemplateResponse {
		$this->logger->debug("FileIndex for $fileId", ['app' => 'wopi']);
		$file = $this->fileService->getByFileId($fileId);
		
		return $this->getTemplateResponse($_action, $file, null);
	}

	/**
	 * @param string $_action
	 * @param Node $file
	 * @param string|null $shareToken
	 * @return TemplateResponse
	 * @throws HintException
	 */
	private function getTemplateResponse($_action, Node $file, $shareToken): TemplateResponse {
		$info = new \SplFileInfo($file->getName());
		$data = [
			'key' => 'wopi',
			'data-id' => $file->getId(),
			'data-mime' => $file->getMimetype(),
			'data-ext' => $info->getExtension(),
			'data-fileName' => $info->getBasename(),
			'data-shareToken' => $shareToken,
			'data-action' => $_action
		];
		
		\OCP\Util::addHeader('data', $data);

		$resp = new TemplateResponse('wopi', 'main', [], 'base');

		$policy = $this->getCsp();
		$resp->setContentSecurityPolicy($policy);

		return $resp;
	}

	/**
	 * @return ContentSecurityPolicy
	 * @throws HintException
	 */
	private function getCsp(): ContentSecurityPolicy {
		$hosts = [];
		$hosts[] = $this->discoveryService->getOfficeOnlineUrl();
		$data = $this->discoveryService->getDiscoveryData();
		foreach ($data as $appActions) {
			foreach ($appActions as $urls) {
				if (\is_array($urls)) {
					// Map of extension to url
					foreach ($urls as $ext => $url) {
						if ($ext !== 'App') {
							$hosts[]= $url;
						}
					}
				} else {
					// all other urls
					$hosts[]= $urls;
				}
			}
		}

		$wopiHosts = \array_map(function ($url) {
			$urlParts = \parse_url($url);
			/** @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset */
			$wopiHost = $urlParts['host'];
			if (isset($urlParts['port'])) {
				$wopiHost .= ":{$urlParts['port']}";
			}
			return $wopiHost;
		}, $hosts);

		$policy = new ContentSecurityPolicy();
		foreach ($wopiHosts as $wopiHost) {
			$policy->addAllowedFrameDomain($wopiHost);
			$policy->addAllowedConnectDomain($wopiHost);
			$policy->addAllowedImageDomain($wopiHost);
		}

		return $policy;
	}
}
