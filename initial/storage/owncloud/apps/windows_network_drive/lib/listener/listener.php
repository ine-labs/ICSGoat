<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib\listener;

/**
 * Wraps a ListenerProcess (smbclient) and executes different callbacks based on its output
 * The "start" method will loop indefinitely until the underlying process (smbclient) stops or one
 * of the callbacks throw a ListenerException
 */
class Listener {
	/** @var ListenerProcess */
	private $smbclient;

	/** @var ListenerBuffer */
	private $buffer;

	/**
	 * @param ListenerProcess $process the process that will be run
	 * @param ListenerBuffer $buffer a buffer to store the lines of the ListenerProcess. A fresh one
	 * will be used if you use null.
	 */
	public function __construct(ListenerProcess $process, ListenerBuffer $buffer = null) {
		$this->smbclient = $process;
		if ($buffer === null) {
			$buffer = new ListenerBuffer();
		}
		$this->buffer = $buffer;
	}

	/**
	 * @return ListenerProcess the ListenerProcess
	 */
	public function getListenerProcess() {
		return $this->smbclient;
	}

	/**
	 * @return ListenerBuffer the ListenerBuffer
	 */
	public function getListenerBuffer() {
		return $this->buffer;
	}

	/**
	 * Check if the $line is a notification returned by the smbclient's notify call.
	 */
	private function isNotifyLine($line) {
		// check the first 4 char and check is a numeric value. 5th char must be a space
		// if there isn't a 5th char, supress the notice message (the check will fail as expected)
		return \is_numeric(\substr($line, 0, 4)) && @$line[4] === ' ';
	}

	/**
	 * Start the process and block indefinitely. Different callbacks will be executed depending on
	 * the output of the underlying process.
	 * It's highly recommended to do very light processing in the idleCallback and include a little
	 * sleep in it to prevent consuming too much CPU.
	 * @param callable $notifyCallback the callback to be executed for "notify" lines
	 * @param callable $errorCallback the callback to be executed for "non-notify" lines
	 * @param callable $idleCallback the callback to be executed while being idle
	 */
	public function start(callable $notifyCallback, callable $errorCallback, callable $idleCallback) {
		$this->smbclient->getProcess()->start();

		$this->callbackLoop($notifyCallback, $errorCallback, $idleCallback);
	}

	/**
	 * Listener loop. We'll use the ListenerBuffer to store any partial lines that the ListenerProcess
	 * could return in order to process full lines. Note that the when the buffer will store a full
	 * line (newline char stored), the line will be returned and removed from the buffer.
	 *
	 * For each cycle of the loop:
	 * 1. Fetch any new data the ListenerProcess has (via getIncrementalOutput)
	 * 2. Store that data in the buffer and return any line available
	 * 3. Foreach returned line:
	 *   3.1 If the line is a smbclient's "notify" line, call the $notifyCallback with the parameters
	 *   3.2 If the line isn't, call the $errorCallback
	 * 3. If the buffer doesn't return any line, call the $idleCallback
	 */
	private function callbackLoop(callable $notifyCallback, callable $errorCallback, callable $idleCallback) {
		$currentBuffer = $this->getListenerBuffer();
		$smbclient = $this->getListenerProcess();
		$smbclientProcess = $smbclient->getProcess();
		$stop = false;
		do {
			try {
				$stillRunning = $smbclientProcess->isRunning();
				$output = $smbclientProcess->getIncrementalOutput();
				$lines = $currentBuffer->storeAndGetLines($output, $stillRunning);
				$smbclientProcess->clearOutput();
				if (!empty($lines)) {
					foreach ($lines as $line) {
						$line = \trim($line);
						if ($line === '') {
							continue;
						}
						if ($this->isNotifyLine($line)) {
							$parts = \explode(' ', $line, 2);
							$changeType = \intval($parts[0]);
							$path = \str_replace('\\', '/', $parts[1]);
							$notifyCallback($changeType, $smbclient->getHost(), $smbclient->getShare(), $path);
						} else {
							$errorCallback($smbclient->getHost(), $smbclient->getShare(), $line);
						}
					}
				} else {
					// not enough output for a line: call the idle callback
					$idleCallback();
				}
			} catch (ListenerException $ex) {
				switch ($ex->getAction()) {
					case ListenerException::LISTENER_EXIT:
						$stop = true;
						break;
					case ListenerException::LISTENER_EXIT_DO_NOTHING:
						throw $ex;
						break;
					case ListenerException::LISTENER_EXIT_STOP_PROCESS:
						$smbclientProcess->stop(1);
						throw $ex;
						break;
				}
			}
		} while (!$stop && $stillRunning);
	}

	public function __destruct() {
		if (isset($this->smbclient)) {
			$smbclientProcess = $this->smbclient->getProcess();
			if ($smbclientProcess->isRunning()) {
				$smbclientProcess->stop(1);
			}
		}
	}
}
