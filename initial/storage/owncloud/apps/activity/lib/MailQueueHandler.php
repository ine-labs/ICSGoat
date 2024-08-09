<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Activity;

use OCP\Activity\IManager;
use OCP\Defaults;
use OCP\IDateTimeFormatter;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Template;
use OCP\Util;

/**
 * Class MailQueueHandler
 * Gets the users from the database and
 *
 * @package OCA\Activity
 */
class MailQueueHandler {
	/** Number of entries we want to list in the email */
	public const ENTRY_LIMIT = 200;

	public const POSTGRE_MAX_INT = 2147483647;

	/** @var array */
	protected $languages;

	/** @var string */
	protected $senderAddress;

	/** @var string */
	protected $senderName;

	/** @var IDateTimeFormatter */
	protected $dateFormatter;

	/** @var DataHelper */
	protected $dataHelper;

	/** @var IDBConnection */
	protected $connection;

	/** @var IMailer */
	protected $mailer;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var IUserManager */
	protected $userManager;

	/** @var IManager */
	protected $activityManager;

	/**
	 * Constructor
	 *
	 * @param IDateTimeFormatter $dateFormatter
	 * @param IDBConnection $connection
	 * @param DataHelper $dataHelper
	 * @param IMailer $mailer
	 * @param IURLGenerator $urlGenerator
	 * @param IUserManager $userManager
	 * @param IManager $activityManager
	 */
	public function __construct(
		IDateTimeFormatter $dateFormatter,
		IDBConnection $connection,
		DataHelper $dataHelper,
		IMailer $mailer,
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		IManager $activityManager
	) {
		$this->dateFormatter = $dateFormatter;
		$this->connection = $connection;
		$this->dataHelper = $dataHelper;
		$this->mailer = $mailer;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->activityManager = $activityManager;
	}

	/**
	 * Get the users we want to send an email to
	 *
	 * @param int|null $limit
	 * @param int $latestSend
	 * @return array
	 */
	public function getAffectedUsers($limit, $latestSend) {
		$limit = (!$limit) ? null : (int) $limit;

		$query = $this->connection->prepare(
			'SELECT `amq_affecteduser`, `email`, MIN(`amq_latest_send`) AS `amq_trigger_time` '
			. ' FROM `*PREFIX*activity_mq` '
			. ' LEFT JOIN `*PREFIX*accounts` ON `user_id` = `amq_affecteduser` '
			. ' WHERE `amq_latest_send` < ? '
			. ' GROUP BY `amq_affecteduser`, `email` '
			. ' ORDER BY `amq_trigger_time` ASC',
			$limit
		);
		$query->execute([$latestSend]);

		$affectedUsers = [];
		while ($row = $query->fetch()) {
			$affectedUsers[] = [
				'uid' => $row['amq_affecteduser'],
				'email' => $row['email']
			];
		}

		return $affectedUsers;
	}

	/**
	 * Get all items for the user we want to send an email to
	 *
	 * @param string $affectedUser
	 * @param int $maxTime
	 * @param int $maxNumItems
	 * @return array [data of the first max. 200 entries, total number of entries]
	 */
	protected function getItemsForUser($affectedUser, $maxTime, $maxNumItems = self::ENTRY_LIMIT) {
		$query = $this->connection->prepare(
			'SELECT * '
			. ' FROM `*PREFIX*activity_mq` '
			. ' WHERE `amq_timestamp` <= ? '
			. ' AND `amq_affecteduser` = ? '
			. ' ORDER BY `amq_timestamp` ASC',
			$maxNumItems
		);
		$query->execute([(int) $maxTime, $affectedUser]);

		$activities = [];
		while ($row = $query->fetch()) {
			$activities[] = $row;
		}

		if (isset($activities[$maxNumItems - 1])) {
			// Reached the limit, run a query to get the actual count.
			$query = $this->connection->prepare(
				'SELECT COUNT(*) AS `actual_count`'
				. ' FROM `*PREFIX*activity_mq` '
				. ' WHERE `amq_timestamp` <= ? '
				. ' AND `amq_affecteduser` = ?'
			);
			$query->execute([(int) $maxTime, $affectedUser]);

			$row = $query->fetch();
			return [$activities, $row['actual_count'] - $maxNumItems];
		} else {
			return [$activities, 0];
		}
	}

