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
namespace OCA\FilesClassifier\Controller;

use OCA\FilesClassifier\Model\Rule;
use OCA\FilesClassifier\Persistence;
use OCA\FilesClassifier\Validator\RulesValidator;
use OCA\FilesClassifier\Serializer;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\SystemTag\ISystemTagManager;

class TagsController extends Controller {

	/** @var ISystemTagManager */
	private $manager;
	/** @var Serializer  */
	private $serializer;
	/** @var Persistence  */
	private $persistence;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ISystemTagManager $manager
	 * @param Serializer $serializer
	 * @param Persistence $persistence
	 */
	public function __construct($appName, IRequest $request, ISystemTagManager $manager, Serializer $serializer, Persistence $persistence) {
		parent::__construct($appName, $request);
		$this->manager = $manager;
		$this->serializer = $serializer;
		$this->persistence = $persistence;
	}

	/**
	 * @return JSONResponse
	 */
	public function listTags() {
		$result = [];
		foreach ($this->manager->getAllTags() as $tag) {
			$result[] =  [
				'id' => $tag->getId(),
				'name' => $tag->getName(),
				'isUserVisible' => $tag->isUserVisible(),
				'isUserAssignable' => $tag->isUserAssignable(),
				'isUserEditable' => $tag->isUserEditable()
			];
		}

		return new JSONResponse($result);
	}

	public function listRules() {
		$rules = $this->persistence->loadRules();
		return new JSONResponse($this->serializer->normalize($rules));
	}

	public function setRules() {
		$rules = $this->request->getParams();
		unset($rules['_route']);
		$validator = new RulesValidator();

		if (!$validator->isValid($rules)) {
			return new JSONResponse($validator->getErrors(), Http::STATUS_BAD_REQUEST);
		}

		/** @var array $objRules */
		$objRules = $this->serializer->denormalize($rules, Rule::class . '[]');
		$this->persistence->storeRules($objRules);
		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}
}
