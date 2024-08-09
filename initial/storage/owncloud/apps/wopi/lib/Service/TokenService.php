<?php /** @noinspection HtmlUnknownTag */

/**
 * ownCloud Wopi
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @copyright 2018 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\WOPI\Service;

use Firebase\JWT\JWT;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;

require_once __DIR__ . '/../../vendor/autoload.php';

class TokenService {

	/** @var IConfig */
	private $config;
	/** @var ITimeFactory */
	private $timeFactory;
	/**
	 * @var IURLGenerator
	 */
	private $generator;
	public function __construct(
		IConfig $config,
		ITimeFactory $timeFactory,
		IURLGenerator $generator
	) {
		$this->config = $config;
		$this->timeFactory = $timeFactory;
		$this->generator = $generator;
	}

	/**
	 * @param string $fileId
	 * @param string $folderUrl
	 * @param IUser $user
	 * @return array
	 * @throws \Exception
	 */
	public function GenerateNewAuthUserAccessToken(string $fileId, string $folderUrl, IUser $user): array {
		if ($fileId === '' || $folderUrl === '') {
			throw new \InvalidArgumentException();
		}
		$key = $this->getTokenKey();

		$expiry = $this->timeFactory->getTime() + (int)$this->config->getAppValue('wopi', 'access-token.validity', (string)(4 * 60 * 60));
		$token = JWT::encode([
			'uid' => $user->getUID(),
			'fid' => $fileId,
			'furl' => $folderUrl,
			'exp' => $expiry
		], $key);

		$wopiSrc = $this->getWopiSrc($fileId, $key);

		return [
			'token' => $token,
			'expires' => $expiry * 1000,
			'wopi_src' => $wopiSrc
		];
	}

	/**
	 * @param string $fileId
	 * @param string $folderUrl
	 * @param string $shareToken
	 * @return array
	 * @throws \Exception
	 */
	public function GenerateNewPublicLinkAccessToken(string $fileId, string $folderUrl, $shareToken): array {
		if ($fileId === '' || $folderUrl === '') {
			throw new \InvalidArgumentException();
		}
		$key = $this->getTokenKey();

		$expiry = $this->timeFactory->getTime() + (int)$this->config->getAppValue('wopi', 'access-token.validity', (string)(4 * 60 * 60));
		$token = JWT::encode([
			'uid' => '',
			'st' => $shareToken,
			'fid' => $fileId,
			'furl' => $folderUrl,
			'exp' => $expiry
		], $key);

		$wopiSrc = $this->getWopiSrc($fileId, $key);

		return [
			'token' => $token,
			'expires' => $expiry * 1000,
			'wopi_src' => $wopiSrc
		];
	}

	/**
	 * @param string $access_token
	 * @param string $fileId
	 * @return array
	 * @throws SecurityException
	 */
	public function verifyToken(string $access_token, string $fileId): array {
		try {
			$fileId = $this->extractFileId($fileId);

			$token = JWT::decode($access_token, $this->getTokenKey(), ['HS256']);
			if ($token->fid !== $fileId) {
				throw new SecurityException('Token not for the given fileId', 401);
			}

			if ($token->uid !== '') {
				return [
					'UserId' => $token->uid,
					'FolderUrl' => $token->furl,
					'FileId' => $fileId,
				];
			}

			return [
				'ShareToken' => $token->st,
				'FolderUrl' => $token->furl,
				'FileId' => $fileId,
			];
		} catch (\UnexpectedValueException $ex) {
			throw new SecurityException($ex->getMessage(), 401);
		}
	}

	private function extractFileId(string $fileId): string {
		# not a JWT - just return
		$tks = \explode('.', $fileId);
		if (\count($tks) !== 3) {
			return $fileId;
		}
		return JWT::decode($fileId, $this->getTokenKey(), ['HS256'])->f;
	}

	/**
	 * Reads the jwt secret key which is used for jwt computation
	 * @return string
	 * @throws \Exception
	 */
	private function getTokenKey() : string {
		$key = $this->config->getSystemValue('wopi.token.key', null);
		if ($key === null) {
			throw new \Exception('System configuration <wopi.token.key> is missing');
		}
		return $key;
	}

	/**
	 * @param string $fileId
	 * @param string $key
	 * @return mixed|string
	 */
	private function getWopiSrc(string $fileId, string $key) {
		$wopiSrc = $this->config->getSystemValue('wopi.proxy.url', null);
		if ($wopiSrc) {
			$url = $this->generator->linkToRouteAbsolute('wopi.discovery.index');
			$url = substr($url, 0, -14) . 'files/';

			# assumption: proxy url holds e.g. https://office.owncloud.com/wopi/files/
			$fileId = JWT::encode([
				'u' => $url,
				'f' => $fileId
			], $key);
			$wopiSrc .= $fileId;
		}
		return $wopiSrc;
	}
}
