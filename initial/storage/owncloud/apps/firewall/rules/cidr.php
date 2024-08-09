<?php
/**
 * ownCloud Firewall
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Firewall\Rules;

use OCA\Firewall\Ruler;
use OCA\Firewall\Rules\Contracts\Rule;

/**
 * @package OCA\Firewall\Rules
 */
class CIDR extends Rule {
	public const IPv4_MAX_BITS = 32;
	public const IPv6_MAX_BITS = 128;
	public const IPv4_IN_IPv6_START = '::ffff:';

	// Regex from http://stackoverflow.com/a/9744436
	public const IPv4_REGEX = '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/';
	public const IPv6_REGEX = '/^(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:(?!$)|$)|\2))(?4){5}((?4){2}|((2[0-4]|1\d|[1-9])?\d|25[0-5])(\.(?7)){3})\z/i';

	/**
	 * Return an array with the allowed operators
	 *
	 * @return string[]
	 */
	protected function getValidOperators() {
		return [Ruler::OPERATOR_EQUALS, Ruler::OPERATOR_NOT_EQUALS];
	}

	/**
	 * Decide if the given IP is IPv6
	 *
	 * @param string $ip
	 * @return bool
	 */
	protected function isIPv6($ip) {
		return (\strpos($ip, ':') !== false);
	}

	/**
	 * Decide if the given IP is IPv4
	 *
	 * @param string $ip
	 * @return bool
	 */
	protected function isIPv4($ip) {
		return !$this->isIPv6($ip);
	}

	/**
	 * @param string $ruleValue
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the value is not allowed
	 */
	public function validateRuleValue($ruleValue, $ruleId) {
		if (!\is_string($ruleValue)) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}

		if (\strpos($ruleValue, '/') === false) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}

		list($subnetIp, $bits) = \explode('/', $ruleValue);

		if ($this->isIPv4($subnetIp)) {
			$maxBits = self::IPv4_MAX_BITS;
			$regex = self::IPv4_REGEX;
		} else {
			$maxBits = self::IPv6_MAX_BITS;
			$regex = self::IPv6_REGEX;
		}

		$result = \preg_match($regex, $subnetIp);
		if (!$result) {
			throw new \OutOfBoundsException('The defined IP subnet address for rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}

		if (!\is_numeric($bits) || $bits < 1 || $bits > $maxBits) {
			throw new \OutOfBoundsException('The defined IP subnet bits for rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}
	}

	/**
	 * @param string $operator
	 * @param mixed $ruleValue
	 * @return boolean True if the check passes, false if the check fails
	 */
	public function doCheck($operator, $ruleValue) {
		return $this->performCheck($operator, $ruleValue, $this->context->getRemoteAddress());
	}

	/**
	 * @param string $operator
	 * @param string $ruleValue
	 * @param string $contextValue
	 * @return bool
	 */
	protected function performCheck($operator, $ruleValue, $contextValue) {
		if ($contextValue === '') {
			/**
			 * If the criteria value is empty, it is a local request, e.g. CLI
			 * So we return false, causing the rule to not match
			 */
			return false;
		}

		if ($this->isIPv6($ruleValue) || $this->isIPv6($contextValue)) {
			$result = $this->cidrMatchIPv6($ruleValue, $contextValue);
		} else {
			$result = $this->cidrMatchIPv4($ruleValue, $contextValue);
		}

		if ($operator === Ruler::OPERATOR_EQUALS) {
			return $result;
		}
		return !$result;
	}

	/**
	 * Matches a cidr to the requested ipv4
	 *
	 * @param string $subnetInCIDRFormat
	 * @param string $ip
	 * @return bool
	 */
	protected function cidrMatchIPv4($subnetInCIDRFormat, $ip) {
		list($subnetIp, $bits) = \explode('/', $subnetInCIDRFormat);

		$ip = \ip2long($ip);
		$subnet = \ip2long($subnetIp);
		$mask = -1 << (self::IPv4_MAX_BITS - $bits);
		$subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
		return ($ip & $mask) === $subnet;
	}

	/**
	 * Matches a cidr to the requested ipv6
	 *
	 * @param string $subnetInCIDRFormat
	 * @param string $ip
	 * @return bool
	 */
	protected function cidrMatchIPv6($subnetInCIDRFormat, $ip) {
		list($subnetIp, $bits) = \explode('/', $subnetInCIDRFormat);

		if ($this->isIPv4($subnetIp)) {
			// The rule is IPv4, but the access is from IPv6,
			// so we convert to an equivalent IPv6 rule
			// before being able to check it.
			$subnetIp = self::IPv4_IN_IPv6_START . $subnetIp;
			$bits += (self::IPv6_MAX_BITS - self::IPv4_MAX_BITS);
		}

		if ($this->isIPv4($ip)) {
			// The rule is IPv6, but the access is from IPv4, so we embed it,
			// before being able to check it.
			$ip = self::IPv4_IN_IPv6_START . $ip;
		}

		$subnetIpString = $this->convertIpToBinNumberString($subnetIp);
		$ipString = $this->convertIpToBinNumberString($ip);

		return \substr($subnetIpString, 0, $bits) === \substr($ipString, 0, $bits);
	}

	/**
	 * Converts a given IP into a binary (0|1) string
	 * @param string $ip
	 * @return string
	 */
	protected function convertIpToBinNumberString($ip) {
		$ipBin = \inet_pton($ip);
		$hexString = \bin2hex($ipBin);
		$binNumber = '';

		// Basically this is like hex2bin, but instead of melting bits to a byte
		// we keep them separated, so we can easily compare each of them.
		for ($i = 0; $length = \strlen($hexString), $i < $length; $i++) {
			$binNumber .= \sprintf('%04d', \decbin(\hexdec($hexString[$i])));
		}

		return $binNumber;
	}
}
