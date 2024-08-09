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

namespace OCA\Files_Lifecycle;

use OC\Files\Node\Folder;
use OCA\Files_Lifecycle\Dav\ArchivePlugin;
use OCA\Files_Lifecycle\Entity\Property;
use OCA\Files_Lifecycle\Entity\PropertyMapper;
use OCP\Files\IRootFolder;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class UploadPlugin
 *
 * @package OCA\Files_Lifecycle
 */
class UploadPlugin {
	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;

	/**
	 * @var PropertyMapper
	 */
	protected $mapper;

	/**
	 * UploadPlugin constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param PropertyMapper $mapper
	 */
	public function __construct(IRootFolder $rootFolder, PropertyMapper $mapper) {
		$this->rootFolder = $rootFolder;
		$this->mapper = $mapper;
	}

	/**
	 * Sets the upload time property
	 *
	 * @param GenericEvent $event
	 *
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 *
	 * @return void
	 */
	public function setUploadTime(GenericEvent $event) {
		$path = $event->getArgument('path');
		$file = $this->rootFolder->get($path);

		if ($file instanceof Folder) {
			return;
		}

		$result = $this->excludeFromArchive($path);
		if ($result === true) {
			return;
		}
		$now = new \DateTime();

		$entity = new Property();
		$entity->setFileid($file->getId());
		$entity->setPropertyname(ArchivePlugin::UPLOAD_TIME);
		$entity->setPropertyvalue($now->format(\DateTime::ATOM));
		$this->mapper->insert($entity);
	}

	/**
	 * Check Paths to exclude
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function excludeFromArchive($path) {
		$uid = "";
		$path = \ltrim($path, "/");
		/**
		 * Making assumption that path has to be $uid/files/
		 * So if the path when exploded doesn't make up to this
		 * pattern, then this method will return true. Else return
		 * false.
		 */
		$splitPath = \explode("/", $path);
		if (\count($splitPath) < 2) {
			//For example if the path has /avatars
			return true;
		} else {
			//For example if the path has /avatars/12
			if ($splitPath[1] !== 'files') {
				return true;
			} else {
				//After doing ltrim chances are bit low for empty. Still who knows
				if ($splitPath[0] === "") {
					return true;
				}
				$uid = $splitPath[0];
			}
		}
		$userPathStarts = $uid . "/files/";

		/**
		 * Final check if the path has $uid/files/ in it
		 * if so then get the data logged. Else exclude
		 * them from the logger
		 */
		if (\strpos($path, $userPathStarts) !== false) {
			return false;
		}
		return true;
	}
}
