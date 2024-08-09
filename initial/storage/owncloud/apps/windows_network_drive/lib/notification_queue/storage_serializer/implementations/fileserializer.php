<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright Copyright (c) 2017, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib\notification_queue\storage_serializer\implementations;

use OCA\windows_network_drive\lib\notification_queue\storage_serializer\exceptions\DataSourceNotAvailableException;
use OCA\windows_network_drive\lib\notification_queue\storage_serializer\exceptions\WriteException;
use OCA\windows_network_drive\lib\notification_queue\storage_serializer\exceptions\ReadException;
use OCA\windows_network_drive\lib\Utils;
use OCA\windows_network_drive\lib\WND;

class FileSerializer implements ISerializer {
	protected $file;
	/**
	 * @param string $file the local file that will be read or write
	 */
	public function __construct($params) {
		if (isset($params['file'])) {
			$this->file = $params['file'];
		} else {
			throw new \InvalidArgumentException('missing required params for file serializer');
		}
	}

	public function writeStorages(array $storages, array $context) {
		$this->writeSanityChecks();  // this might throw a DataSourceNotAvailableException

		// we should be able to write the storage safely now
		$fileResource = \fopen($this->file, 'c');
		// change the permissions so only the current user can read and write the file
		\chmod($this->file, 0600);

		try {
			$lock = \flock($fileResource, LOCK_EX | LOCK_NB);
			if ($lock) {
				\ftruncate($fileResource, 0);
				$this->realWrite($storages, $context, $fileResource);
			} else {
				throw new DataSourceNotAvailableException("cannot get the lock for the target file for writing");
			}
		} finally {
			\fflush($fileResource);
			if ($lock) {
				\flock($fileResource, LOCK_UN);
			}
			// always close the file
			\fclose($fileResource);
		}
	}

	/**
	 * Check the status of the target file in order to write in it
	 * @throws DataSourceNotAvailableException if the target file can't be read or write
	 * @return null
	 */
	protected function writeSanityChecks() {
		$parentDir = \dirname($this->file);
		// check if parent directory is writeable
		if (!\is_writeable($parentDir)) {
			throw new DataSourceNotAvailableException("$parentDir isn't writeable");
		}

		// check if the target file already exists
		if (\file_exists($this->file)) {
			if (\is_dir($this->file)) {
				throw new DataSourceNotAvailableException("a directory already exists with the same name");
			} else {
				// there is already a file with the same name, check if we can overwrite it
				if (!\is_writeable($this->file)) {
					throw new DataSourceNotAvailableException("a file already exists and it can't be overwritten");
				}
			}
		}
	}

	/**
	 * Effectively write the storage list in the file
	 * @param WND[] $storages the list of storages
	 * @param array $context the context that will be written in the header. This will be used as a
	 * safety mechanism when the file is read later: if the context is different it will throw
	 * an exception
	 * @param resource $fileResource an opened file resource ready to write the storages. The resource
	 * won't be closed inside this function!
	 * @throws WriteException if there are errors writing in the file
	 */
	protected function realWrite(array $storages, array $context, $fileResource) {
		$header = $this->generateHeader($context);
		// write the header to easily identify the contents
		$bytes = \fwrite($fileResource, $header . "\n");
		if ($bytes !== \strlen($header) + 1) {
			// count also the new line
			throw new WriteException("error writing data in the file: " . __CLASS__);
		}
		foreach ($storages as $storage) {
			$data = [
				'host' => $storage->getHost(),
				'share' => $storage->getShareName(),
				'root' => $storage->getRoot(),
				'domain' => $storage->getDomain(),
				'user' => $storage->getUser(),
				'password' => Utils::encryptPassword($storage->getPassword()),
				'permissionManager' => $storage->getPermissionManagerName(),
			];
			$jsonData = \json_encode($data);
			$bytes = \fwrite($fileResource, $jsonData . "\n");
			if ($bytes !== \strlen($jsonData) + 1) {
				// count also the new line
				throw new WriteException("error writing data in the file: " . $jsonData);
			}
		}
	}

	public function readStorages(array $context) {
		$this->readSanityChecks();  // this might throw DataSourceNotAvailableException

		$fileResource = \fopen($this->file, 'r');
		try {
			$lock = \flock($fileResource, LOCK_SH | LOCK_NB);
			if ($lock) {
				$storageList = $this->realRead($context, $fileResource);
			} else {
				throw new DataSourceNotAvailableException("cannot get the lock for the target file for reading");
			}
		} finally {
			if ($lock) {
				\flock($fileResource, LOCK_UN);
			}
			// always close the file
			\fclose($fileResource);
		}

		// return the storage list if no exception has been thrown
		return $storageList;
	}

	protected function readSanityChecks() {
		if (\file_exists($this->file)) {
			if (\is_dir($this->file)) {
				throw new DataSourceNotAvailableException("target file is a directory");
			} else {
				// there is already a file with the same name, check if we can overwrite it
				if (!\is_readable($this->file)) {
					throw new DataSourceNotAvailableException("target file isn't readable");
				}
			}
		} else {
			throw new DataSourceNotAvailableException("target file is missing");
		}
	}

	protected function realRead($context, $fileResource) {
		$expectedHeader = $this->generateHeader($context);
		// read first line for the header
		$header = \trim(\fgets($fileResource));
		if ($header !== $expectedHeader) {
			throw new ReadException("found $header as header which mismatch the expected header");
		}

		$storageList = [];

		try {
			do {
				$line = \trim(\fgets($fileResource));
				if ($line === '' || \substr($line, 0, 2) === '//') {
					// ignore empty lines and lines starting with //
					continue;
				}

				$data = \json_decode($line, true);
				// decrypt password
				$data['password'] = Utils::decryptPassword($data['password']);
				$wnd = new WND($data);
				$storageList[$wnd->getId()] = $wnd;
			} while (!\feof($fileResource));
		} catch (\Exception $ex) {
			throw new ReadException("error reading the storages", 0, $ex);
		}
		return $storageList;
	}

	public function clearStorages(array $context) {
		// $context will be ignored. The file will be removed anyway
		if (\file_exists($this->file)) {
			$result = \unlink($this->file);
			if (!$result) {
				throw new DataSourceNotAvailableException("can't delete the target file");
			}
		}
	}

	/**
	 * Generate the header for the file. The header is intended to provided a simple verification
	 * mechanism to know what we're trying to read or write.
	 * There is no particular reason to use sha1 other than provide a kind of id
	 */
	protected function generateHeader(array $context) {
		return __CLASS__ . ' ' . \sha1(\json_encode($context));
	}
}
