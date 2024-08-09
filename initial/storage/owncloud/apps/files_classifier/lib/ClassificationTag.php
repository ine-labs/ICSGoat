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
namespace OCA\FilesClassifier;

use OCA\FilesClassifier\Model\Rule;

class ClassificationTag {
	private $rule;
	private $fileIds;

	/**
	 * ClassificationTag constructor.
	 *
	 * @param Rule $rule
	 * @param int[] $fileIds
	 */
	public function __construct(Rule $rule, array $fileIds = []) {
		$this->rule = $rule;
		$this->fileIds = $fileIds;
	}

	/**
	 * @return Rule
	 */
	public function getRule(): Rule {
		return $this->rule;
	}

	/**
	 * @return array
	 */
	public function getFileIds(): array {
		return $this->fileIds;
	}

	public function addFileId(int $id) {
		$this->fileIds[] = $id;
	}
}
