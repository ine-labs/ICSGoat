<?php
/**
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license OCL
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
namespace OCA\FilesClassifier\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

class TagExists extends Constraint {
	public function validatedBy() : string {
		return TagExistsValidator::class;
	}
}
