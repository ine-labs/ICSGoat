<?php
/**
 * ownCloud Firewall
 *
 * @author Clark Tomlinson
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

use OCP\IConfig;

class Config {
	/** @var IConfig */
	protected $config;

	/**
	 * Use a different config file for the firewall
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Get the list of firewall rules
	 *
	 * @param bool $decode
	 * @return string|array The rules as a JSON string or decoded as an array
	 */
	public function getRules($decode = false) {
		$rules = $this->config->getSystemValue('firewall.rules', '{}');
		/**
		 * We have to convert this to this format or our ui explodes
		 * todo: xxx fix this in x.0.1
		 */
		if ($rules === '[{}]') {
			$rules = '{}';
		}
		if ($decode) {
			return \json_decode($rules, true);
		}
		return $rules;
	}

	/**
	 * Set the list of firewall rules as a JSON string
	 *
	 * @param string $rules
	 */
	public function setRules($rules) {
		$this->assertConfigIsWritable();
		$this->config->setSystemValue('firewall.rules', $rules);
	}

	/**
	 * Sets debug level to firewall config
	 *
	 * @param int $level
	 */
	public function setDebug($level) {
		$this->assertConfigIsWritable();
		$this->config->setSystemValue('firewall.debug', (int) $level);
	}

	/**
	 * Returns current debug level in the config
	 *
	 * @return int
	 */
	public function getDebugLevel() {
		return (int) $this->config->getSystemValue('firewall.debug', 1);
	}

	/**
	 * Returns branded user agents in the config
	 *
	 * @return array
	 */
	public function getBrandedClients() {
		$config = $this->config->getSystemValue('firewall.branded_clients', []);
		if (empty($config) || !\is_array($config)) {
			return [];
		}

		return $config;
	}

	/**
	 * Blocks updating readonly config.php
	 */
	public function assertConfigIsWritable() {
		$isConfigReadOnly = $this->config->getSystemValue('config_is_read_only', false);
		if ($isConfigReadOnly === true) {
			throw new \RuntimeException("Updating config.php prohibited by the flag config_is_read_only set to true.");
		}
	}
}
