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

use OCA\Firewall\Rules\Contracts\Rule;
use OCP\IContainer;

class RuleFactory {

	/** @var IContainer */
	protected $container;

	/** @var array */
	protected $rules = [
		'cidr'		=> 'OCA\Firewall\Rules\CIDR',
		'cidr6'		=> 'OCA\Firewall\Rules\CIDR',
		'deviceType'	=> 'OCA\Firewall\Rules\DeviceType',
		'filetype'	=> 'OCA\Firewall\Rules\FileType',
		'regex'		=> 'OCA\Firewall\Rules\Regex',
		'request'	=> 'OCA\Firewall\Rules\Request',
		'request-url'	=> 'OCA\Firewall\Rules\RequestUrl',
		'sizeup'	=> 'OCA\Firewall\Rules\FileSize',
		'systemTag'	=> 'OCA\Firewall\Rules\SystemTag',
		'time'		=> 'OCA\Firewall\Rules\Time',
		'userAgent'	=> 'OCA\Firewall\Rules\UserAgent',
		'userGroup'	=> 'OCA\Firewall\Rules\UserGroup',
	];

	/**
	 * @param IContainer $container
	 */
	public function __construct(IContainer $container) {
		$this->container = $container;
	}

	/**
	 * Add custom operator to the parsing engine
	 *
	 * @param string $id
	 * @return Rule
	 */
	public function getRuleInstance($id) {
		if (isset($this->rules[$id])) {
			return $this->container->query($this->rules[$id]);
		}

		throw new \InvalidArgumentException('Unknown firewall rule id "' . (string) $id . '"');
	}

	/**
	 * @return array
	 */
	public function getRules() {
		return \array_keys($this->rules);
	}
}
