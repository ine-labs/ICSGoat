<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

namespace OCA\OAuth2\Controller;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;

use OCP\IAvatarManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;

class OpenIdConnectController extends ApiController {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUserSession */
	private $userSession;

	/** @var IAvatarManager */
	private $avatarManager;

	/**
	 * OAuthApiController constructor.
	 *
	 * @param string $AppName The app's name.
	 * @param IRequest $request The request.
	 * @param IUserSession $userSession
	 * @param IURLGenerator $urlGenerator The URL generator.
	 * @param IAvatarManager $avatarManager
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		IUserSession $userSession,
		IURLGenerator $urlGenerator,
		IAvatarManager $avatarManager
	) {
		parent::__construct($AppName, $request);

		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->avatarManager = $avatarManager;
	}

	/**
	 * Implements OpenID Connect UserInfo endpoint
	 *
	 * @see https://connect2id.com/products/server/docs/api/userinfo
	 *
	 * @return JSONResponse The claims as JSON Object.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @CORS
	 * @throws \Exception
	 */
	public function userInfo() {
		$user = $this->userSession->getUser();
		if ($user === null) {
			// should never happen
			throw new \RuntimeException('Not logged in');
		}

		$data = [
			'sub' => $user->getUID()
		];
		$avatarUrl = $this->getAvatarUrl($user);
		if ($avatarUrl !== null) {
			$data['picture'] = $avatarUrl;
		}
		if ($user->getDisplayName() !== null) {
			$data['name'] = $user->getDisplayName();
		}
		if ($user->getEMailAddress() !== null) {
			$data['email'] = $user->getEMailAddress();
		}
		return new JSONResponse($data);
	}

	/**
	 * @param IUser $user
	 * @return string | null
	 * @throws \Exception
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getAvatarUrl($user) {
		$avatar = $this->avatarManager->getAvatar($user->getUID());
		if (!$avatar->exists()) {
			return null;
		}

		$avatarUrl = $this->urlGenerator->linkTo('', 'remote.php');
		$avatarUrl .= "/dav/avatars/{$user->getUID()}/96.jpeg";

		$avatarUrl = $this->urlGenerator->getAbsoluteURL($avatarUrl);
		return $avatarUrl;
	}
}
