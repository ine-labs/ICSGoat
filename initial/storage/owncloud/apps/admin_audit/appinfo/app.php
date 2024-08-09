<?php
/**
 * ownCloud
 *
 * @author Frank Karlitscheck <frank@owncloud.com>
 * @author Tom Needham <tom@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

use OCA\Admin_Audit\Handlers\Lifecycle;

$licenseManager = \OC::$server->getLicenseManager();
if ($licenseManager->checkLicenseFor('admin_audit')) {
	$eventDispatcher = OC::$server->getEventDispatcher();

	/**
	 * Log events of addition, deletion and update in the config.php file
	 * @var OCA\Admin_Audit\Handlers\Config $config
	 */
	$config = new OCA\Admin_Audit\Handlers\Config();
	$eventDispatcher->addListener('config.aftersetvalue', [$config, 'createorupdate']);
	$eventDispatcher->addListener('config.afterdeletevalue', [$config, 'delete']);

	/**
	 * Log events of impersonation and logout after impersonation
	 * @var OCA\Admin_Audit\Handlers\Impersonate $impersonate
	 */
	$impersonate = new \OCA\Admin_Audit\Handlers\Impersonate();
	$eventDispatcher->addListener('user.afterimpersonate', [$impersonate, 'impersonated']);
	$eventDispatcher->addListener('user.afterimpersonatelogout', [$impersonate, 'loggedout']);

	/**
	 * Log events of addition, deletion and update in the appconfig table
	 * @var OCA\Admin_Audit\Handlers\AppConfig $appConfig
	 */
	$appConfig = new OCA\Admin_Audit\Handlers\AppConfig();
	$eventDispatcher->addListener('appconfig.aftersetvalue', [$appConfig, 'createorupdate']);
	$eventDispatcher->addListener('appconfig.afterdeletevalue', [$appConfig, 'delete']);
	$eventDispatcher->addListener('appconfig.afterdeleteapp', [$appConfig, 'deleteapp']);

	/**
	 * Log events of addition, deletion and update in the user preferences table
	 * @var OCA\Admin_Audit\Handlers\UserPreferences $userPreferences
	 */
	$userPreferences = new OCA\Admin_Audit\Handlers\UserPreferences();
	$eventDispatcher->addListener('userpreferences.afterSetValue', [$userPreferences, 'updateUserKeyValue']);
	$eventDispatcher->addListener('userpreferences.afterDeleteValue', [$userPreferences, 'deleteUserKey']);
	$eventDispatcher->addListener('userpreferences.afterDeleteApp', [$userPreferences, 'deleteAllUserPreferencesApp']);
	$eventDispatcher->addListener('userpreferences.afterDeleteUser', [$userPreferences, 'deleteAllUserPreferencesUser']);

	/**
	 * Handle comment events to create, update and delete
	 * @var OCA\Admin_Audit\Handlers\Comments $commentHandler
	 */
	$commentHandler = new OCA\Admin_Audit\Handlers\Comments();
	$eventDispatcher->addListener('comment.aftercreate', [$commentHandler, 'createComment']);
	$eventDispatcher->addListener('comment.afterupdate', [$commentHandler, 'updateComment']);
	$eventDispatcher->addListener('comment.afterdelete', [$commentHandler, 'deleteComment']);

	$sessionHandler = new OCA\Admin_Audit\Handlers\Session(new \OCP\Defaults());
	$eventDispatcher->addListener('user.loginfailed', [$sessionHandler, 'login_failed']);
	$eventDispatcher->addListener('user.afterlogin', [$sessionHandler, 'post_login']);
	$eventDispatcher->addListener('user.beforelogout', [$sessionHandler, 'logout']);

	$userSession = \OC::$server->getUserSession();
	$adminAuditUser = new OCA\Admin_Audit\Handlers\User();
	$eventDispatcher->addListener('user.afterfeaturechange', [$adminAuditUser, 'changeUser']);
	$eventDispatcher->addListener('user.aftersetpassword', [$adminAuditUser, 'postSetPassword']);
	$eventDispatcher->addListener('user.aftercreate', [$adminAuditUser, 'user_create']);
	$eventDispatcher->addListener('user.afterdelete', [$adminAuditUser, 'user_delete']);

	$groupManager = \OC::$server->getGroupManager();
	$userAuditHandler = OCA\Admin_Audit\Handlers\User::class;
	$groupManager->listen('\OC\Group', 'postAddUser', [$userAuditHandler, 'postAddUser']);
	$groupManager->listen('\OC\Group', 'postRemoveUser', [$userAuditHandler, 'postRemoveUser']);
	$eventDispatcher->addListener('group.postCreate', [$userAuditHandler, 'createGroup']);
	$eventDispatcher->addListener('group.postDelete', [$userAuditHandler, 'deleteGroup']);

	$fileAuditHandler = new OCA\Admin_Audit\Handlers\Files();
	$eventDispatcher->addListener('file.afterrename', [$fileAuditHandler, 'rename']);
	$eventDispatcher->addListener('file.aftercreate', [$fileAuditHandler, 'create']);
	$eventDispatcher->addListener('file.aftercopy', [$fileAuditHandler, 'copy']);
	$eventDispatcher->addListener('file.afterupdate', [$fileAuditHandler, 'update']);
	$eventDispatcher->addListener('file.beforedelete', [$fileAuditHandler, 'beforeDelete']);
	$eventDispatcher->addListener('file.afterdelete', [$fileAuditHandler, 'delete']);
	OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_read,
		\OCA\Admin_Audit\Handlers\Files::class,
		'read'
	);
	OCP\Util::connectHook(
		\OC\Files\Filesystem::CLASSNAME,
		\OC\Files\Filesystem::signal_post_copy,
		\OCA\Admin_Audit\Handlers\Files::class,
		'copyUsingDAV'
	);

	OCP\Util::connectHook(
		'\OCA\Files_Trashbin\Trashbin',
		'post_restore',
		\OCA\Admin_Audit\Handlers\Files::class,
		'trash_post_restore'
	);
	OCP\Util::connectHook(
		'\OCP\Trashbin',
		'preDelete',
		\OCA\Admin_Audit\Handlers\Files::class,
		'trash_delete'
	);

	OCP\Util::connectHook(
		'\OCP\Versions',
		'delete',
		\OCA\Admin_Audit\Handlers\Files::class,
		'version_delete'
	);
	OCP\Util::connectHook(
		'\OCP\Versions',
		'rollback',
		\OCA\Admin_Audit\Handlers\Files::class,
		'version_rollback'
	);

	OCP\Util::connectHook(
		'OCP\Share',
		'post_shared',
		\OCA\Admin_Audit\Handlers\Sharing::class,
		'post_shared'
	);
	OCP\Util::connectHook(
		'OCP\Share',
		'post_unshare',
		\OCA\Admin_Audit\Handlers\Sharing::class,
		'post_unshare'
	);
	OCP\Util::connectHook(
		'OCP\Share',
		'share_link_access',
		\OCA\Admin_Audit\Handlers\Sharing::class,
		'share_link_access'
	);

	//Listener for self unshare, update
	$sharing = new OCA\Admin_Audit\Handlers\Sharing();
	$eventDispatcher->addListener('fromself.unshare', [$sharing, 'unshareFromRecipient']);
	$eventDispatcher->addListener('share.afterupdate', [$sharing, 'shareUpdate']);

	//Listener for share accept, decline
	$eventDispatcher->addListener('share.afteraccept', [$sharing, 'shareAccept']);
	$eventDispatcher->addListener('share.afterreject', [$sharing, 'shareDecline']);

	//For logging CustomGroups activities
	$eventDispatcher = \OC::$server->getEventDispatcher();
	$customGroups = new OCA\Admin_Audit\Handlers\CustomGroups();
	$eventDispatcher->addListener('\OCA\CustomGroups::addGroupAndUser', [$customGroups,'addGroupAndUser']);
	$eventDispatcher->addListener('\OCA\CustomGroups::addUserToGroup', [$customGroups, 'addUserToGroup']);
	$eventDispatcher->addListener('\OCA\CustomGroups::deleteGroup', [$customGroups,'deleteGroup']);
	$eventDispatcher->addListener('\OCA\CustomGroups::removeUserFromGroup', [$customGroups,'removeUserFromGroup']);
	$eventDispatcher->addListener('\OCA\CustomGroups::leaveFromGroup', [$customGroups,'leaveFromGroup']);
	$eventDispatcher->addListener('\OCA\CustomGroups::updateGroupName', [$customGroups, 'updateGroupName']);
	$eventDispatcher->addListener('\OCA\CustomGroups::changeRoleInGroup', [$customGroups, 'changeRoleInGroup']);

	//For logging accepted/rejected federated share
	$eventDispatcher = OC::$server->getEventDispatcher();
	$acceptRemoteShare = new OCA\Admin_Audit\Handlers\Sharing();
	$eventDispatcher->addListener('remoteshare.accepted', [$acceptRemoteShare, 'accept_remote_shared']);
	$eventDispatcher->addListener('remoteshare.declined', [$acceptRemoteShare, 'decline_remote_shared']);
	$eventDispatcher->addListener('\OCA\FederatedFileSharing::remote_shareReceived', [$acceptRemoteShare, 'federatedShareArrivedFromRemote']);
	$eventDispatcher->addListener('\OCA\Files_Sharing::unshareEvent', [$acceptRemoteShare, 'unshareInfo']);

	//For logging public links using webdav
	$eventDispatcher->addListener('dav.public.get.after', [$acceptRemoteShare, 'accessPublicLinkWebDAV']);
	$eventDispatcher->addListener('dav.public.propfind.after', [$acceptRemoteShare, 'accessPublicLinkWebDAV']);

	$managerListener = function (\OCP\SystemTag\ManagerEvent $event) {
		$application = new \OCP\AppFramework\App('admin_audit');
		/** @var \OCA\Admin_Audit\Handlers\SystemTags $listener */
		$listener = $application->getContainer()->query(\OCA\Admin_Audit\Handlers\SystemTags::class);
		$listener->managerEvent($event);
	};

	$eventDispatcher = \OC::$server->getEventDispatcher();
	$eventDispatcher->addListener(\OCP\SystemTag\ManagerEvent::EVENT_CREATE, $managerListener);
	$eventDispatcher->addListener(\OCP\SystemTag\ManagerEvent::EVENT_DELETE, $managerListener);
	$eventDispatcher->addListener(\OCP\SystemTag\ManagerEvent::EVENT_UPDATE, $managerListener);

	$mapperListener = function (\OCP\SystemTag\MapperEvent $event) {
		$application = new \OCP\AppFramework\App('admin_audit');
		/** @var \OCA\Admin_Audit\Handlers\SystemTags $listener */
		$listener = $application->getContainer()->query(\OCA\Admin_Audit\Handlers\SystemTags::class);
		$listener->mapperEvent($event);
	};

	$eventDispatcher->addListener(\OCP\SystemTag\MapperEvent::EVENT_ASSIGN, $mapperListener);
	$eventDispatcher->addListener(\OCP\SystemTag\MapperEvent::EVENT_UNASSIGN, $mapperListener);

	$consoleListener = function (\OCP\Console\ConsoleEvent $event) {
		$application = new \OCP\AppFramework\App('admin_audit');
		/** @var \OCA\Admin_Audit\Handlers\Console $listener */
		$listener = $application->getContainer()->query(\OCA\Admin_Audit\Handlers\Console::class);
		$listener->consoleEvent($event);
	};

	$eventDispatcher->addListener(\OCP\Console\ConsoleEvent::EVENT_RUN, $consoleListener);

	$appManagerListener = function (\OCP\App\ManagerEvent $event) {
		$application = new \OCP\AppFramework\App('admin_audit');
		/** @var \OCA\Admin_Audit\Handlers\App $listener */
		$listener = $application->getContainer()->query(\OCA\Admin_Audit\Handlers\App::class);
		$listener->managerEvent($event);
	};

	$eventDispatcher->addListener(\OCP\App\ManagerEvent::EVENT_APP_ENABLE, $appManagerListener);
	$eventDispatcher->addListener(\OCP\App\ManagerEvent::EVENT_APP_ENABLE_FOR_GROUPS, $appManagerListener);
	$eventDispatcher->addListener(\OCP\App\ManagerEvent::EVENT_APP_DISABLE, $appManagerListener);

	$eventDispatcher->addListener(\OC\User\User::class . '::postSetEnabled', [\OCA\Admin_Audit\Handlers\User::class, 'postSetEnabled']);

	/**
	 * Log files_lifecycle events
	 */
	$filesLifecycleHandler = new Lifecycle();
	$eventDispatcher->addListener(
		'lifecycle:file_archived',
		function ($event) use ($filesLifecycleHandler) {
			$filesLifecycleHandler->handleFileArchived($event);
		}
	);
	// File restored
	$eventDispatcher->addListener(
		'lifecycle:file_restored',
		function ($event) use ($filesLifecycleHandler) {
			$filesLifecycleHandler->handleFileRestored($event);
		}
	);
	// File expired
	$eventDispatcher->addListener(
		'lifecycle:file_expired',
		function ($event) use ($filesLifecycleHandler) {
			$filesLifecycleHandler->handleFileExpired($event);
		}
	);

	$eventDispatcher->addListener('smb_acl.acl.beforeset', [\OCA\Admin_Audit\Handlers\SmbAcl::class, 'beforeSetAcl']);
	$eventDispatcher->addListener('smb_acl.acl.afterset', [\OCA\Admin_Audit\Handlers\SmbAcl::class, 'afterSetAcl']);
}
