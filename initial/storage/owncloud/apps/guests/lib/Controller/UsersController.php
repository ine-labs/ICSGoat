<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Guests\Controller;

use OC\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class UsersController extends Controller {

	/**
	 * @var IUserManager
	 */
	private $userManager;
	/**
	 * @var IL10N
	 */
	private $l10n;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IMailer
	 */
	private $mailer;
	/**
	 * @var ISecureRandom
	 */
	private $secureRandom;
	/**
	 * @var EventDispatcherInterface
	 */
	private $eventDispatcher;
	/**
	 * @var IUserSession
	 */
	private $currentUser;

	/**
	 * UsersController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param IMailer $mailer
	 * @param ISecureRandom $secureRandom
	 * @param EventDispatcherInterface $eventDispatcher
	 * @param IUserSession $currentUser
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IUserManager $userManager,
		IL10N $l10n,
		IConfig $config,
		IMailer $mailer,
		ISecureRandom $secureRandom,
		EventDispatcherInterface $eventDispatcher,
		IUserSession $currentUser
	) {
		parent::__construct($appName, $request);

		$this->userManager = $userManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->mailer = $mailer;
		$this->secureRandom = $secureRandom;
		$this->eventDispatcher = $eventDispatcher;
		$this->currentUser = $currentUser;
	}

	/**
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param string $email
	 * @param string $displayName
	 *
	 * @return DataResponse
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function create($email, $displayName) {
		$errorMessages = [];
		$email = \trim(\rawurldecode($email));
		$username = \strtolower($email);

		if (empty($email) || !$this->mailer->validateMailAddress($email)) {
			$errorMessages['email'] = (string)$this->l10n->t(
				'Invalid mail address'
			);
		}

		if ($this->userManager->userExists($username)) {
			$errorMessages['email'] = (string)$this->l10n->t(
				'A username with that email already exists.'
			);
		}

		$users = $this->userManager->getByEmail($email);
		if (!empty($users)) {
			$errorMessages['email'] = (string)$this->l10n->t(
				'A username with that email already exists.'
			);
		}

		if (!empty($errorMessages)) {
			return new DataResponse(
				[
					'errorMessages' => $errorMessages
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		$uid = $this->currentUser->getUser()->getUID();
		$isGuest = (bool) $this->config->getUserValue(
			$uid,
			'owncloud',
			'isGuest',
			false
		);

		if ($isGuest) {
			return new DataResponse(
				[
					'message' => (string)$this->l10n->t(
						'A guest user can not create other guest users.'
					)
				],
				Http::STATUS_FORBIDDEN
			);
		}

		$event = new GenericEvent();

		$this->eventDispatcher->dispatch($event, 'OCP\User::createPassword');

		if ($event->hasArgument('password')) {
			$password = $event->getArgument('password');
		} else {
			$password = $this->secureRandom->generate(20);
		}

		$user = $this->userManager->createUser(
			$username,
			$password
		);

		$user->setEMailAddress($email);

		if (!empty($displayName)) {
			$user->setDisplayName($displayName);
		}

		$token = $this->secureRandom->generate(
			21,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		);

		$this->config->setUserValue(
			$username,
			'guests',
			'registerToken',
			$token
		);

		$this->config->setUserValue(
			$username,
			'guests',
			'created',
			\strval(\time())
		);

		$this->config->setUserValue(
			$username,
			'owncloud',
			'isGuest',
			'1'
		);

		return new DataResponse(
			[
				'userid' => $user->getUID(),
				'displayname' => $user->getDisplayName(),
				'email' => $user->getEMailAddress(),
				'message' => (string)$this->l10n->t(
					'User successfully created'
				)
			],
			Http::STATUS_CREATED
		);
	}
}
