<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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
 *
 */

namespace OCA\CustomGroups\Dav;

use OCP\IConfig;
use Sabre\DAV\ICollection;
use OCA\CustomGroups\CustomGroupsDatabaseHandler;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\Forbidden;
use OCA\CustomGroups\Service\MembershipHelper;
use OCP\IGroupManager;

/**
 * Collection of users
 */
class UsersCollection implements ICollection {

	/**
	 * Custom groups handler
	 *
	 * @var CustomGroupsDatabaseHandler
	 */
	private $groupsHandler;

	/**
	 * Membership helper
	 *
	 * @var MembershipHelper
	 */
	private $helper;

	/** @var IConfig */
	private $config;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * UsersCollection constructor.
	 *
	 * @param IGroupManager $groupManager
	 * @param CustomGroupsDatabaseHandler $groupsHandler custom groups handler
	 * @param MembershipHelper $helper
	 * @param IConfig $config
	 */
	public function __construct(
		IGroupManager $groupManager,
		CustomGroupsDatabaseHandler $groupsHandler,
		MembershipHelper $helper,
		IConfig $config
	) {
		$this->groupManager = $groupManager;
		$this->groupsHandler = $groupsHandler;
		$this->helper = $helper;
		$this->config = $config;
	}

	/**
	 * Not supported
	 *
	 * @param string $name name
	 * @param resource|string $data unused
	 * @throws MethodNotAllowed not supported
	 */
	public function createFile($name, $data = null) {
		throw new MethodNotAllowed('Cannot create regular nodes');
	}

	/**
	 * Creates a new custom group
	 *
	 * @param string $name group URI
	 * @throws MethodNotAllowed if the group already exists
	 */
	public function createDirectory($name) {
		throw new MethodNotAllowed('Cannot create user nodes');
	}

	/**
	 * Returns the given user's memberships
	 *
	 * @param string $name user id
	 * @return CustomGroupMembershipCollection user membership collection
	 * @throws Forbidden if the current user has insufficient permissions
	 */
	public function getChild($name) {
		// users can only query their own memberships
		// but ownCloud admin can query membership of any user
		if ($name === $this->helper->getUserId() || $this->helper->isUserSuperAdmin()) {
			return new GroupsCollection(
				$this->groupManager,
				$this->groupsHandler,
				$this->helper,
				$this->config,
				$name
			);
		}

		// regular user can only query for self
		throw new Forbidden('Insufficient permissions');
	}

	/**
	 * Not supported
	 *
	 * @throws MethodNotAllowed not supported
	 */
	public function getChildren() {
		throw new MethodNotAllowed('Not supported');
	}

	/**
	 * Returns whether a custom group exists.
	 *
	 * @param string $name user id
	 * @return boolean true if the group exists, false otherwise
	 */
	public function childExists($name) {
		try {
			$this->getChild($name);
		} catch (Forbidden $e) {
			return false;
		} catch (NotFound $e) {
			return false;
		}
		return true;
	}

	/**
	 * Not supported
	 *
	 * @throws MethodNotAllowed not supported
	 */
	public function delete() {
		throw new MethodNotAllowed('Cannot delete this collection');
	}

	/**
	 * Returns the name of the node.
	 *
	 * This is used to generate the url.
	 *
	 * @return string node name
	 */
	public function getName() {
		return 'users';
	}

	/**
	 * Not supported
	 *
	 * @param string $name name
	 * @throws MethodNotAllowed not supported
	 */
	public function setName($name) {
		throw new MethodNotAllowed('Cannot rename this collection');
	}

	/**
	 * Returns null
	 *
	 * @return int null
	 */
	public function getLastModified() {
		return null;
	}
}
