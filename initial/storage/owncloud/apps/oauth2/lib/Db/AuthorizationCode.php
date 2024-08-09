<?php
/**
 * @author Project Seminar "sciebo@Learnweb" of the University of Muenster
 * @copyright Copyright (c) 2017, University of Muenster
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
 */

namespace OCA\OAuth2\Db;

use OCA\OAuth2\Exceptions\UnsupportedPkceTransformException;
use OCA\OAuth2\Utilities;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getCode()
 * @method void setCode(string $code)
 * @method int getClientId()
 * @method void setClientId(int $clientId)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getExpires()
 * @method void setExpires(int $value)
 * @method void setCodeChallenge(string $codeChallenge)
 * @method void setCodeChallengeMethod(string $codeChallengeMethod)
 */
class AuthorizationCode extends Entity {
	public const EXPIRATION_TIME = 600;

	protected $code;
	protected $clientId;
	protected $userId;
	protected $expires;
	protected $codeChallenge;
	protected $codeChallengeMethod;

	/**
	 * AuthorizationCode constructor.
	 */
	public function __construct() {
		$this->addType('id', 'int');
		$this->addType('code', 'string');
		$this->addType('client_id', 'int');
		$this->addType('user_id', 'string');
		$this->addType('expires', 'int');
		$this->addType('code_challenge', 'string');
		$this->addType('code_challenge_method', 'string');
	}

	/**
	 * Resets the expiry time to EXPIRATION_TIME seconds from now.
	 */
	public function resetExpires() {
		$this->setExpires(\time() + self::EXPIRATION_TIME);
	}

	/**
	 * Determines if an authorization code has expired.
	 *
	 * @return boolean true if the authorization code has expired, false otherwise.
	 */
	public function hasExpired() {
		return \time() >= $this->getExpires();
	}

	public function isCodeVerifierValid($codeVerifier) {
		if ($this->codeChallengeMethod === 'S256') {
			// See https://tools.ietf.org/pdf/rfc7636.pdf#57
			$h = \hash('sha256', $codeVerifier, true);
			$encoded = Utilities::base64url_encode($h);
			return $encoded === $this->codeChallenge;
		} elseif ($this->codeChallengeMethod === 'plain' ||
				   $this->codeChallengeMethod === '' ||
				   $this->codeChallengeMethod === null) {
			return $codeVerifier === $this->codeChallenge;
		}
		throw new UnsupportedPkceTransformException("Code challenge method {$this->codeChallengeMethod} not supported");
	}
}
