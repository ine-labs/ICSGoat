<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle\Entity;

use OCP\AppFramework\Db\Entity;

/**
 * Class Property
 *
 * @package OCA\Files_Lifecycle\Entity
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getFileid()
 * @method void setFileid(int $fileid)
 * @method string getPropertyname()
 * @method void setPropertyname(string $propertyname)
 * @method string getPropertyvalue()
 * @method void setPropertyvalue(string $propertyvalue)
 * @method int getPropertytype()
 * @method void setPropertytype(int $propertytype)
 */
class Property extends Entity {
	public const DAV_PROPERTY_TYPE_STRING = 1;

	protected $fileid;
	protected $propertyname;
	protected $propertyvalue;
	protected $propertytype = self::DAV_PROPERTY_TYPE_STRING;
}
