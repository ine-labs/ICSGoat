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

namespace OCA\OAuth2;

require_once __DIR__ . '/../vendor/autoload.php';

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;

class Utilities {

	/**
	 * Generates a random string with 64 characters.
	 *
	 * @return string The random string.
	 */
	public static function generateRandom(): string {
		return \OC::$server->getSecureRandom()->generate(
			64,
			'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
		);
	}

	/**
	 * Validates a redirection URI.
	 *
	 * @param string $expected The expected redirection URI.
	 * @param string $actual The actual redirection URI.
	 * @param boolean $allowSubdomains Whether to allow subdomains.
	 *
	 * @return boolean True if the redirection URI is valid, false otherwise.
	 */
	public static function validateRedirectUri($expected, $actual, $allowSubdomains) {
		$validatePort = true;
		if (\strpos($expected, 'http://localhost:*') === 0) {
			$expected = 'http://localhost' . \substr($expected, 18);
			$validatePort = false;
		}
		try {
			$expectedUrl = new URL($expected);
			$actualUrl = new URL($actual);
			if (\strcmp($expectedUrl->protocol, $actualUrl->protocol) !== 0) {
				return false;
			}

			if ($allowSubdomains) {
				if (\strcmp($expectedUrl->hostname, $actualUrl->hostname) !== 0
					&& \strcmp($expectedUrl->hostname, \str_replace(\explode('.', $actualUrl->hostname)[0] . '.', '', $actualUrl->hostname)) !== 0
				) {
					return false;
				}
			} elseif (\strcmp($expectedUrl->hostname, $actualUrl->hostname) !== 0) {
				return false;
			}

			if ($validatePort && $expectedUrl->port !== $actualUrl->port) {
				return false;
			}

			if ($expectedUrl->pathname !== $actualUrl->pathname) {
				return false;
			}

			if ($expectedUrl->search !== $actualUrl->search) {
				return false;
			}

			return true;
		} catch (TypeError $ex) {
			return false;
		}
	}

	public static function removeWildcardPort($redirectUri): string {
		if (\strpos($redirectUri, 'http://localhost:*') === 0) {
			$redirectUri = 'http://localhost' . \substr($redirectUri, 18);
		}
		return $redirectUri;
	}

	public static function isValidUrl($redirectUri): bool {
		$redirectUri = Utilities::removeWildcardPort($redirectUri);
		return (\filter_var($redirectUri, FILTER_VALIDATE_URL) !== false);
	}

	// See https://tools.ietf.org/pdf/rfc7636.pdf#56
	public static function base64url_encode($data) {
		return \rtrim(\strtr(\base64_encode($data), '+/', '-_'), '=');
	}
}
