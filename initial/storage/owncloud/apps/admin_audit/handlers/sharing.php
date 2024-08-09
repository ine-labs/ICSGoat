<?php
/**
 * ownCloud Admin_Audit
 *
 * @author Frank Karlitscheck <frank@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Gapczynski
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright (C) 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;

use OCP\Constants;
use OCP\Share;
use Symfony\Component\EventDispatcher\GenericEvent;

class Sharing extends Base {
	public static function unshareInfo($arguments) {
		self::logUnshareInfo("{actor} unshare {targetmount} from {shareType} target {targetUser}", $arguments);
	}

	public static function federatedShareArrivedFromRemote($arguments) {
		self::logFederatedShareArriveFromRemote('{actor} received federated share {name} from {shareType} target {targetuser}', $arguments);
	}

	public static function decline_remote_shared($arguments) {
		self::logDeclineShareHook('{actor} declined share {itemType} from {shareType} target {targetuser}', $arguments);
	}

	public static function accept_remote_shared($arguments) {
		self::logAcceptShareHook('{actor} accepted share {itemType} from {shareType} target {targetuser}', $arguments);
	}

	protected static function logUnshareInfo($message, $arguments) {
		self::getLogger()->log($message, [
			'targetUser' => $arguments['user'],
			'targetmount' => $arguments['targetmount'],
			'shareType' => 'remote',
		], [
			'action' => 'federated_share_unshared',
			'targetUser' => $arguments['user'],
			'targetmount' => $arguments['targetmount'],
			'shareType' => 'remote',
		]);
	}
	protected static function logFederatedShareArriveFromRemote($message, $arguments) {
		self::getLogger()->log($message, [
			'name' => $arguments['name'],
			'targetuser' => $arguments['targetuser'],
			'shareType' => 'remote',
		], [
			'action' => 'federated_share_received',
			'name' => $arguments['name'],
			'targetuser' => $arguments['targetuser'],
			'shareType' => 'remote',
		]);
	}

	protected static function logDeclineShareHook($message, $arguments) {
		self::getLogger()->log($message, [
			'itemType' => $arguments['sharedItem'],
			'targetuser' => $arguments['shareAcceptedFrom'] . "@" . $arguments['remoteUrl'],
			'shareType' => 'remote',
		], [
			'action' => 'federated_share_declined',
			'itemType' => $arguments['sharedItem'],
			'targetuser' => $arguments['shareAcceptedFrom'] . "@" . $arguments['remoteUrl'],
			'shareType' => 'remote',
		]);
	}

	protected static function logAcceptShareHook($message, $arguments) {
		self::getLogger()->log($message, [
			'itemType' => $arguments['sharedItem'],
			'targetuser' => $arguments['shareAcceptedFrom'] . "@" . $arguments['remoteUrl'],
			'shareType' => 'remote',
		], [
			'action' => 'federated_share_accepted',
			'itemType' => $arguments['sharedItem'],
			'targetuser' => $arguments['shareAcceptedFrom'] . "@" . $arguments['remoteUrl'],
			'shareType' => 'remote',
		]);
	}

	public static function post_shared($arguments) {
		self::logShareHook('{actor} shared {itemType} {path} with {target}, permissions: {permissions}, owner: {owner}', $arguments, 'file_shared');
	}

	protected static function logShareHook($message, array $arguments, $action = null) {
		if ($arguments['itemType'] !== 'file' && $arguments['itemType'] !== 'folder') {
			return;
		}

		$nodes = \OC::$server->getUserFolder($arguments['uidOwner'])->getById($arguments['itemSource'], true);

		if (empty($nodes)) {
			return;
		}
		/** @var \OCP\Files\Node $node */
		$node = $nodes[0];

		$target = self::buildTarget($arguments);

		$extraFields = [
			'permissions' => self::getPermissionsArray($arguments['permissions']),
			'action' => $action,
			'itemType' => $arguments['itemType'],
			'shareType' => self::getShareTypeString($arguments['shareType']),
			'path' => $node->getPath(),
			'shareOwner' => $arguments['uidOwner'],
			'owner' => $node->getOwner()->getUID(),
			'fileId' => (string)$node->getId(),
			'shareId' => (string)$arguments['id']
		];

		if (isset($arguments['oldpermissions'])) {
			$extraFields['oldPermissions'] = self::getPermissionsArray($arguments['oldpermissions']);
		}

		if ($arguments['shareType'] === Share::SHARE_TYPE_LINK) {
			$extraFields['sharePass'] = $arguments['passwordEnabled'];
			if (isset($arguments['token'])) {
				$extraFields['shareToken'] = $arguments['token'];
			}
		} else {
			$extraFields['shareWith'] = $arguments['shareWith'];
		}

		if (isset($arguments['expiration']) && $arguments['expiration'] instanceof \DateTime) {
			$extraFields['expirationDate'] = $arguments['expiration']->format('Y-m-d');
		}

		self::getLogger()->log("$message", [
			'itemType' => $arguments['itemType'],
			'path' => $node->getPath(),
			'target' => $target,
			'permissions' => self::getPermissionsArray($arguments['permissions']),
			'oldPermissions' => isset($arguments['oldpermissions']) ? self::getPermissionsArray($arguments['oldpermissions']) : '',
			'owner' => $node->getOwner()->getUID(),
		], $extraFields);
	}

	public static function post_unshare($arguments) {
		if ($arguments['itemType'] !== 'file' && $arguments['itemType'] !== 'folder') {
			return;
		}

		$nodes = \OC::$server->getUserFolder($arguments['uidOwner'])->getById($arguments['itemSource'], true);

		if (empty($nodes)) {
			return;
		}
		/** @var \OCP\Files\Node $node */
		$node = $nodes[0];

		$fields = [
			'action' => 'file_unshared',
			'itemType' => $arguments['itemType'],
			'shareType' => self::getShareTypeString($arguments['shareType']),
			'path' => $node->getPath(),
			'owner' => $node->getOwner()->getUID(),
			'fileId' => (string)$node->getId(),
			'shareId' => (string)$arguments['id'],
		];

		if ($arguments['shareType'] === Share::SHARE_TYPE_LINK) {
			$fields['action'] = 'public_link_removed';
			self::getLogger()->log('{actor} removed the share link for {itemType} {path}, owner: {owner}', $fields, $fields);
			return;
		}

		$parameters = \array_merge($fields, ['target' => self::buildTarget($arguments)]);
		$fields['shareWith'] = $arguments['shareWith'];

		self::getLogger()->log('{actor} unshared {itemType} {path} with {target}, owner: {owner}', $parameters, $fields);
	}

	public static function shareDecline(GenericEvent $event) {
		$share = $event->getArgument('share');
		$node = $share->getNode();

		self::getLogger()->log('{actor} declined the share for {itemType} {path}, owner: {owner}', [
			'itemType' => $share->getNodeType(),
			'path' => $node->getPath(),
			'owner' => $node->getOwner()->getUID(),
		], [
			'action' => 'share_declined',
			'itemType' => $share->getNodeType(),
			'path' => $node->getPath(),
			'owner' => $node->getOwner()->getUID(),
			'fileId' => (string) $node->getId(),
			'shareId' => (string) $share->getId(),
			'shareType' => $share->getShareType(),
		]);
	}

	public static function shareAccept(GenericEvent $event) {
		$share = $event->getArgument('share');
		$node = $share->getNode();

		self::getLogger()->log('{actor} accepted the share for {itemType} {path}, owner: {owner}', [
			'itemType' => $share->getNodeType(),
			'path' => $node->getPath(),
			'owner' => $node->getOwner()->getUID(),
		], [
			'action' => 'share_accepted',
			'itemType' => $share->getNodeType(),
			'path' => $node->getPath(),
			'owner' => $node->getOwner()->getUID(),
			'fileId' => (string) $node->getId(),
			'shareId' => (string) $share->getId(),
			'shareType' => $share->getShareType(),
		]);
	}

	public static function unshareFromRecipient($arguments) {
		$fileId = "unknown";
		// prevent logging the delete
		$recipientPath = "/{$arguments['shareRecipient']}/files{$arguments['recipientPath']}";
		/** @var \OCP\Files\FileInfo $fileInfo */
		foreach (Files::$beforeDeleteFileInfo as $key => $fileInfo) {
			if (
				$recipientPath === $fileInfo->getPath()
				&& $arguments['shareOwner'] === $fileInfo->getOwner()->getUID()
			) {
				$fileId = (string)$fileInfo->getId();
				unset(Files::$beforeDeleteFileInfo[$key]);
				break;
			}
		}

		self::getLogger()->log('{actor} unshared {itemType} {path} from user {owner}, owner path: {ownerPath} [fid={fileId}]', [
			'itemType' => $arguments['nodeType'],
			'path' => $arguments['recipientPath'],
			'owner' => $arguments['shareOwner'],
			'ownerPath' => $arguments['ownerPath'],
			'fileId' => $fileId
		], [
			'action' => 'file_unshared',
			'itemType' => $arguments['nodeType'],
			'path' => $recipientPath,
			'owner' => $arguments['shareOwner'],
			'ownerPath' => $arguments['ownerPath'],
			'fileId' => $fileId,
		]);
	}

	public static function shareUpdate(GenericEvent $arguments) {
		/** @var Share\IShare $share */
		$share = $arguments->getArgument('shareobject');

		$node = $share->getNode();

		$owner = $node->getOwner()->getUID();

		$defaultFields = [
			'action' => 'share_updated',
			'itemType' => $node instanceof \OCP\Files\File ? 'file' : 'folder',
			'shareType' => self::getShareTypeString($share->getShareType()),
			'shareOwner' => $share->getSharedBy(),
			'permissions' => self::getPermissionsArray($share->getPermissions()),
			'path' => $node->getPath(),
			'fileId' => (string)$node->getId(),
			'shareId' => (string)$share->getId(),
			'owner' => $owner,
		];

		if ($share->getShareType() !== Share::SHARE_TYPE_LINK) {
			$defaultFields['shareWith'] = $share->getSharedWith();
		}

		if ($arguments->hasArgument('permissionupdate') && ($arguments->getArgument('permissionupdate') === true)) {
			$parameters = $fields = \array_merge($defaultFields, [
				'action' => 'share_permission_updated',
				'oldPermissions' => self::getPermissionsArray($arguments->getArgument('oldpermissions')),
			]);
			$parameters['target'] = self::buildTarget([
				'shareType' => $share->getShareType(),
				'shareWith' => $share->getSharedWith(),
				'shareToken' => $share->getToken(),
				'passwordEnabled' => !empty($share->getPassword())
			]);
			self::getLogger()->log('{actor} updated the permissions for {itemType} {path} for {target}, from old permissions: {oldPermissions} to new permissions: {permissions}, owner: {owner}', $parameters, $fields);
		}

		if ($arguments->hasArgument('passwordupdate') && ($arguments->getArgument('passwordupdate') === true)) {
			$fields = \array_merge($defaultFields, [
				'action' => 'share_password_updated',
				'shareToken' => $share->getToken(),
			]);
			if ($share->getPassword() === null) {
				$action = 'removed password from';
				$fields['sharePass'] = false;
			} else {
				$action = 'updated password on';
				$fields['sharePass'] = true;
			}
			self::getLogger()->log('{actor} ' . $action . ' link-shared {itemType} {path}, token: {shareToken}, owner: {owner}', $fields, $fields);
		}

		if ($arguments->hasArgument('expirationdateupdated') && ($arguments->getArgument('expirationdateupdated') === true)) {
			$date = $share->getExpirationDate();
			if ($date instanceof \DateTime) {
				$date = $date->format('Y-m-d');
			}
			$olddate = $arguments->getArgument('oldexpirationdate');
			if ($olddate instanceof \DateTime) {
				$olddate = $olddate->format('Y-m-d');
			}
			$fields = \array_merge($defaultFields, [
				'action' => 'share_expiration_date_updated',
				'expirationDate' => $date,
			]);
			if ($date === null) {
				self::getLogger()->log('{actor} removed expiration date from shared {itemType} {path}, owner: {owner}', $fields, $fields);
			} else {
				if ($olddate === null) {
					self::getLogger()->log('{actor} set expiration date {expirationDate} on shared {itemType} {path}, owner: {owner}', $fields, $fields);
				} else {
					$fields['oldExpirationDate'] = $olddate;
					self::getLogger()->log('{actor} update expiration from date {oldExpirationDate} to date {expirationDate} on shared {itemType} {path}, owner: {owner}', $fields, $fields);
				}
			}
		}

		if ($arguments->hasArgument('sharenameupdated') && ($arguments->getArgument('sharenameupdated') === true)) {
			$fields = \array_merge($defaultFields, [
				'action' => 'share_name_updated',
				'oldShareName' => $arguments->getArgument('oldname'),
				'shareName' => $share->getName(),
			]);
			self::getLogger()->log('{actor} public link name updated from "{oldShareName}" to "{shareName}" for shared {itemType} {path}, owner: {owner}', $fields, $fields);
		}
	}

	public static function share_link_access($arguments) {
		if ($arguments['itemType'] !== 'file' && $arguments['itemType'] !== 'folder' && $arguments['itemType'] !== '') {
			return;
		}

		if ($arguments['errorCode'] === 200) {
			$result = 'success.';
		} else {
			$result = 'error: ' . $arguments['errorMessage'];
		}

		$detailInfo = $path = $owner = $fileId = '';
		if (!empty($arguments['itemType'])) {
			$detailInfo = 'd {itemType} {path}, owner: {owner}, [fid={fileId}]';
			$nodes = \OC::$server->getUserFolder($arguments['uidOwner'])->getById($arguments['itemSource'], true);

			if (empty($nodes)) {
				return;
			}
			/** @var \OCP\Files\Node $node */
			$node = $nodes[0];

			$path = $node->getPath();
			$owner = $node->getOwner()->getUID();
			// Try harder to find owner
			if (!\is_string($owner)) {
				// Use the path instead - use helper
				$owner = self::getHelper()->getOwner($path);
			}
			$fileId = (string)$node->getId();
		}

		self::getLogger()->log('Share' . $detailInfo . ' with token {token} was accessed with ' . $result, [
			'token' => $arguments['token'],
			'itemType' => $arguments['itemType'],
			'path' => $path,
			'owner' => $owner,
			'fileId' => $fileId
		], [
			'action' => 'public_link_accessed',
			'success' => $arguments['errorCode'] === 200,
			'shareToken' => $arguments['token'],
			'itemType' => $arguments['itemType'],
			'path' => $path,
			'owner' => $owner,
			'fileId' => $fileId
		]);
	}

	public static function accessPublicLinkWebDAV($arguments) {
		self::getLogger()->log(
			"User accessed public link with method: {method} and token: {token}",
			[
				'token' => $arguments['token'],
				'method' => $arguments['method'],
			],
			[
				'action' => 'public_link_accessed_webdav',
				'token' => $arguments['token'],
				'method' => $arguments['method'],
			]
		);
	}

	protected static function getPermissionsArray($given) {
		$permissions = [];
		if ($given & Constants::PERMISSION_READ) {
			$permissions[] = 'READ';
		}
		if ($given & Constants::PERMISSION_UPDATE) {
			$permissions[] = 'UPDATE';
		}
		if ($given & Constants::PERMISSION_CREATE) {
			$permissions[] = 'CREATE';
		}
		if ($given & Constants::PERMISSION_DELETE) {
			$permissions[] = 'DELETE';
		}
		if ($given & Constants::PERMISSION_SHARE) {
			$permissions[] = 'SHARE';
		}

		return $permissions;
	}

	protected static function getShareTypeString($given) {
		switch ($given) {
			case Share::SHARE_TYPE_GROUP:
				return 'group';
			case Share::SHARE_TYPE_LINK:
				return 'link';
			case Share::SHARE_TYPE_USER:
				return 'user';
			case Share::SHARE_TYPE_GUEST:
				return 'guest';
			case Share::SHARE_TYPE_CONTACT:
				return 'contact';
			case Share::SHARE_TYPE_REMOTE:
				return 'remote';
		}
		return "unknown:$given";
	}

	public static function buildTarget($arguments) {
		if ($arguments['shareType'] === Share::SHARE_TYPE_GROUP) {
			return 'the group ' . $arguments['shareWith'];
		}
		if ($arguments['shareType'] === Share::SHARE_TYPE_USER) {
			return 'the user ' . $arguments['shareWith'];
		}
		if ($arguments['shareType'] === Share::SHARE_TYPE_REMOTE) {
			return 'the remote user ' . $arguments['shareWith'];
		}
		if ($arguments['shareType'] === Share::SHARE_TYPE_LINK) {
			$target = 'a public link';
			if (isset($arguments['token'])) {
				$target .= ', token: ' . $arguments['token'];
			}
			if (!empty($arguments['shareWith']) || $arguments['passwordEnabled']) {
				$target .= ', with password';
			} else {
				$target .= ', without password';
			}
			return $target;
		}
		return "unknown:{$arguments['shareType']}:{$arguments['shareWith']}";
	}
}
