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

use OCA\CustomGroups\CustomGroupsDatabaseHandler;
use OCP\IConfig;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\SimpleCollection;
use OCA\CustomGroups\Service\MembershipHelper;
use OCP\IGroupManager;

/**
 * Root collection for the custom groups and members
 */
class RootCollection extends SimpleCollection {
	/**
	 * RootCollection constructor.
	 *
	 * @param IGroupManager $groupManager
	 * @param CustomGroupsDatabaseHandler $groupsHandler groups database handler
	 * @param MembershipHelper $helper membership helper
	 * @param IConfig $config
	 */
	public function __construct(
		IGroupManager $groupManager,
		CustomGroupsDatabaseHandler $groupsHandler,
		MembershipHelper $helper,
		IConfig $config
	) {
		$children = [
			new GroupsCollection(
				$groupManager,
				$groupsHandler,
				$helper,
				$config
			),
			new UsersCollection(
				$groupManager,
				$groupsHandler,
				$helper,
				$config
			),
		];
		parent::__construct('customgroups', $children);
	}
}
