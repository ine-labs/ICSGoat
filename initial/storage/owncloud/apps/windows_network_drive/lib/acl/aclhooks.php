<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib\acl;

use OCP\ILogger;
use OCP\Files\StorageNotAvailableException;
use OCA\windows_network_drive\lib\acl\groupmembership\OCLDAPMembership;
use OCA\windows_network_drive\lib\notification_queue\StorageFactory;
use OCA\windows_network_drive\lib\Utils;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Hooks to listen events from the smb_acl app such as "smb_acl.acl.afterset"
 * Note: This class relies on specific setup to work properly along with the smb_acl app
 * Requires:
 * - SMB group membership can be fetched from the ownCloud's group manager => it has to be connected
 *     to the same LDAP / AD and the ownCloud's username must match the uid / sAMAccountName of the SMB user
 * - Only WND mount points using "login credentials, saved in DB" will be handled. This is because we don't need
 *     to create the storage to get the account that will be used to access to WND
 */
class ACLHooks {
	/** @var StorageFactory */
	private $storageFactory;
	/** @var OCLDAPMembership */
	private $groupMembership;
	/** @var ACLOperator */
	private $aclOperator;
	/** @var ILogger */
	private $logger;

	/**
	 * @var \OCP\Files\Storage\IStorage[]
	 * cache holding the storage for the downwards propagation
	 */
	private $downwardPropagationStorages = [];
	/**
	 * @var array
	 * This will hold an array such as $cacheStopDownwardPropagationOnPaths[$storageId][$path]
	 * Stop the propagation on those paths for those storage ids. The intention is
	 * to prevent unneeded hits to the DB if a folder has been removed from the cache or
	 * if the target folder doesn't exists in the cache. Events are expected to keep coming,
	 * but we can ignore those
	 */
	private $cacheStopDownwardPropagationOnPaths = [];

	/**
	 * @param StorageFactory $storageFactory a storage factory to create the required storages to be manipulated
	 * @param OCLDAPMembership $groupMembership
	 * @param ACLOperator $aclOperator
	 * @param ILogger $logger
	 */
	public function __construct(
		StorageFactory $storageFactory,
		OCLDAPMembership $groupMembership,
		ACLOperator $aclOperator,
		ILogger $logger
	) {
		$this->storageFactory = $storageFactory;
		$this->groupMembership = $groupMembership;
		$this->aclOperator = $aclOperator;
		$this->logger = $logger;
	}

