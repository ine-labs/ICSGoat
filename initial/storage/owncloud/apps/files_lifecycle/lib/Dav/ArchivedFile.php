<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle\Dav;

use OC\AppFramework\Http\Request;
use OCP\Files\FileInfo;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\File;
use OCP\Share\IManager;

/**
 * Class ArchivedFile
 *
 * @package OCA\Files_Lifecycle\Dav
 */
class ArchivedFile extends File {
	/**
	 * ArchivedFile constructor.
	 *
	 * @param View $view
	 * @param FileInfo $info
	 * @param null|IManager $shareManager
	 * @param Request|null $request
	 */
	public function __construct(
		View $view,
		FileInfo $info,
		IManager $shareManager = null,
		Request $request = null
	) {
		parent::__construct($view, $info, $shareManager, $request);
	}

	/**
	 * @param resource|string $data
	 *
	 * @return null|string
	 *
	 * @throws \Sabre\DAV\Exception
	 */
	public function put($data) {
		throw new Forbidden('Put content not allowed in the archive');
	}

	/**
	 * @return resource|void
	 *
	 * @throws Forbidden
	 */
	public function get() {
		throw new Forbidden('Not allowed to read file content in the archive');
	}

	/**
	 * @return null
	 *
	 * @throws Forbidden
	 */
	public function delete() {
		throw new Forbidden('Not allowed the to delete files in the archive');
	}
}
