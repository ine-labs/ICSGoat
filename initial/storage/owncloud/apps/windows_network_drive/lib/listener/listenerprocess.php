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

use Symfony\Component\Process\Process;
use OCA\windows_network_drive\lib\Helper;

/**
 * Wraps a Symfony's Process in order to execute a smbclient's notify command
 */
class ListenerProcess {
	/** @var Process */
	private $smbclient
;
	private $host;

	private $share;

	private $basePath;

	/** @var Helper */
	private $helper;

	private $unbufferingMode = 'auto';

	/**
	 * Creates a Symfony's Process to run the smbclient's notify command. A fresh Helper will be
	 * created if null is used as Helper
	 * @throws ListenerException if the process can't be created properly
	 */
	public function __construct($host, $share, $username, $password, $path = '', $unbufferingMode = 'auto', Helper $helper = null) {
		if ($helper === null) {
			$helper = new Helper();
		}
		$this->helper = $helper;

		$this->unbufferingMode = $unbufferingMode;  // it needs to be setup before getting the listener process

		// create the process if possible
		$this->smbclient = $this->getListenerProcess($host, $share, $username, $password, $path);
		// store extra information
		$this->host = $host;
		$this->share = $share;
		$this->basePath = $path;
	}

	private function getListenerProcess($host, $share, $username, $password, $path = '') {
		if (!$this->helper->checkCommandExists('smbclient')) {
			throw new ListenerException('smbclient cannot be executed');
		}

		$baseCmdline = $this->getBaseSmbclientCommandLine($host, $share, $username, $path);

		$evaluatedUnbufferingMode = $this->evaluateUnbufferingMode();
		// only 'pty' and 'stdbuf' should be available. Exceptions might be thrown in the "evaluateUnbufferingMode" call
		if ($evaluatedUnbufferingMode === 'pty') {
			$command = "exec $baseCmdline";
			$process = new Process($command, null, ['PASSWD' => $password], null, null);
			$process->setPty(true);
		} else {
			$command = "exec stdbuf -oL -eL $baseCmdline";
			$process = new Process($command, null, ['PASSWD' => $password], null, null);
		}

		return $process;
	}

	private function getBaseSmbclientCommandLine($host, $share, $username, $path) {
		$components = $this->helper->getUsernameAndDomain($username);

		$hostShare = "//$host/$share";
		$notifyCommand = "notify \"$path\"";

		$domain = \escapeshellarg($components[0]);
		$username = \escapeshellarg($components[1]);
		$hostShare = \escapeshellarg($hostShare);
		$notifyCommand = \escapeshellarg($notifyCommand);

		if ($components[0] === '') {
			// empty domain, don't set the workgroup
			$cmdline = "smbclient -U $username $hostShare -c $notifyCommand";
		} else {
			$cmdline = "smbclient -U $username -W $domain $hostShare -c $notifyCommand";
		}
		return $cmdline;
	}

	/**
	 * Return the unbuffering mode to be used or throw a ListenerException if it isn't possible to get
	 * a valid value
	 */
	private function evaluateUnbufferingMode() {
		switch ($this->unbufferingMode) {
			case 'auto':
				return $this->checkAutoMode();
			case 'pty':
				return $this->checkPtyMode();
			case 'stdbuf':
				return $this->checkStdbufMode();
			default:
				throw new ListenerException('Unknown unbuffering mode');
		}
	}

	/**
	 * Return 'pty' if a pty is available, or 'stdbuf' if stdbuf is available or throw an exception
	 * if none of those options can be used. 'pty' will be prioritized over 'stdbuf'
	 * @return string 'pty' or 'stdbuf', depending on what is available, prioritizing 'pty'
	 * @throws ListenerException if no option is available
	 */
	private function checkAutoMode() {
		if ($this->helper->checkCommandExists('stdbuf')) {
			return 'stdbuf';
		} elseif ($this->helper->isPtySupported()) {
			return 'pty';
		} else {
			throw new ListenerException('Neither a pty nor stdbuf can be used to get data from the smbclient command');
		}
	}

	/**
	 * Return 'pty' if a pty is available or throw an exception if not
	 * @return string 'pty'
	 * @throws ListenerException if a pty isn't available
	 */
	private function checkPtyMode() {
		if ($this->helper->isPtySupported()) {
			return 'pty';
		} else {
			throw new ListenerException('A pty cannot be used to get data from the smbclient command');
		}
	}

	/**
	 * Return 'stdbuf' if the stdbuf command is available or throw an exception if not
	 * @return string 'stdbuf'
	 * @throws ListenerException if the stdbuf command isn't available
	 */
	private function checkStdbufMode() {
		if ($this->helper->checkCommandExists('stdbuf')) {
			return 'stdbuf';
		} else {
			throw new ListenerException('Stdbuf cannot be used to get data from the smbclient command');
		}
	}

	/**
	 * @return string the host that the smbclient is connecting to
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @return string the share that the smbclient is connecting to
	 */
	public function getShare() {
		return $this->share;
	}

	/**
	 * @return string the base directory for the notifications
	 */
	public function getBasePath() {
		return $this->basePath;
	}

	/**
	 * @return Process the smbclient process
	 */
	public function getProcess() {
		return $this->smbclient;
	}

	/**
	 * Get the unbuffering mode
	 * @return string the mode set by setUnbufferingMode
	 */
	public function getUnbufferingMode() {
		return $this->unbufferingMode;
	}
}
