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

use OCP\SystemTag\TagNotFoundException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use OCP\SystemTag\ISystemTagManager;

class TagExistsValidator extends ConstraintValidator {

	/** @var ISystemTagManager  */
	private $tagManager;

	public function __construct() {
		$this->tagManager = \OC::$server->getSystemTagManager();
	}

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value tag id
	 * @param Constraint $constraint The constraint for the validation
	 */
	public function validate($value, Constraint $constraint) {
		if (!\is_int($value)) {
			$this->context->addViolation("Tag id's must be integers");
			return;
		}

		try {
			$tag = $this->tagManager->getTagsByIds($value)[$value];
			//Exclude the visible tag
			if ($tag->isUserVisible() && $tag->isUserEditable() && $tag->isUserAssignable()) {
				$this->context->addViolation(
					'Only restricted or invisible or static tags can be used for classification.'
				);
			}
		} catch (TagNotFoundException $e) {
			$this->context->addViolation(
				"Tag with id $value does not exist"
			);
		}
	}
}
