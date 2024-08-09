<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib\storage;

use OC\Files\Cache\Wrapper\CacheWrapper;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\ICache as PublicCache;
use OCP\IConfig;
use OCA\windows_network_drive\lib\WND;

/**
 * Class WNDCacheWrapper
 *
 * Reads the cache information from the underlying cache implementation and
 * verifies if the current user has access by calling stat on the storage.
 *
 * In case performance becomes a problem we might want to include a stat cache
 * per user on e.g. redis or so.
 *
 * @package OCA\Files_External\Lib\Cache
 */
class WNDCacheWrapper extends CacheWrapper {
	/** @var int */
	private $cacheTtl = null;
	/** @var WND */
	private $wnd;
	/** @var PublicCache */
	private $publicCache;
	/** @var IConfig */
	private $config;

	public function __construct(ICache $cache, WND $wnd, PublicCache $publicCache, IConfig $config) {
		parent::__construct($cache);
		$this->wnd = $wnd;
		$this->publicCache = $publicCache;
		$this->config = $config;
	}

	public function get($file) {
		// canAccess will return the cacheEntry, no need to hit the DB twice,
		// if the file isn't accessible, false will be returned instead
		return $this->canAccess($file);
	}

	/**
	 * getFolderContents method in the CacheWrapper will call this method,
	 * so filter the entries just here.
	 */
	public function getFolderContentsById($fileId) {
		$children = parent::getFolderContentsById($fileId);
		return \array_filter($children, function (ICacheEntry $c) {
			return $this->canAccess($c->getPath());
		});
	}

	private function getPublicCacheKey($file) {
		$storageId = $this->wnd->getId();
		return "/{$storageId}{$file}";
	}

	/**
	 * It will return the cacheEntry of the file if it can access, false otherwise
	 * @param string $file
	 * @return ICacheEntry|false
	 */
	private function canAccess($file) {
		try {
			$fileInfo = parent::get($file);
			if ($fileInfo === false) {
				return false;
			}

			$cacheTtl = $this->getCacheTtl();
			if ($cacheTtl > 0) {
				// we shouldn't cache the info indefinitely, so we don't want a 0 value
				$file = $fileInfo->getPath();
				$cacheKey = $this->getPublicCacheKey($file);
				$cachedInfo = $this->publicCache->get($cacheKey);

				$fileEtag = $fileInfo->getEtag();
				if ($cachedInfo && $cachedInfo['etag'] === $fileEtag) {
					if ($cachedInfo['access']) {
						// if the current etag matches the one cached, the file is accessible
						// otherwise, there have been changes and we need to check again
						return $fileInfo;
					} else {
						return false;
					}
				}
			}

			// we expect errors if the user doesn't have permissions, so disable the log
			$isDir = $fileInfo->getMimeType() === 'httpd/unix-directory';
			if ($isDir) {
				$fdesc = $this->wnd->opendirWithOpts($file, ['logger' => null]);
			} else {
				$fdesc = $this->wnd->fopenWithOpts($file, 'rb', ['logger' => null]);
			}

			$isOpened = $fdesc !== false;
			if ($cacheTtl > 0) {
				$this->publicCache->set($cacheKey, ['etag' => $fileEtag, 'access' => $isOpened], $cacheTtl);
			}

			if ($isOpened) {
				return $fileInfo;
			}
			return false;
		} catch (\Exception $ex) {
			\OC::$server->getLogger()->logException($ex);
			return false;
		} finally {
			// this will be executed after the return
			if (isset($fdesc) && \is_resource($fdesc)) {
				if ($isDir) {
					\closedir($fdesc);
				} else {
					\fclose($fdesc);
				}
			}
		}
	}

	/**
	 * Get the configured ttl. Default value is 30 minutes. Invalid value will set the ttl
	 * to -1 to disable caching
	 */
	private function getCacheTtl() {
		if ($this->cacheTtl === null) {
			$cacheTtl = $this->config->getSystemValue('wnd2.cachewrapper.ttl', 30*60);
			if (\is_int($cacheTtl)) {
				$this->cacheTtl = $cacheTtl;
			} else {
				$this->cacheTtl = -1;
			}
		}
		return $this->cacheTtl;
	}
}
