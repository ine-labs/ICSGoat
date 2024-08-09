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

use Microsoft\Graph\Model\Group;
use Microsoft\Graph\Model\User;
use OC\AppFramework\Http;
use OCA\Graph\Service\UsersService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroup;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;

class GroupsController extends Controller {

	/**
	 * @var IURLGenerator
	 */
	private $generator;
	/**
	 * @var UsersService
	 */
	private $service;

	public function __construct($appName, IRequest $request, UsersService $service, IURLGenerator $generator) {
		parent::__construct($appName, $request);

		$this->generator = $generator;
		$this->service = $service;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function index(): JSONResponse {
		$top = $this->request->getParam('$top', 10);
		$skip = $this->request->getParam('$skip', 0);
		$expand = $this->request->getParam('$expand', null);
		if (!\in_array($expand, [null, 'members'], true)) {
			return $this->newErrorResponse('BadRequest', 'Parsing Select and Expand failed.', Http::STATUS_BAD_REQUEST);
		}
		$users = $this->service->listGroups($top, $skip);

		$users = \array_map(function (IGroup $group) use ($expand) {
			$g = new Group([
				'id' => $group->getGID(),
				'displayName' => $group->getDisplayName(),
			]);
			if ($expand === 'members') {
				// @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal
				$g->setMembers($this->getUsersInGroup($group));
			}
			return $g;
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
	 *
	 * @param string $id
	 * @return JSONResponse
	 * @throws \Exception
	 */
	public function show($id): JSONResponse {
		$group = $this->service->getGroup($id);
		if ($group === null) {
			return $this->newErrorResponse('ResourceNotFound', 'Resource not found.', Http::STATUS_NOT_FOUND);
		}

		$g = new Group([
			'id' => $group->getGID(),
			'displayName' => $group->getDisplayName(),
		]);

		return new JSONResponse($g);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return JSONResponse
	 * @throws \Exception
	 */
	public function members($id): JSONResponse {
		$top = $this->request->getParam('$top', 10);
		$skip = $this->request->getParam('$skip', 0);
		$group = $this->service->getGroup($id);
		if ($group === null) {
			return $this->newErrorResponse('ResourceNotFound', 'Resource not found.', Http::STATUS_NOT_FOUND);
		}

		$users = $this->getUsersInGroup($group, $top, $skip);
		$data = [ 'value' => \array_values($users)];
		if (\count($users) >= $top) {
			$skip += $top;
			$nextLink = $this->generator->linkToRouteAbsolute($this->request->getParam('_route'));
			$data['@odata.nextLink'] = "$nextLink?\$top=$top&\$skip=$skip";
		}
		return new JSONResponse($data);
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

	/**
	 * @param IGroup $group
	 * @param int $top
	 * @param int $skip
	 * @return array|IUser[]
	 */
	protected function getUsersInGroup(IGroup $group, $top = null, $skip = null): array {
		$users = $group->searchUsers('', $top, $skip);
		$users = \array_map(static function (IUser $user) {
			return new User([
				'id' => $user->getUID(),
				'displayName' => $user->getDisplayName(),
				'mail' => $user->getEMailAddress(),
			]);
		}, $users);
		return $users;
	}
}
