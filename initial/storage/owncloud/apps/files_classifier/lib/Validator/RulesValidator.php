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
namespace OCA\FilesClassifier\Validator;

use OCA\FilesClassifier\Validator\Constraint as Ensure;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class RulesValidator {

	/** @var array  */
	private $errors = [];
	/** @var Assert\All  */
	private $constraint;

	public function __construct() {
		$this->constraint = new Assert\All(
			new Assert\Collection([
				'tagId' => [
					new Assert\Type('int'),
					new Assert\NotNull(),
					new Ensure\TagExists(),
					new Ensure\OneRulePerTag()
				],
				'isUploadAllowed' => [
					new Assert\Type('bool'),
					new Assert\NotNull()
				],
				'isLinkShareAllowed' => [
					new Assert\Type('bool'),
					new Assert\NotNull()
				],
				'xpath' => [
					new Assert\Type('string')
				],
				'value' => [
					new Assert\Type('string')
				],
				'documentIdXpath' => [
					new Assert\Type('string')
				],
				'daysUntilPasswordlessLinkSharesExpire' => [
					new Assert\Type('int'),
					new Assert\Range(['min' => 1])
				]
		]));
	}

	public function isValid(array $data) {
		$validator = Validation::createValidator();
		$errors = $validator->validate($data, $this->constraint);

		if (\count($errors) === 0) {
			return true;
		}

		/** @var ConstraintViolation $error */
		foreach ($errors as $error) {
			$this->errors[$error->getPropertyPath()] =  $error->getMessage();
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}
}
