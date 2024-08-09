<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Engine\Event;

use OCA\Workflow\PublicAPI\Engine\FlowInterface;
use OCA\Workflow\PublicAPI\Event\FileActionInterface;
use Symfony\Component\EventDispatcher\Event;

class FileAction extends Event implements FileActionInterface {

	/** @var FlowInterface */
	protected $flow;

	/** @var string */
	protected $path;

	/** @var int|null */
	protected $fileId;

	/**
	 * FileAction constructor.
	 *
	 * @param FlowInterface $flow
	 * @param string $path
	 * @param int|null $fileId
	 */
	public function __construct(FlowInterface $flow, $path, $fileId = null) {
		$this->flow = $flow;
		$this->path = $path;
		$this->fileId = $fileId;
	}

	/**
	 * @return FlowInterface
	 */
	public function getFlow() {
		return $this->flow;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return int
	 * @throws \BadMethodCallException when the file ID is not available on the event.
	 */
	public function getFileId() {
		if ($this->fileId === null) {
			throw new \BadMethodCallException('File ID is not available on this event');
		}

		return $this->fileId;
	}
}