	/**
	 * Get a language object for a specific language
	 *
	 * @param string $lang Language identifier
	 * @return \OCP\IL10N Language object of $lang
	 */
	protected function getLanguage($lang) {
		if (!isset($this->languages[$lang])) {
			$this->languages[$lang] = Util::getL10N('activity', $lang);
		}

		return $this->languages[$lang];
	}

	/**
	 * Get the sender data
	 * @param string $setting Either `email` or `name`
	 * @return string
	 */
	protected function getSenderData($setting) {
		if (empty($this->senderAddress)) {
			$this->senderAddress = Util::getDefaultEmailAddress('no-reply');
		}
		if (empty($this->senderName)) {
			$defaults = new Defaults();
			$this->senderName = $defaults->getName();
		}

		if ($setting === 'email') {
			return $this->senderAddress;
		}
		return $this->senderName;
	}

	/**
	 * Send a notification to one user
	 *
	 * @param string $userName Username of the recipient
	 * @param string $email Email address of the recipient
	 * @param string $lang Selected language of the recipient
	 * @param string $timezone Selected timezone of the recipient
	 * @param int $maxTime
	 */
	public function sendEmailToUser($userName, $email, $lang, $timezone, $maxTime) {
		$user = $this->userManager->get($userName);
		if (!$user instanceof IUser) {
			return;
		}

		list($mailData, $skippedCount) = $this->getItemsForUser($userName, $maxTime);
		$this->sendMail($user, $email, $lang, $timezone, $mailData, $skippedCount);
	}

	/**
	 * @param IUser $user
	 * @param string $email
	 * @param string $lang
	 * @param string $timezone
	 * @param array $mailData
	 * @param int $skippedCount
	 */
	protected function sendMail($user, $email, $lang, $timezone, $mailData, $skippedCount) {
		$l = $this->getLanguage($lang);
		$plainParser = new PlainTextParser($l);
		$htmlParser = new HtmlTextParser($l, $this->urlGenerator);
		$this->dataHelper->setUser($user->getUid());
		$this->dataHelper->setL10n($l);
		$this->activityManager->setCurrentUserId($user->getUid());

		$activityListPlain = [];
		$activityListHtml = [];
		foreach ($mailData as $activity) {
			$event = $this->activityManager->generateEvent();
			$event->setApp($activity['amq_appid'])
				->setType($activity['amq_type'])
				->setTimestamp($activity['amq_timestamp'])
				->setSubject($activity['amq_subject'], []);

			$relativeDateTime = $this->dateFormatter->formatDateTimeRelativeDay(
				$activity['amq_timestamp'],
				'long',
				'medium',
				new \DateTimeZone($timezone),
				$l
			);

			$message = $this->dataHelper->translation(
				$activity['amq_appid'],
				$activity['amq_subject'],
				$this->dataHelper->getParameters($event, 'subject', $activity['amq_subjectparams'])
			);

			$activityListPlain[] = [
				$plainParser->parseMessage($message),
				$relativeDateTime,
			];
			$activityListHtml[] = [
				$htmlParser->parseMessage($message),
				$relativeDateTime,
			];
		}

		$alttext = new Template('activity', 'email.notification', '', false);
		$alttext->assign('username', $user->getDisplayName());
		$alttext->assign('activities', $activityListPlain);
		$alttext->assign('skippedCount', $skippedCount);
		$alttext->assign('owncloud_installation', $this->urlGenerator->getAbsoluteURL('/'));
		$alttext->assign('overwriteL10N', $l);
		$emailText = $alttext->fetchPage();

		$htmltext = new Template('activity', 'html.notification', '', false);
		$htmltext->assign('username', $user->getDisplayName());
		$htmltext->assign('activities', $activityListHtml);
		$htmltext->assign('skippedCount', $skippedCount);
		$htmltext->assign('owncloud_installation', $this->urlGenerator->getAbsoluteURL('/'));
		$htmltext->assign('overwriteL10N', $l);
		$htmlText = $htmltext->fetchPage();

		$message = $this->mailer->createMessage();
		$message->setTo([$email => $user->getDisplayName()]);
		$message->setSubject((string) $l->t('Activity notification'));
		$message->setHtmlBody($htmlText);
		$message->setPlainBody($emailText);
		$message->setFrom([$this->getSenderData('email') => $this->getSenderData('name')]);
		$this->mailer->send($message);

		$this->activityManager->setCurrentUserId(null);
	}

