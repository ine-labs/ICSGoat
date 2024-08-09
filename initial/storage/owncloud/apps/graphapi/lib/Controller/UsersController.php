<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Graph\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';

use Microsoft\Graph\Model\User;
use OC\AppFramework\Http;
use OCA\Graph\Service\UsersService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;

class UsersController extends Controller {

	/**
	 * @var UsersService
	 */
	private $usersService;
	/**
	 * @var IURLGenerator
	 */
	private $generator;

	public function __construct($appName, IRequest $request, UsersService $usersService, IURLGenerator $generator) {
		parent::__construct($appName, $request);

		$this->usersService = $usersService;
		$this->generator = $generator;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @return JSONResponse
	 */
	public function index(): JSONResponse {
		$top = $this->request->getParam('$top', 10);
		$skip = $this->request->getParam('$skip', 0);
		$users = $this->usersService->listUsers($top, $skip);

		$users = \array_map(static function (IUser $user) {
			return new User([
				'id' => $user->getUID(),
				'displayName' => $user->getDisplayName(),
				'mail' => $user->getEMailAddress(),
			]);
		}, $users);

		$data = [ 'value' => \array_values($users)];
		if (\count($users) >= $top) {
			$skip += $top;
			$nextLink = $this->generator->linkToRouteAbsolute($this->request->getParam('_route'));
			$data['@odata.nextLink'] = "$nextLink?\$top=$top&\$skip=$skip";
		}
		return new JSONResponse($data);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @param string $id
	 * @return JSONResponse
	 * @throws \Exception
	 */
	public function show($id): JSONResponse {
		$user = $this->usersService->getUser($id);
		if (!$user) {
			return $this->newErrorResponse('ResourceNotFound', 'Resource not found.', Http::STATUS_NOT_FOUND);
		}
		$user = new User([
			'id' => $user->getUID(),
			'displayName' => $user->getDisplayName(),
			'mail' => $user->getEMailAddress(),
		]);

		return new JSONResponse($user);
	}

	/**
	 * @param string $code
	 * @param string $message
	 * @param int $httpStatus
	 * @return JSONResponse
	 * @throws \Exception
	 */
	protected function newErrorResponse(string $code, string $message, int $httpStatus): JSONResponse {
		$data = [
			'error' => [
				'code' => $code,
				'message' => $message,
				'innerError' => [
					'request-id' => $this->request->getId(),
					'date' => (new \DateTime())->format(\DateTime::ATOM)
				]
			]
		];
		return new JSONResponse($data, $httpStatus);
	}
}
