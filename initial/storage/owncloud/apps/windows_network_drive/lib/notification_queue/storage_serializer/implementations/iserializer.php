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

namespace OCA\windows_network_drive\lib\notification_queue\storage_serializer\implementations;

/**
 * Interface to serialize storages. The implementations should set the data source (a file for
 * example) in their constructor.
 * Both methods are expected to interact with the data source set in the constructor to write
 * the data to, or read the data from it.
 *
 * Implementations are expected to have a constructor accepting an array of parameter such as
 * ['key1' => 'value1', 'key2' => 'value2']. They should be able to create an instance with
 * those parameters. If any required parameter is missing and the object can't be created,
 * implementation should throw an \InvalidArgumentException in the constructor. Note that there
 * shouldn't be any connection being made to any data source in the constructor.
 */
interface ISerializer {
	/**
	 * Write the list of storages in the data source
	 * @param \OCA\windows_network_drive\lib\WND[] $storages list of storages to be serialized
	 * @param array $context an additional context that will be provided. This context can be used
	 * to provide some kind of safety mechanisms, verification, namespace, etc. For now the context
	 * will have only the host and share for those storages, such as
	 * ['host' => 'myhost', 'share' => 'myshare']
	 * After writing with a context, reading in the same data source with the same context should
	 * return the written storages. However, if the context is different it should either returns a
	 * different result (different namespace), or returns a exception (context mismatch)
	 * @throws DataSourceNotAvailableException if we can't access to the data source
	 * @throws WriteException if there was any error while writing the storages
	 * to write
	 */
	public function writeStorages(array $storages, array $context);

	/**
	 * Read the storages from the data source
	 * @param boolean $strict whether we can ignore faulty mounts: false to ignore, true to throw
	 * exceptions
	 * @param array $context an additional context that will be provided. This context can be used
	 * to provide some kind of safety mechanisms, verification, namespace, etc. For now the context
	 * will have only the host and share for those storages, such as
	 * ['host' => 'myhost', 'share' => 'myshare']
	 * After writing with a context, reading in the same data source with the same context should
	 * return the written storages. However, if the context is different it should either returns a
	 * different result (different namespace), or returns a exception (context mismatch)
	 * @throws DataSourceNotAvailableException if we can't access in the data source
	 * @throws ReadException if there was any error while reading the storages
	 * @return array the list of storages ready to be used. The keys in the array will be the
	 * storage's id, the values will be the actual storages, something like
	 * ['wnd::server/share//root' => WND1]
	 */
	public function readStorages(array $context);

	/**
	 * Clear all the storages that are in the data source. You can use this method if there are errors
	 * reading or writing in the data source in order to clean up any data in the data source that
	 * could be causing the issue
	 * @param array $context an additional context that will be provided. This context can be used
	 * to provide some kind of safety mechanisms, verification, namespace, etc. For now the context
	 * will have only the host and share for those storages, such as
	 * ['host' => 'myhost', 'share' => 'myshare']
	 * After writing with a context, reading in the same data source with the same context should
	 * return the written storages. However, if the context is different it should either returns a
	 * different result (different namespace), or returns a exception (context mismatch)
	 * @throws DataSourceNotAvailableException if we can't access in the data source
	 */
	public function clearStorages(array $context);
}
