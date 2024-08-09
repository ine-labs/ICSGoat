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

namespace OCA\windows_network_drive\lib;

use Symfony\Component\Process\Process;

/**
 * Helper class easily mockable
 */
class Helper {
	/**
	 * Check if the Symfony's Process supports pseudo-terminal
	 * @return bool true if supported, false otherwise
	 */
	public function isPtySupported() {
		return Process::isPtySupported();
	}

	/**
	 * Check if the command exists. [Requires "exec"]
	 * @param string $command the command to be checked
	 * @return bool true if exists, false otherwise
	 */
	public function checkCommandExists($command) {
		$command = \escapeshellarg($command);
		\exec("command -v $command >/dev/null 2>&1", $out, $exitCode);
		return $exitCode === 0;
	}

	/**
	 * Send the signal defined by the $signalNumber to the process with the pid $pid.
	 * [Requires "exec"]
	 * @param int $pid the pid of the process to send the signal
	 * @param int $signalNumber the signal number to send to the process. Signal 15 by default
	 */
	public function sendSignal($pid, $signalNumber = 15) {
		$pid = \escapeshellarg($pid);
		$signalNumber = \escapeshellarg(\intval($signalNumber));
		\exec("kill -${signalNumber} $pid 2>&1", $out, $exitCode);
		return [$exitCode, $out];
	}

	/**
	 * Get the children process of the $pid one. It will return the raw output of the command
	 * as string. [Requires "exec"]
	 * This will execute "ps -o pid= --ppid $pid"
	 * @param int $pid the pid of the process we want to get the children from
	 * @return string the output of the command
	 */
	public function getChildrenPids($pid) {
		$pid = \escapeshellarg($pid);
		\exec("exec ps -o pid= --ppid $pid", $out, $exitCode);
		return \implode("\n", $out);
	}

	/**
	 * Check if the $value seconds have passed since $timestamp.
	 * @param float $timestamp a "microtime(true)" timestamp, or something similar
	 * @param int|float $value the value to check
	 * @return bool true if such time has passed or false otherwise
	 */
	public function timeDifferenceGreaterThan($timestamp, $value) {
		return \microtime(true) - $timestamp > $value;
	}

	/**
	 * Do a "time_nanosleep" with the passed parameters.
	 */
	public function nanosleep($secs, $nanosecs) {
		\time_nanosleep($secs, $nanosecs);
	}

	/**
	 * Check if the function exists using the builtin method
	 * @param string $name the name of the function
	 * @return bool true if exists, false otherwise
	 */
	public function function_exists($name) {
		return \function_exists($name);
	}

	/**
	 * Get the username and the domain from a windows username like 'domain\username'
	 * If only the username part is set, the domain returned will be an empty string
	 * @param string $username a username such as 'domain\username' or 'username'
	 * @return array the first element is the domain, the second is the username.
	 */
	public function getUsernameAndDomain($username) {
		if (\strpos($username, '/') !== false) {
			$result = \explode('/', $username, 2);
		} elseif (\strpos($username, '\\') !== false) {
			$result = \explode('\\', $username, 2);
		} else {
			$result = ['', $username];
		}
		return $result;
	}
}
