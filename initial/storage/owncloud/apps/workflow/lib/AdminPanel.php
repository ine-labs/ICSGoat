<?php

/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Tom Needham <tom@owncloud.com>
 * @copyright 2017 ownCloud, GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow;

use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Template;

class AdminPanel implements ISettings {

	/** @var IConfig  */
	protected $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getSectionID() {
		return 'workflow';
	}

	public function getPanel() {
		$tmpl = new Template('workflow', 'admin-settings');
		$tmpl->assign('retention_engine', $this->config->getSystemValue('workflow.retention_engine', 'tagbased'));
		$tmpl->assign('folder_retention', $this->config->getAppValue('workflow', 'folder_retention', 0));
		$tmpl->assign('folder_retention_period', $this->config->getAppValue('workflow', 'folder_retention_period', 7));
		return $tmpl;
	}

	public function getPriority() {
		return 10;
	}
}
