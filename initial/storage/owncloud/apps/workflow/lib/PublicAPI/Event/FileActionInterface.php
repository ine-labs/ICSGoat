<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Workflow\PublicAPI\Event;

use OCA\Workflow\PublicAPI\Engine\FlowInterface;

interface FileActionInterface {
	public const CACHE_INSERT = 'insertCache';
	public const FILE_CREATE = 'createFile';
	public const FILE_DELETE = 'deleteFile';
	public const FILE_UPDATE = 'updateFile';
	public const FILE_RENAME = 'renameFile';

	/**
	 * @return FlowInterface
	 */
	public function getFlow();

	/**
	 * @return string
	 */
	public function getPath();

	/**
	 * @return int
	 * @throws \BadMethodCallException when the file ID is not available on the event.
	 */
	public function getFileId();

	/**
	 * Stops the propagation of the event to further event listeners.
	 */
	public function stopPropagation();
}
