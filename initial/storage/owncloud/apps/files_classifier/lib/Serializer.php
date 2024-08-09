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

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class Serializer {

	/** @var \Symfony\Component\Serializer\Serializer  */
	private $serializer;

	public function __construct() {
		$encoders = [new JsonEncoder()];
		$objectNormalizer = new ObjectNormalizer(null, null, null, new PhpDocExtractor());
		$objectNormalizer->setIgnoredAttributes(['classificationQuery', 'documentIdQuery']);
		$normalizers = [
			new ArrayDenormalizer(),
			$objectNormalizer

		];

		$this->serializer = new \Symfony\Component\Serializer\Serializer($normalizers, $encoders);
	}

	public function normalize($data) {
		return $this->serializer->normalize($data, 'json');
	}

	public function denormalize($data, $type) {
		return $this->serializer->denormalize($data, $type, 'json');
	}

	public function serialize($data) {
		return $this->serializer->serialize($data, 'json');
	}

	public function deserialize($data, $type) {
		return $this->serializer->deserialize($data, $type, 'json');
	}
}
