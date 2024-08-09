<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright Copyright (c) 2016, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib\custom_loggers;

use OCP\ILogger;
use OCP\IConfig;

/**
 * The class will wrap any ILogger implementation to conditionally log in that logger. If the
 * condition isn't fulfilled, the log will return false and do nothing.
 */
class WNDConditionalLogger implements ILogger {
	protected $logger;
	private $config;

	public function __construct(ILogger $logger, IConfig $config) {
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * @return bool true if we should log or false otherwise
	 */
	protected function shouldLog() {
		return $this->config->getSystemValue('wnd.logging.enable', false) === true;
	}

	public function emergency($message, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->emergency($message, $context);
		} else {
			return false;
		}
	}

	public function alert($message, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->alert($message, $context);
		} else {
			return false;
		}
	}

	public function critical($message, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->critical($message, $context);
		} else {
			return false;
		}
	}

	public function error($message, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->error($message, $context);
		} else {
			return false;
		}
	}

	public function warning($message, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->warning($message, $context);
		} else {
			return false;
		}
	}

	public function notice($message, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->notice($message, $context);
		} else {
			return false;
		}
	}

	public function info($message, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->info($message, $context);
		} else {
			return false;
		}
	}

	public function debug($message, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->debug($message, $context);
		} else {
			return false;
		}
	}

	public function log($level, $message, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->log($level, $message, $context);
		} else {
			return false;
		}
	}

	public function logException($exception, array $context = []) {
		if ($this->shouldLog()) {
			$this->logger->logException($exception, $context);
		} else {
			return false;
		}
	}
}
