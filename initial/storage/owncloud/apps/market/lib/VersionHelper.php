<?php
/**
 * @author Viktar Dubiniuk <dubinuk@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\Market;

use OCP\Util;

/**
 * Class VersionHelper
 *
 * @package OCA\Market
 */
class VersionHelper {
	/**
	 * Get the current ownCloud version
	 *
	 * @param int $cutTo
	 *
	 * @return string
	 */
	public function getPlatformVersion($cutTo = null) {
		$v = Util::getVersion();
		if ($cutTo !== null) {
			$v = \array_slice($v, 0, $cutTo);
		}
		return \join('.', $v);
	}

	/**
	 * Check if both versions has the same major part
	 *
	 * @param string $first
	 * @param string $second
	 *
	 * @return bool
	 */
	public function isSameMajorVersion($first, $second) {
		$firstMajor = $this->getMajorVersion($first);
		$secondMajor = $this->getMajorVersion($second);
		return $firstMajor === $secondMajor;
	}

	/**
	 * @param string $first
	 * @param string $second
	 * @return mixed
	 */
	public function lessThanOrEqualTo($first, $second) {
		return \version_compare($first, $second, '<=');
	}

	/**
	 * Parameters will be normalized and then passed into version_compare
	 * in the same order they are specified in the method header
	 *
	 * @param string|null $first
	 * @param string|null $second
	 * @param string $operator
	 *
	 * @return bool result similar to version_compare
	 */
	public function compare($first, $second, $operator) {
		// we can't normalize versions if one of the given parameters is not a
		// version string but null. In case one parameter is null normalization
		// will therefore be skipped
		if ($first !== null && $second !== null) {
			list($first, $second) = $this->normalizeVersions($first, $second);
		}
		return \version_compare($first, $second, $operator);
	}

	/**
	 * Truncates both versions to the lowest common version, e.g.
	 * 5.1.2.3 and 5.1 will be turned into 5.1 and 5.1,
	 * 5.2.6.5 and 5.1 will be turned into 5.2 and 5.1
	 *
	 * @param string $first
	 * @param string $second
	 *
	 * @return string[] first element is the first version, second element is the
	 * second version
	 */
	private function normalizeVersions($first, $second) {
		$first = \explode('.', $first);
		$second = \explode('.', $second);

		// get both arrays to the same minimum size
		$length = \min(\count($second), \count($first));
		$first = \array_slice($first, 0, $length);
		$second = \array_slice($second, 0, $length);

		return [\implode('.', $first), \implode('.', $second)];
	}

	/**
	 * Get major version digits
	 *
	 * @param string $version
	 *
	 * @return int|null
	 */
	private function getMajorVersion($version) {
		$versionArray = \explode('.', $version);
		return isset($versionArray[0]) ? (int) $versionArray[0] : null;
	}
}
