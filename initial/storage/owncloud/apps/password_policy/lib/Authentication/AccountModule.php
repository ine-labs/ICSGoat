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

namespace OCA\PasswordPolicy\Authentication;

use OCP\Authentication\Exceptions\AccountCheckException;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Authentication\IAccountModule;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;

class AccountModule implements IAccountModule {

	/**
	 * @var ISession
	 */
	private $session;
	/**
	 * @var IRequest
	 */
	private $request;
	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	public function __construct(ISession $session, IRequest $request, IURLGenerator $urlGenerator) {
		$this->session = $session;
		$this->request = $request;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Checks a session variable to not interfere with existing sessions
	 *
	 * @param IUser $user
	 * @throws AccountCheckException
	 */
	public function check(IUser $user) {
		$forcePasswordChange = $this->session->get('password_policy.forcePasswordChange');
		if ($forcePasswordChange) {
			$redirectUrl = $this->request->getRequestUri();
			$response = new RedirectResponse(
				$this->urlGenerator->linkToRoute('password_policy.password.show', [
						'redirect_url' => $redirectUrl
					])
			);
			// full list of error codes https://msdn.microsoft.com/en-us/library/windows/desktop/ms681386(v=vs.85).aspx
			// ldap relevant: https://github.com/rancher/rancher/issues/1941#issue-104315860
			// we use 'user must reset password', not 'password expired' because the letter indicates password cannot be changed
			throw new AccountCheckException($response, 'The user must reset their password.', 0x773);
		}
	}
}
