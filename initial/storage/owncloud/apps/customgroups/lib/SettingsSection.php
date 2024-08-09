<?php
/**
 * ownCloud CustomGroups
 *
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright 2017 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\CustomGroups;

use OCP\IL10N;
use OCP\Settings\ISection;

class SettingsSection implements ISection {

	/** @var IL10N  */
	protected $l;

	public function __construct(IL10N $l) {
		$this->l = $l;
	}

	public function getPriority() {
		return 10;
	}

	public function getIconName() {
		return 'customgroups';
	}

	public function getID() {
		return 'customgroups';
	}

	public function getName() {
		return $this->l->t('Custom Groups');
	}
}