	/**
	 * Delete all entries we dealt with
	 *
	 * @param array $affectedUsers
	 * @param int $maxTime
	 */
	public function deleteSentItems(array $affectedUsers, $maxTime) {
		// Don't try to delete if we are not considering any users
		if (\count($affectedUsers) === 0) {
			return;
		}
		$placeholders = \implode(',', \array_fill(0, \sizeof($affectedUsers), '?'));
		$queryParams = $affectedUsers;
		\array_unshift($queryParams, (int) $maxTime);

		$query = $this->connection->prepare(
			'DELETE FROM `*PREFIX*activity_mq` '
			. ' WHERE `amq_timestamp` <= ? '
			. ' AND `amq_affecteduser` IN (' . $placeholders . ')'
		);
		$query->execute($queryParams);
	}

	/**
	 * Get all unique users from mail queue
	 * Instant messaging use case
	 *
	 * @param int $limit
	 * @return array
	 */
	public function getAllUsers($limit) {
		$limit = (!$limit) ? null : (int) $limit;

		$query = $this->connection->prepare(
			'SELECT `amq_affecteduser`, `email`, MAX(`mail_id`) AS `max_mail_id`, MIN(`amq_latest_send`) AS `amq_trigger_time` '
			. ' FROM `*PREFIX*activity_mq` '
			. ' LEFT JOIN `*PREFIX*accounts` ON `user_id` = `amq_affecteduser` '
			. ' GROUP BY `amq_affecteduser`, `email` '
			. ' ORDER BY `amq_trigger_time` ASC',
			$limit
		);
		$query->execute();

		$allUsers = [];
		while ($row = $query->fetch()) {
			$allUsers[] = [
				'uid' => $row['amq_affecteduser'],
				'email' => $row['email'],
				'max_mail_id' => $row['max_mail_id'],
			];
		}

		return $allUsers;
	}

	/**
	 * Send a notification containing all queue items to one user
	 * Instant messaging use case
	 *
	 * @param string $userName Username of the recipient
	 * @param string $email Email address of the recipient
	 * @param string $lang Selected language of the recipient
	 * @param string $timezone Selected timezone of the recipient
	 * @param int $maxMailId Maximal notification id
	 */
	public function sendAllEmailsToUser($userName, $email, $lang, $timezone, $maxMailId) {
		$user = $this->userManager->get($userName);
		if (!$user instanceof IUser) {
			return;
		}

		// Get All Notifications for this user
		list($mailData, $skippedCount) = $this->getItemsForUser($userName, self::POSTGRE_MAX_INT);
		// Sort out any entries that are greater than $maxMailId
		$mailData = \array_filter(
			$mailData,
			function ($item) use ($maxMailId) {
				return $item['mail_id'] <= $maxMailId;
			}
		);

		$this->sendMail($user, $email, $lang, $timezone, $mailData, $skippedCount);
	}

	/**
	 * Delete all entries we dealt with
	 * Instant messaging use case
	 *
	 * @param array $affectedUsers uid => maximal mail_id of all processed for this user notifications
	 */
	public function deleteAllSentItems(array $affectedUsers) {
		$userCount = \count($affectedUsers);
		// Don't try to delete if we are not considering any users
		if ($userCount === 0) {
			return;
		}
		$queryPartsArray = [];
		$queryParams = [];
		foreach ($affectedUsers as $userData) {
			$queryParams[] = $userData['uid'];
			$queryParams[] = $userData['maxMailId'];
			$queryPartsArray[] = '(`amq_affecteduser` = ? AND `mail_id` <= ?)';
		}

		$queryParts = \implode(' OR ', $queryPartsArray);

		$query = $this->connection->prepare(
			'DELETE FROM `*PREFIX*activity_mq` WHERE ' . $queryParts
		);
		$query->execute($queryParams);
	}
}
