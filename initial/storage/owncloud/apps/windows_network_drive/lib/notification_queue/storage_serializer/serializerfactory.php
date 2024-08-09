<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright Copyright (c) 2017, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib\notification_queue\storage_serializer;

class SerializerFactory {
	/**
	 * Returns a serializer ready to be used
	 * @param string $serializerType the type of serializer you want to create
	 * @param array $params a list of parameters in the form ['param1=value1', 'param2=value2'].
	 * Each 'paramX' must match the name of a parameter in the target serializer's constructor.
	 * So for example, for the FileSerializer $params should be ['file=/tmp/file']
	 * @return ISerializer a serializer object
	 * @throws \InvalidArgumentException if the serializer type isn't supported or couldn't create
	 * the serializer (normally due to missing required parameters)
	 */
	public function getSerializer($serializerType, array $params) {
		$paramList = $this->convertParamListToArray($params);
		$targetClassName = '\OCA\windows_network_drive\lib\notification_queue\storage_serializer\implementations\\' . $serializerType . 'Serializer';
		$targetInterfaceName = '\OCA\windows_network_drive\lib\notification_queue\storage_serializer\implementations\ISerializer';
		if (\class_exists($targetClassName) && \is_subclass_of($targetClassName, $targetInterfaceName)) {
			return new $targetClassName($paramList);
		} else {
			throw new \InvalidArgumentException('invalid serializer type');
		}
	}

	private function convertParamListToArray($params) {
		$result = [];
		foreach ($params as $param) {
			$exploded = \explode('=', $param, 2);
			// 1 element should be guaranteed, check for the second and set null if missing
			if (isset($exploded[1])) {
				$result[$exploded[0]] = $exploded[1];
			} else {
				$result[$exploded[0]] = null;
			}
		}
		return $result;
	}
}
