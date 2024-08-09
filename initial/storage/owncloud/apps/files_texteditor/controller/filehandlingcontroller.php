<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\Files_Texteditor\Controller;

use OC\HintException;
use OC\User\NoUserException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\ForbiddenException;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Lock\LockedException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use Sabre\DAV\Exception\NotFound;

class FileHandlingController extends Controller {

	/** @var IL10N */
	private $l;

	/** @var ILogger */
	private $logger;

	/** @var IManager */
	private $shareManager;

	/** @var IUserSession */
	private $userSession;

	/** @var IRootFolder */
	private $root;

	/**
	 * @NoAdminRequired
	 *
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param ILogger $logger
	 * @param IManager $shareManager
	 * @param IUserSession $userSession
	 * @param IRootFolder $root
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		IL10N $l10n,
		ILogger $logger,
		IManager $shareManager,
		IUserSession $userSession,
		IRootFolder $root
	) {
		parent::__construct($AppName, $request);
		$this->l = $l10n;
		$this->logger = $logger;
		$this->shareManager = $shareManager;
		$this->userSession = $userSession;
		$this->root = $root;
	}

	/**
	 * load text file
	 *
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $dir
	 * @param string $filename
	 * @return DataResponse
	 */
	public function load($dir, $filename) {
		try {
			if (!empty($filename)) {
				$path = $dir . '/' . $filename;
				try {
					$node = $this->getNode($path);
				} catch (ShareNotFound $e) {
					return new DataResponse(
						['message' => $this->l->t('Invalid share token')],
						Http::STATUS_BAD_REQUEST
					);
				} catch (NoUserException $e) {
					return new DataResponse(
						['message' => $this->l->t('No user found')],
						Http::STATUS_BAD_REQUEST
					);
				}

				// default of 4MB
				$maxSize = 4194304;
				if ($node->getSize() > $maxSize) {
					return new DataResponse(['message' => (string)$this->l->t('This file is too big to be opened. Please download the file instead.')], Http::STATUS_BAD_REQUEST);
				}

				/** @var mixed $fileContents */
				$fileContents = $node->getContent();

				if ($fileContents !== false) {
					$permissions = $this->getPermissions($node);
					$writable = ($permissions & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE;
					$mime = $node->getMimeType();
					$mTime = $node->getMTime();
					$encoding = \mb_detect_encoding($fileContents . "a", "UTF-8, GB2312, GBK ,BIG5, WINDOWS-1252, SJIS-win, EUC-JP, ISO-8859-15, ISO-8859-1, ASCII", true);
					if ($encoding == "") {
						// set default encoding if it couldn't be detected
						$encoding = 'ISO-8859-15';
					}
					$fileContents = \iconv($encoding, "UTF-8", $fileContents);
					return new DataResponse(
						[
							'filecontents' => $fileContents,
							'writeable' => $writable,
							'mime' => $mime,
							'mtime' => $mTime
						],
						Http::STATUS_OK
					);
				} else {
					return new DataResponse(['message' => (string)$this->l->t('Cannot read the file.')], Http::STATUS_BAD_REQUEST);
				}
			} else {
				return new DataResponse(['message' => (string)$this->l->t('Invalid file path supplied.')], Http::STATUS_BAD_REQUEST);
			}
		} catch (LockedException $e) {
			$message = (string) $this->l->t('The file is locked.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		} catch (ForbiddenException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		} catch (HintException $e) {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			$message = (string)$this->l->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * save text file
	 *
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $path
	 * @param string $filecontents
	 * @param integer $mtime
	 * @return DataResponse
	 */
	public function save($path, $filecontents, $mtime) {
		try {
			if ($path !== '' && (\is_integer($mtime) && $mtime > 0)) {
				try {
					$node = $this->getNode($path);
				} catch (ShareNotFound $e) {
					return new DataResponse(
						['message' => $this->l->t('Invalid share token')],
						Http::STATUS_BAD_REQUEST
					);
				} catch (NoUserException $e) {
					return new DataResponse(
						['message' => $this->l->t('No user found')],
						Http::STATUS_BAD_REQUEST
					);
				}

				$permissions = $this->getPermissions($node);

				// Get file mtime
				$filemtime = $node->getMTime();

				if ($mtime !== $filemtime) {
					// Then the file has changed since opening
					$this->logger->error(
						'File: ' . $path . ' modified since opening.',
						['app' => 'files_texteditor']
					);
					return new DataResponse(
						['message' => $this->l->t('Cannot save file as it has been modified since opening')],
						Http::STATUS_BAD_REQUEST
					);
				} else {
					// File same as when opened, save file
					if (($permissions & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE) {
						$filecontents = \iconv(\mb_detect_encoding($filecontents), "UTF-8", $filecontents);
						try {
							$node->putContent($filecontents);
						} catch (LockedException $e) {
							$message = (string) $this->l->t('The file is locked.');
							return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
						} catch (ForbiddenException $e) {
							return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
						}
						// Clear statcache
						\clearstatcache();
						// Get new mtime
						$newmtime = $node->getMTime();
						$newsize = $node->getSize();
						return new DataResponse(['mtime' => $newmtime, 'size' => $newsize], Http::STATUS_OK);
					} else {
						// Not writeable!
						$this->logger->error(
							'User does not have permission to write to file: ' . $path,
							['app' => 'files_texteditor']
						);
						return new DataResponse(
							[ 'message' => $this->l->t('Insufficient permissions')],
							Http::STATUS_BAD_REQUEST
						);
					}
				}
			} elseif ($path === '') {
				$this->logger->error('No file path supplied');
				return new DataResponse(['message' => $this->l->t('File path not supplied')], Http::STATUS_BAD_REQUEST);
			} else {
				$this->logger->error('No file mtime supplied', ['app' => 'files_texteditor']);
				return new DataResponse(['message' => $this->l->t('File mtime not supplied')], Http::STATUS_BAD_REQUEST);
			}
		} catch (HintException $e) {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			$message = (string)$this->l->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}
	}

	private function getNode(string $path): File {
		$sharingToken = $this->request->getParam('sharingToken');

		if ($sharingToken) {
			$share = $this->shareManager->getShareByToken($sharingToken);
			$node = $share->getNode();
			if (!($node instanceof File)) {
				$node = $node->get($path);
			}
		} else {
			$user = $this->userSession->getUser();
			if (!$user) {
				throw new NoUserException();
			}

			$node = $this->root->get('/' . $user->getUID() . '/files' . $path);
		}

		if (!($node instanceof File)) {
			throw new NotFound();
		}

		return $node;
	}

	private function getPermissions($node): int {
		$sharingToken = $this->request->getParam('sharingToken');

		if ($sharingToken) {
			$share = $this->shareManager->getShareByToken($sharingToken);
			return $share->getPermissions();
		}

		return $node->getPermissions();
	}
}
