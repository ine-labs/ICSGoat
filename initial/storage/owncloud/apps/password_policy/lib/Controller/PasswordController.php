<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\PasswordPolicy\Controller;

use OCA\PasswordPolicy\Rules\PolicyException;
use OCA\PasswordPolicy\ConfigProvider;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Authentication\IAccountModuleController;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;

class PasswordController extends Controller implements IAccountModuleController {

	/** @var IUserSession */
	protected $userSession;
	/** @var IUserManager */
	protected $userManager;
	/** @var IConfig */
	protected $config;
	/** @var ISession */
	protected $session;
	/** @var IURLGenerator */
	protected $urlGenerator;
	/** @var IL10N */
	protected $l10n;
	/** @var ConfigProvider */
	protected $configProvider;

	/**
	 * PasswordController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 * @param ISession $session
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $l10n
	 * @param ConfigProvider $configProvider
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IUserSession $userSession,
		IUserManager $userManager,
		IConfig $config,
		ISession $session,
		IURLGenerator $urlGenerator,
		IL10N $l10n,
		ConfigProvider $configProvider
	) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->config = $config;
		$this->session = $session;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->configProvider = $configProvider;
	}

	/**
	 * @param string $redirect_url
	 * @param string $error (optional)
	 * @return TemplateResponse
	 */
	protected function createPasswordTemplateResponse($redirect_url, $error = null) {
		$params = [
			'redirect_url' => $redirect_url,
			'webroot' => \OC::$WEBROOT,
			'password_requirements' => $this->configProvider->getActivePasswordRequirements(),
		];

		$user = $this->userSession->getUser();
		if ($user !== null) {
			if ($this->config->getUserValue($user->getUID(), 'password_policy', 'firstLoginPasswordChange') === '1') {
				$params['firstLogin'] = true;
			}
		}
		if (empty($params['redirect_url'])) {
			$params['redirect_url'] = $this->urlGenerator->getAbsoluteURL('');
		}
		if ($error) {
			$params['error'] = $error;
		}
		return new TemplateResponse(
			$this->appName,
			'password',
			$params,
			'guest'
		);
	}
	/**
	 * Shows a change password template
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param string $redirect_url
	 * @return TemplateResponse|JSONResponse
	 *
	 * @throws \Exception
	 */
	public function show($redirect_url) {
		// gracefully handle redirected ocs api requests
		if ($this->request->getHeader('OCS-APIREQUEST') === 'true') {
			return new JSONResponse([], Http::STATUS_NO_CONTENT);
		}
		return $this->createPasswordTemplateResponse($redirect_url);
	}

	/**
	 * Shows a change password template
	 *
	 * @UseSession
	 * @NoAdminRequired
	 *
	 * @param string $current_password
	 * @param string $new_password
	 * @param string $confirm_password
	 * @param string $redirect_url
	 * @return TemplateResponse|RedirectResponse
	 */
	public function update($current_password, $new_password, $confirm_password, $redirect_url) {
		$user = $this->userSession->getUser();
		if (($user !== null) && !$this->userManager->checkPassword($user->getUID(), $current_password)) {
			return $this->createPasswordTemplateResponse(
				$redirect_url,
				$this->l10n->t('Old password is wrong.')
			);
		}

		if ($new_password !== $confirm_password) {
			return $this->createPasswordTemplateResponse(
				$redirect_url,
				$this->l10n->t('Password confirmation does not match the password.')
			);
		}

		if ($new_password === $current_password) {
			return $this->createPasswordTemplateResponse(
				$redirect_url,
				$this->l10n->t('Password must be different than the old password.')
			);
		}

		try {
			$changed = $user->setPassword($new_password);
		} catch (PolicyException $e) {
			return $this->createPasswordTemplateResponse(
				$redirect_url,
				$e->getMessage()
			);
		}
		if (!$changed) {
			// throw exception, so the user can go to the admin
			throw new \UnexpectedValueException('Unable to update the user\'s password.');
		}

		// unset session flag
		$this->session->remove('password_policy.forcePasswordChange');
		// unset user config flag
		$this->config->deleteUserValue($user->getUID(), 'password_policy', 'forcePasswordChange');
		if ($this->config->getUserValue($user->getUID(), 'password_policy', 'firstLoginPasswordChange')) {
			$this->config->deleteUserValue($user->getUID(), 'password_policy', 'firstLoginPasswordChange');
		}
		return new RedirectResponse($redirect_url);
	}
}