	/**
	 * Listen to the smb_acl.acl.afterset event in order to update the WND filecache on the affected storages.
	 * It's expected that only WND storages with "login credentials, saved in DB" auth will be updated.
	 * @param GenericEvent $event a 'smb_acl.acl.afterset' event triggered by the smb_acl app
	 */
	public function aclSet(GenericEvent $event) {
		$eventArgs = $event->getArguments();
		$this->logger->debug("received ACL set event: " . \json_encode($eventArgs), ['app' => 'wnd']);

		$aceChanges = $this->aclOperator->getACEchanges($eventArgs['oldDescriptor']['acl'], $eventArgs['descriptor']['acl']);
		$aceChanges = \array_merge($aceChanges['added'], $aceChanges['removed']);
		$this->logger->debug("received ACL set event; ACE changes: " . \json_encode($aceChanges), ['app' => 'wnd']);

		$accountMap = $this->aclOperator->getAccountMap($aceChanges, $this->groupMembership);

		$userList = [];
		foreach ($aceChanges as $aceChange) {
			$trustee = $aceChange['trustee'];
			foreach ($accountMap['trusteeToUser'][$trustee] as $user) {
				$userList[$user] = $user;
			}
		}

		if (empty($userList)) {
			$this->logger->debug("received ACL set event; no target accounts, aborting", ['app' => 'wnd']);
			return;
		}

		$this->logger->debug("received ACL set event; target accounts: " . \implode(',', $userList), ['app' => 'wnd']);
		// setupFS is required for now due to authentication problems. Although accessing with
		// basic auth seems to work fine and sets the FS beforehand, this doesn't happen with
		// oAuth. The setupFS doesn't seems to be called with oAuth, and causes the WND backend
		// not to be registered properly, so no WND storage will be created and updated even
		// though users have access to them
		\OC_Util::setupFS();

		// NOTE: We'll only fetch storages with 'password::logincredentials' or 'password::hardcodedconfigcredentials' authentication
		$storagesToBeUpdated = $this->storageFactory->fetchStoragesForServerAndForUsers($eventArgs['smbHost'], $eventArgs['smbShare'], $userList);
		$this->logger->debug("found " . \count($storagesToBeUpdated) . " storages to be updated");

		$canReadCache = [];
		foreach ($storagesToBeUpdated as $storageId => $storage) {
			if (!Utils::isInsideFolder("{$eventArgs['smbPath']}", $storage->getRoot())) {
				$this->logger->debug("{$eventArgs['smbPath']} not inside " . $storage->getRoot());
				continue;
			}
			// getRelativePath will never return null because the path is inside the folder
			$relativePath = Utils::getRelativePath("{$eventArgs['smbPath']}", $storage->getRoot());
			$targetPath = Utils::innermostPathInCache($storage->getCache(), $relativePath);

			$storageTrustee = Utils::conditionalDomainPlusUsername($storage->getDomain(), $storage->getUser());
			try {
				if ($targetPath === $relativePath) {
					if (!isset($canReadCache[$storageTrustee])) {
						$storageTrusteePermissions = $this->aclOperator->evaluatePermissionsForTrustee($storageTrustee, $eventArgs['descriptor']['acl'], $this->groupMembership);
						$canReadCache[$storageTrustee] = $storageTrusteePermissions['R'] === 'allowed';
					}
					// the relativePath is in the storage cache -> update the information accordingly
					if ($canReadCache[$storageTrustee]) {
						$storage->getUpdater()->update($relativePath);
						$this->logger->debug("updated $relativePath inside $storageId", ['app' => 'wnd']);
					} else {
						$storage->getUpdater()->remove($relativePath);
						$this->logger->debug("removed $relativePath inside $storageId", ['app' => 'wnd']);
					}
				} else {
					// the relativePath isn't in the storage cache -> update the closest parent present in the cache
					$storage->getUpdater()->update($targetPath);
					$this->logger->debug("$relativePath not found inside $storageId. Updating $targetPath instead", ['app' => 'wnd']);
				}
			} catch (StorageNotAvailableException $e) {
				$this->logger->warning("cannot update $storageId: " . $e->getMessage(), ['app' => 'wnd']);
			}
		}
	}

	public function aclDownwardsPropagationStarts(GenericEvent $event) {
		$eventArgs = $event->getArguments();
		$this->logger->debug("starting ACL downward propagation event: " . \json_encode($eventArgs), ['app' => 'wnd']);

		$aceChanges = \array_merge($eventArgs['addedAces'], $eventArgs['removedAces']);
		$this->logger->debug("received ACL downward propagation event; ACE changes: " . \json_encode($aceChanges), ['app' => 'wnd']);

		$accountMap = $this->aclOperator->getAccountMap($aceChanges, $this->groupMembership);

		$userList = [];
		foreach ($aceChanges as $aceChange) {
			$trustee = $aceChange['trustee'];
			foreach ($accountMap['trusteeToUser'][$trustee] as $user) {
				$userList[$user] = $user;
			}
		}

		if (empty($userList)) {
			$this->logger->debug("received ACL downward propagation event; no target accounts, aborting", ['app' => 'wnd']);
			// reset caches in order to prevent further events being processed with wrong information
			$this->downwardPropagationStorages = [];
			$this->cacheStopDownwardPropagationOnPaths = [];
			return;
		}

		$this->logger->debug("received ACL downward propagation event; target accounts: " . \implode(',', $userList), ['app' => 'wnd']);
		// setupFS is required for now due to authentication problems. Although accessing with
		// basic auth seems to work fine and sets the FS beforehand, this doesn't happen with
		// oAuth. The setupFS doesn't seems to be called with oAuth, and causes the WND backend
		// not to be registered properly, so no WND storage will be created and updated even
		// though users have access to them
		\OC_Util::setupFS();

		// NOTE: We'll only fetch storages with 'password::logincredentials' or 'password::hardcodedconfigcredentials' authentication
		$this->downwardPropagationStorages = $this->storageFactory->fetchStoragesForServerAndForUsers($eventArgs['smbHost'], $eventArgs['smbShare'], $userList);
		$this->cacheStopDownwardPropagationOnPaths = [];  // initialize this cache too
		$this->logger->debug("found " . \count($this->downwardPropagationStorages) . " storages to be updated");
	}

