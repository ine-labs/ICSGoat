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

namespace OCA\Files_Lifecycle\Activity;

use OCA\Files_Lifecycle\Application;
use OCA\Files_Lifecycle\Events\FileArchivedEvent;
use OCA\Files_Lifecycle\Events\FileExpiredEvent;
use OCA\Files_Lifecycle\Events\FileRestoredEvent;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Files\IRootFolder;
use OCP\IUserSession;

/**
 * Class Listener
 *
 * Takes in the symfony events from the archive processing classes and converts
 * them into appropriate activity events for users streams
 *
 * @package OCA\Files_Lifecycle\Activity
 */
class Listener {
	/**
	 * @var IManager
	 */
	protected $activityManager;
	/**
	 * @var IUserSession
	 */
	protected $session;
	/**
	 * @var \OCP\App\IAppManager
	 */
	protected $appManager;
	/**
	 * @var \OCP\Files\Config\IMountProviderCollection
	 */
	protected $mountCollection;
	/**
	 * @var \OCP\Files\IRootFolder
	 */
	protected $rootFolder;

	/**
	 * Listener constructor.
	 *
	 * @param IManager $activityManager
	 * @param IUserSession $session
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(
		IManager $activityManager,
		IUserSession $session,
		IRootFolder $rootFolder
	) {
		$this->activityManager = $activityManager;
		$this->session = $session;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @param FileArchivedEvent $event
	 *
	 * @return void;
	 */
	public function fileArchived(FileArchivedEvent $event) {
		$activity = $this->activityManager->generateEvent();
		$this->activityManager->publish(
			$this->mapArchiveEventToActivity(
				$event,
				$activity
			)
		);
	}

	/**
	 * @param FileArchivedEvent $event
	 * @param IEvent $activity
	 *
	 * @return IEvent
	 */
	protected function mapArchiveEventToActivity(
		FileArchivedEvent $event,
		IEvent $activity
	) {
		$actor = $event->getOriginalFileInfo()->getOwner()->getUID();
		$activity->setApp(Application::APPID);
		$activity->setType(Application::APPID);
		$activity->setAuthor($actor);
		$activity->setObject('files', $event->getOriginalFileInfo()->getId());
		$activity->setMessage('');
		$activity->setAffectedUser($actor);
		$activity->setSubject(
			Extension::FILE_ARCHIVED_SUBJECT,
			[
				\pathinfo($event->getOriginalFileInfo()->getPath())['basename'],
			]
		);
		return $activity;
	}

	/**
	 * @param FileRestoredEvent $event
	 *
	 * @return void;
	 */
	public function fileRestored(FileRestoredEvent $event) {
		$activity = $this->activityManager->generateEvent();
		$this->activityManager->publish(
			$this->mapRestoreEventToActivity(
				$event,
				$activity
			)
		);
	}

	/**
	 * @param FileRestoredEvent $event
	 * @param IEvent $activity
	 *
	 * @return IEvent
	 */
	protected function mapRestoreEventToActivity(
		FileRestoredEvent $event,
		IEvent $activity
	) {
		$actor = $event->getOriginalFileInfo()->getOwner()->getUID();
		$activity->setApp(Application::APPID)
			->setType(Application::APPID)
			->setAuthor($actor)
			->setObject('files', $event->getOriginalFileInfo()->getId())
			->setMessage('');
		$activity->setAffectedUser($actor);
		$activity->setSubject(
			Extension::FILE_RESTORED_SUBJECT,
			[
				\pathinfo($event->getOriginalFileInfo()->getPath())['basename'],
			]
		);
		return $activity;
	}

	/**
	 * @param FileExpiredEvent $event
	 *
	 * @return void;
	 */
	public function fileExpired(FileExpiredEvent $event) {
		$activity = $this->activityManager->generateEvent();
		$this->activityManager->publish(
			$this->mapExpireEventToActivity(
				$event,
				$activity
			)
		);
	}

	/**
	 * @param FileExpiredEvent $event
	 * @param IEvent $activity
	 *
	 * @return IEvent
	 */
	protected function mapExpireEventToActivity(
		FileExpiredEvent $event,
		IEvent $activity
	) {
		$actor = $event->getOwner()->getUID();
		$activity->setApp(Application::APPID)
			->setType(Application::APPID)
			->setAuthor($actor)
			->setObject('files', $event->getFileId())
			->setMessage('');
		$activity->setAffectedUser($actor);
		$activity->setSubject(
			Extension::FILE_EXPIRED_SUBJECT,
			[
				\pathinfo($event->getPath())['basename'],
			]
		);
		return $activity;
	}
}
