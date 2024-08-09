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
namespace OCA\FilesClassifier\Model;

class Rule {

	/** @var int */
	private $tagId;
	/** @var string|null */
	private $xpath;
	/** @var string|null */
	private $value;
	/** @var string|null */
	private $documentIdXpath;
	/** @var boolean|null */
	private $isUploadAllowed;
	/** @var boolean|null */
	private $isLinkShareAllowed;
	/** @var int|null */
	private $daysUntilPasswordlessLinkSharesExpire;

	/**
	 * @return int
	 */
	public function getTagId() {
		return $this->tagId;
	}

	/**
	 * @param int $tagId
	 * @return Rule
	 */
	public function setTagId($tagId): Rule {
		$this->tagId = $tagId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getXpath() {
		return $this->xpath;
	}

	/**
	 * @param string $xpath
	 * @return Rule
	 */
	public function setXpath($xpath): Rule {
		$this->xpath = $xpath;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @param string $value
	 * @return Rule
	 */
	public function setValue($value): Rule {
		$this->value = $value;
		return $this;
	}

	public function hasClassificationQuery() : bool {
		return $this->xpath !== null && $this->value !== null;
	}

	/**
	 * @return string|null
	 */
	public function getDocumentIdXpath() {
		return $this->documentIdXpath;
	}

	/**
	 * @param string|null $documentIdXpath
	 * @return Rule
	 */
	public function setDocumentIdXpath($documentIdXpath): Rule {
		$this->documentIdXpath = $documentIdXpath;
		return $this;
	}

	public function hasDocumentIdQuery() :  bool {
		return $this->documentIdXpath !== null;
	}

	/**
	 * @return bool|null
	 */
	public function getIsUploadAllowed() {
		return $this->isUploadAllowed;
	}

	/**
	 * @param bool $isUploadAllowed
	 * @return Rule
	 */
	public function setIsUploadAllowed($isUploadAllowed): Rule {
		$this->isUploadAllowed = $isUploadAllowed;
		return $this;
	}

	/**
	 * @return bool|null
	 */
	public function getIsLinkShareAllowed() {
		return $this->isLinkShareAllowed;
	}

	/**
	 * @param bool $isLinkShareAllowed
	 * @return Rule
	 */
	public function setIsLinkShareAllowed($isLinkShareAllowed): Rule {
		$this->isLinkShareAllowed = $isLinkShareAllowed;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getDaysUntilPasswordlessLinkSharesExpire() {
		return $this->daysUntilPasswordlessLinkSharesExpire;
	}

	/**
	 * @param int $daysUntilPasswordlessLinkSharesExpire
	 * @return Rule
	 */
	public function setDaysUntilPasswordlessLinkSharesExpire($daysUntilPasswordlessLinkSharesExpire): Rule {
		$this->daysUntilPasswordlessLinkSharesExpire = $daysUntilPasswordlessLinkSharesExpire;
		return $this;
	}
}
