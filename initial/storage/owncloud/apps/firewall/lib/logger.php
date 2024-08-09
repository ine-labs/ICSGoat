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

namespace OCA\Firewall;

use OCP\ILogger;

class Logger {
	public const LOGGING_OFF = 1;
	public const LOGGING_BLOCKS = 2;
	public const LOGGING_ON = 3;

	/**
	 * @var int
	 */
	protected $logLevel;

	/**
	 * @var ILogger
	 */
	private $log;

	/**
	 * @param ILogger $log
	 * @param int $logLevel
	 */
	public function __construct(ILogger $log, $logLevel) {
		$this->log = $log;
		$this->logLevel = $logLevel;
	}

	/**
	 * Log the result when the firewall passes
	 */
	public function onPass() {
		if ($this->logLevel === self::LOGGING_ON) {
			$this->log->info('Firewall allowed request', ['app' => 'firewall']);
		}
	}

	/**
	 * Log the result when the firewall blocks a request
	 *
	 * @param string $offendingRule
	 */
	public function onBlock($offendingRule) {
		if ($this->logLevel > self::LOGGING_OFF) {
			$this->log->warning('Firewall blocked request due to a matching rule "{offender}"', [
				'app' => 'firewall',
				'offender' => $offendingRule,
			]);
		}
	}
}
