<?php
/**
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license OCL
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\FilesClassifier;

use OC\Files\Storage\Wrapper\Wrapper;
use Icewind\Streams\CallbackWrapper;
use OCP\ITempManager;
use OCP\IUserSession;

class ClassifierWrapper extends Wrapper {
	/**
	 * Modes that are used for writing
	 *
	 * @var array
	 */
	private $writingModes = ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'];

	/** @var Handler */
	private $handler;

	/** @var IUserSession */
	private $userSession;

	/** @var ITempManager */
	private $tempManager;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->handler = $parameters['handler'];
		$this->userSession = $parameters['userSession'];
		$this->tempManager = $parameters['tempManager'];
	}

	/**
	 * Asynchronously scan data that are written to the file
	 *
	 * @param string $path
	 * @param string $mode
	 *
	 * @return resource | bool
	 */
	public function fopen($path, $mode) {
		$stream = $this->storage->fopen($path, $mode);
		$extension = \pathinfo($path, PATHINFO_EXTENSION);
		// Drop a .part and .ocTransferId extensions
		$cleanPath = \substr($path, 0, \strrpos($path, '.'));
		$cleanPath = \substr($cleanPath, 0, \strrpos($cleanPath, '.'));
		if (\is_resource($stream)
			&& $this->isWritingMode($mode)
			&& $this->handler->isOffice($cleanPath)
			&& $this->userSession->getUser() === null
			&& $extension === 'part'
		) {
			try {
				$tempFile = $this->tempManager->getTemporaryFile();
				$tempFileHandle = \fopen($tempFile, 'a');
				return CallBackWrapper::wrap(
					$stream,
					null,
					function ($data) use ($tempFileHandle) {
						\fwrite($tempFileHandle, $data);
					},
					function () use ($tempFileHandle, $tempFile, $cleanPath) {
						if (\is_resource($tempFileHandle)) {
							\fclose($tempFileHandle);
						}
						$this->handler->classifyByPath($tempFile, $cleanPath);
					}
				);
			} catch (\Exception $e) {
			}
		}
		return $stream;
	}

	/**
	 * Checks whether passed mode is suitable for writing
	 *
	 * @param string $mode
	 *
	 * @return bool
	 */
	private function isWritingMode($mode) {
		// Strip unessential binary/text flags
		$cleanMode = \str_replace(
			['t', 'b'],
			['', ''],
			$mode
		);
		return \in_array($cleanMode, $this->writingModes);
	}
}
