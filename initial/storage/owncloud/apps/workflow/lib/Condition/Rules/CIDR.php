<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2015 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Condition\Rules;

use OCA\Workflow\Condition\Operators;

class CIDR extends BaseRule {
	public const IPv4_MAX_BITS = 32;
	public const IPv6_MAX_BITS = 128;

	// Regex from http://stackoverflow.com/a/9744436
	public const IPv4_REGEX = '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/';
	public const IPv6_REGEX = '/^(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:(?!$)|$)|\2))(?4){5}((?4){2}|((2[0-4]|1\d|[1-9])?\d|25[0-5])(\.(?7)){3})\z/i';

	/**
	 * Return an array with the allowed operators
	 *
	 * @return string[]
	 */
	protected function getValidOperators() {
		return [Operators::OPERATOR_EQUALS, Operators::OPERATOR_NOT_EQUALS];
	}

	/**
	 * @param mixed $ruleValue
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

		// If the subnet contains a colon, it's a IPv6 subnet.
		if (\strpos($subnetIp, ':') === false) {
			// IPv4
			$maxBits = self::IPv4_MAX_BITS;
			$regex = self::IPv4_REGEX;
		} else {
			// IPv6
			$maxBits = self::IPv6_MAX_BITS;
			$regex = self::IPv6_REGEX;
		}

		$result = \preg_match($regex, $subnetIp);
		if (!$result) {
			throw new \OutOfBoundsException('The defined IP for rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}

		if (!\is_numeric($bits) || $bits < 1 || $bits > $maxBits) {
			throw new \OutOfBoundsException('The defined Client IP Subnet for rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
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

		// If the subnet contains a colon, it's a IPv6 subnet.
		if (\strpos($ruleValue, ':') === false) {
			// IPv6 in context can not be converted to IPv4, so it doesn't match
			if (\strpos($contextValue, ':') !== false) {
				return false;
			}

			$result = $this->cidrMatch($ruleValue, $contextValue);
		} else {
			$result = $this->cidrMatchIPv6($ruleValue, $contextValue);
		}

		if ($operator === Operators::OPERATOR_EQUALS) {
			return $result;
		}
		return !$result;
	}

	/**
	 * Matches a cidr to the requested ip
	 *
	 * @param string $subnetInCIDRFormat
	 * @param string $ip
	 * @return bool
	 */
	protected function cidrMatch($subnetInCIDRFormat, $ip) {
		list($subnetIp, $bits) = \explode('/', $subnetInCIDRFormat);

		$ip = \ip2long($ip);
		$subnet = \ip2long($subnetIp);
		$mask = -1 << (32 - (int)$bits);
		$subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
		return ($ip & $mask) === $subnet;
	}

	/**
	 * Matches a cidr to the requested ip
	 *
	 * @param string $subnetInCIDRFormat
	 * @param string $ip
	 * @return bool
	 */
	protected function cidrMatchIPv6($subnetInCIDRFormat, $ip) {
		list($subnetIp, $bits) = \explode('/', $subnetInCIDRFormat);

		if (\strpos($ip, ':') === false) {
			// The rule is IPv6, but the current one is IPv4, so we embed it,
			// before being able to check it.
			$ip = '::ffff:' . $ip;
		}

		$subnetIpString = $this->convertIpToBinNumberString($subnetIp);
		$ipString = $this->convertIpToBinNumberString($ip);

		return \substr($subnetIpString, 0, (int)$bits) === \substr($ipString, 0, (int)$bits);
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
