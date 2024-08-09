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

use Doctrine\DBAL\Platforms\OraclePlatform;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getIdentifier()
 * @method void setIdentifier(string $identifier)
 * @method string getSecret()
 * @method void setSecret(string $secret)
 * @method string getRedirectUri()
 * @method void setRedirectUri(string $redirectUri)
 * @method string getName()
 * @method void setName(string $name)
 * @method boolean getAllowSubdomains()
 * @method boolean getTrusted()
 */
class Client extends Entity {
	protected $identifier;
	protected $secret;
	protected $redirectUri;
	protected $name;
	protected $allowSubdomains;
	protected $trusted;

	/**
	 * Client constructor.
	 */
	public function __construct() {
		$this->addType('id', 'int');
		$this->addType('identifier', 'string');
		$this->addType('secret', 'string');
		$this->addType('redirect_uri', 'string');
		$this->addType('name', 'string');
		$this->addType('allow_subdomains', 'boolean');
		$this->addType('trusted', 'boolean');
	}

	public function setAllowSubdomains(bool $value): void {
		if (\OC::$server->getDatabaseConnection()->getDatabasePlatform() instanceof OraclePlatform) {
			$this->setter('allowSubdomains', [$value ? 1 : 0]);
		} else {
			$this->setter('allowSubdomains', [$value]);
		}
	}

	public function setTrusted(bool $value): void {
		if (\OC::$server->getDatabaseConnection()->getDatabasePlatform() instanceof OraclePlatform) {
			$this->setter('trusted', [$value ? 1 : 0]);
		} else {
			$this->setter('trusted', [$value]);
		}
	}
}
