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
use Symfony\Component\Validator\ConstraintValidator;

class OneRulePerTagValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 */
	public function validate($value, Constraint $constraint) {
		$root = $this->context->getRoot();
		$ids = \array_column($root, 'tagId');
		if (\count(\array_unique($ids)) !== \count($ids)) {
			$this->context->addViolation("Duplicate rule for tag id $value");
		}
	}
}
