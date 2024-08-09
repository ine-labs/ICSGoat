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

namespace OCA\Workflow\Condition;

use OCA\Workflow\Condition\Rules\BaseRule;
use OCP\AppFramework\IAppContainer;

class RuleFactory {

	/** @var IAppContainer */
	protected $container;

	/** @var array */
	protected $rules = [
		'cidr'		=> 'OCA\Workflow\Condition\Rules\CIDR',
		'cidr6'		=> 'OCA\Workflow\Condition\Rules\CIDR',
		'devicetype'=> 'OCA\Workflow\Condition\Rules\DeviceType',
		'filesize'	=> 'OCA\Workflow\Condition\Rules\FileSize',
		'filetype'	=> 'OCA\Workflow\Condition\Rules\FileType',
		'regex'		=> 'OCA\Workflow\Condition\Rules\Regex',
		'request'	=> 'OCA\Workflow\Condition\Rules\Request',
		'requesturl'=> 'OCA\Workflow\Condition\Rules\RequestUrl',
		'subnet'	=> 'OCA\Workflow\Condition\Rules\SubNet',
		'subnet6'	=> 'OCA\Workflow\Condition\Rules\SubNet',
		'systemtag'	=> 'OCA\Workflow\Condition\Rules\SystemTag',
		'time'		=> 'OCA\Workflow\Condition\Rules\Time',
		'useragent'	=> 'OCA\Workflow\Condition\Rules\UserAgent',
		'usergroup'	=> 'OCA\Workflow\Condition\Rules\UserGroup',
	];

	/**
	 * @param IAppContainer $container
	 */
	public function __construct(IAppContainer $container) {
		$this->container = $container;
	}

	/**
	 * Add custom operator to the parsing engine
	 *
	 * @param string $id
	 * @return BaseRule
	 */
	public function getRuleInstance($id) {
		if (isset($this->rules[$id])) {
			return $this->container->query($this->rules[$id]);
		}

		throw new \InvalidArgumentException('Unknown workflow rule id "' . (string) $id . '"');
	}

	/**
	 * @return array
	 */
	public function getRules() {
		return \array_keys($this->rules);
	}
}