	public function aclDownwardsPropagationSet(GenericEvent $event) {
		$eventArgs = $event->getArguments();
		$canReadCache = [];
		foreach ($this->downwardPropagationStorages as $storageId => $storage) {
			if (!Utils::isInsideFolder("{$eventArgs['smbPath']}", $storage->getRoot())) {
				$this->logger->debug("{$eventArgs['smbPath']} not inside " . $storage->getRoot());
				continue;
			}
			// getRelativePath will never return null because the path is inside the folder
			$relativePath = Utils::getRelativePath("{$eventArgs['smbPath']}", $storage->getRoot());

			if ($this->checkAbortPropagation($storageId, $relativePath)) {
				// no need to update this path -> no present in cache
				continue;
			}

			$targetPath = Utils::innermostPathInCache($storage->getCache(), $relativePath);

			$storageTrustee = Utils::conditionalDomainPlusUsername($storage->getDomain(), $storage->getUser());
			try {
				if ($targetPath === $relativePath) {
					if (!isset($canReadCache[$storageTrustee])) {
						$storageTrusteePermissions = $this->aclOperator->evaluatePermissionsForTrustee($storageTrustee, $eventArgs['entryDescriptor']['acl'], $this->groupMembership);
						$canReadCache[$storageTrustee] = $storageTrusteePermissions['R'] === 'allowed';
					}
					// the relativePath is in the storage cache -> update the information accordingly
					if ($canReadCache[$storageTrustee]) {
						$storage->getUpdater()->update($relativePath);
						$this->logger->debug("updated $relativePath inside $storageId", ['app' => 'wnd']);
					} else {
						$storage->getUpdater()->remove($relativePath);
						$this->stopDownwardPropagationOn($storageId, $relativePath);
						$this->logger->debug("removed $relativePath inside $storageId", ['app' => 'wnd']);
					}
				} else {
					// the relativePath isn't in the storage cache -> update the closest parent present in the cache
					$storage->getUpdater()->update($targetPath);
					// blacklist the "relativePath" instead of the parent to allow updating "relativePath"'s siblings if needed
					$this->stopDownwardPropagationOn($storageId, $relativePath);
					$this->logger->debug("$relativePath not found inside $storageId. Updating $targetPath instead", ['app' => 'wnd']);
				}
			} catch (StorageNotAvailableException $e) {
				// if the storage isn't available it's unlikely that we can update that storage further down
				$this->stopDownwardPropagationOn($storageId, $relativePath);
				$this->logger->warning("cannot update $storageId: " . $e->getMessage(), ['app' => 'wnd']);
			}
		}
	}

	public function aclDownwardsPropagationEnds(GenericEvent $event) {
		$eventArgs = $event->getArguments();
		$this->logger->debug("finishing ACL downward propagation event: " . \json_encode($eventArgs), ['app' => 'wnd']);
		// free some memory.
		$this->downwardPropagationStorages = [];
		$this->cacheStopDownwardPropagationOnPaths = [];
	}

	private function stopDownwardPropagationOn($storageId, $path) {
		if (!isset($this->cacheStopDownwardPropagationOnPaths[$storageId])) {
			$this->cacheStopDownwardPropagationOnPaths[$storageId] = [];
		}
		$this->cacheStopDownwardPropagationOnPaths[$storageId][] = $path;
	}

	private function checkAbortPropagation($storageId, $path) {
		if (!isset($this->cacheStopDownwardPropagationOnPaths[$storageId])) {
			return false;
		}

		$pathList = $this->cacheStopDownwardPropagationOnPaths[$storageId];
		foreach ($pathList as $stoppedPath) {
			if ($stoppedPath === '' ||
				$path === $stoppedPath ||
				\substr($path, 0, \strlen($stoppedPath) + 1) === "$stoppedPath/"
			) {
				return true;
			}
		}
		return false;
	}
}
