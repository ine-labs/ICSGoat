<?php
/**
 * ownCloud Firewall
 *
 * @author Tom Needham <tom@owncloud.com>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Firewall;

use OCP\IL10N;
use OCP\Settings\ISection;

class AdminSection implements ISection {

	/** @var IL10N  */
	protected $l;

	public function __construct(IL10N $l) {
		$this->l = $l;
	}

	public function getPriority() {
		return 20;
	}

	public function getIconName() {
		return 'password';
	}

	public function getID() {
		return 'firewall';
	}

	public function getName() {
		return $this->l->t('Firewall');
	}
}
