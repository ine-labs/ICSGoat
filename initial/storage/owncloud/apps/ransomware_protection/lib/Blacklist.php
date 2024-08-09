<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2017 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Ransomware_Protection;

use OCP\ILogger;

class Blacklist {

	/** @var  ILogger */
	protected $logger;

	/** @var array $blacklist */
	protected $blacklist = [];

	/** @var string $blacklistPath */
	protected $blacklistPath;

	/** @var bool $loaded */
	protected $loaded = false;

	public function __construct(ILogger $logger) {
		$this->logger = $logger;

		// @TODO: hard coded for first release, final solution to be designed
		$this->blacklistPath = \dirname(__DIR__) . '/blacklist.txt.dist';
	}

	/**
	 * Collect and return Blacklist
	 *
	 * @return array
	 */
	public function getBlacklist() {
		if (!$this->loaded) {
			if (!\is_readable($this->blacklistPath)) {
				$this->logger->error(
					'Blacklist not found or not readable.',
					['app' => 'ransomware_protection']
				);
			} else {
				$handle = \fopen($this->blacklistPath, 'r');
				while (!\feof($handle)) {
					$line = \fgets($handle);
					if (\strlen(\trim($line)) !== 0) {
						$this->blacklist[] = $this->wildcard2regex($line);
					}
				}
				$this->loaded = true;
			}
		}
		return $this->blacklist;
	}

	/**
	 * Match path with blacklist patterns
	 *
	 * @param string $path
	 * @return array
	 */
	public function match($path) {
		$path = $this->stripPartFileExtension($path);
		$fileName = \pathinfo($path, PATHINFO_BASENAME);
		foreach ($this->getBlacklist() as $pattern) {
			if (\preg_match($pattern, $fileName) === 1) {
				return [
					'pattern' => $this->regex2wildcard($pattern),
					'path' => $path
				];
			}
		}
		return [];
	}

	/**
	 * Remove .part file extension and the ocTransferId from the file
	 * to get the original file name
	 *
	 * @param string $path
	 * @return string
	 */
	protected function stripPartFileExtension($path) {
		if (\pathinfo($path, PATHINFO_EXTENSION) === 'part') {
			$pos = \strrpos($path, '.', -6);
			$path = \substr($path, 0, $pos);
		}

		return $path;
	}

	/**
	 * Convert wildcard strings like '*.ext' to regex patterns like '/^.*?.ext$/'
	 *
	 * @param string $string
	 * @return string
	 */
	protected function wildcard2regex($string) {
		$pattern = \preg_quote(\trim($string), '/');
		$pattern = \str_replace(\preg_quote('*'), '.*', $pattern);
		return "/^$pattern$/";
	}

	/**
	 * Convert patterns back to wildcard strings
	 *
	 * @param string $pattern
	 * @return string
	 */
	protected function regex2wildcard($pattern) {
		$string = \substr(\trim($pattern), 2, -2);
		$string = \stripslashes($string);
		$string = \str_replace('.*', '*', $string);
		return $string;
	}
}
