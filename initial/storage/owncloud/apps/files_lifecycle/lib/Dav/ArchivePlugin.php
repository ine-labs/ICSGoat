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

namespace OCA\Files_Lifecycle\Dav;

use OCA\DAV\Connector\Sabre\ChecksumList;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\Node;
use OCA\Files_Lifecycle\Entity\Property;
use OCA\Files_Lifecycle\Entity\PropertyMapper;
use OCA\Files_Lifecycle\Policy\IPolicy;
use OCP\IRequest;
use OCP\IUserSession;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\Xml\Writer;

/**
 * Sabre Plugin to handle Archived Files
 *
 * Class ArchivePlugin
 *
 * @package OCA\Files_Lifecycle\Dav
 */
class ArchivePlugin extends ServerPlugin {

	// namespace
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';
	public const FILEID_PROPERTYNAME = '{http://owncloud.org/ns}id';
	public const INTERNAL_FILEID_PROPERTYNAME = '{http://owncloud.org/ns}fileid';
	public const PERMISSIONS_PROPERTYNAME = '{http://owncloud.org/ns}permissions';
	public const SIZE_PROPERTYNAME = '{http://owncloud.org/ns}size';
	public const OWNER_ID_PROPERTYNAME = '{http://owncloud.org/ns}owner-id';
	public const OWNER_DISPLAY_NAME_PROPERTYNAME
		= '{http://owncloud.org/ns}owner-display-name';
	public const CHECKSUMS_PROPERTYNAME = '{http://owncloud.org/ns}checksums';
	// The datetime the node was added to owncloud
	public const UPLOAD_TIME = '{http://owncloud.org/ns}upload-time';
	// The datetime the node was moved to the archive (most recently)
	public const ARCHIVED_TIME = '{http://owncloud.org/ns}archived-time';
	// The datetime the node was restored from the archive (most recently)
	public const RESTORED_TIME = '{http://owncloud.org/ns}restored-time';

	// Future times
	// Future time when the file will be moved to the archive
	public const ARCHIVING_TIME = '{http://owncloud.org/ns}archiving-time';
	// Future time when the file will be expired from the archive
	public const EXPIRING_TIME = '{http://owncloud.org/ns}expiring-time';

	/**
	 * @var PropertyMapper
	 */
	protected $mapper;

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * Whether this is public webdav.
	 * If true, some returned information will be stripped off.
	 *
	 * @var bool
	 */
	private $isPublic;

	/**
	 * @var IRequest
	 */
	private $request;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var IPolicy
	 */
	private $policy;

	/**
	 * ArchivePlugin constructor.
	 *
	 * @param IPolicy $policy
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param PropertyMapper $mapper
	 * @param bool $isPublic
	 */
	public function __construct(
		IPolicy $policy,
		IRequest $request,
		IUserSession $userSession,
		PropertyMapper $mapper,
		$isPublic = false
	) {
		$this->request = $request;
		$this->userSession = $userSession;
		$this->mapper = $mapper;
		$this->isPublic = $isPublic;
		$this->policy = $policy;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 *
	 * @return void
	 */
	public function initialize(Server $server) {
		$this->server = $server;
		$this->server->xml->namespaceMap[self::NS_OWNCLOUD] = 'oc';
		// protect the properties from external edits
		$this->server->protectedProperties[] = self::UPLOAD_TIME;
		$this->server->protectedProperties[] = self::ARCHIVED_TIME;
		$this->server->protectedProperties[] = self::RESTORED_TIME;
		$this->server->protectedProperties[] = self::ARCHIVING_TIME;

		if ($this->userSession === null || $this->userSession->getUser() === null) {
			return;
		}

		if (\strpos($this->server->getRequestUri(), 'archive/') !== 0) {
			$this->server->on('propFind', [$this, 'handleProperties']);
			return;
		}

		$this->server->protectedProperties[] = self::EXPIRING_TIME;

		$this->server->protectedProperties[] = self::FILEID_PROPERTYNAME;
		$this->server->protectedProperties[] = self::INTERNAL_FILEID_PROPERTYNAME;
		$this->server->protectedProperties[] = self::SIZE_PROPERTYNAME;
		$this->server->protectedProperties[] = self::OWNER_ID_PROPERTYNAME;
		$this->server->protectedProperties[] = self::OWNER_DISPLAY_NAME_PROPERTYNAME;
		$this->server->protectedProperties[] = self::CHECKSUMS_PROPERTYNAME;

		// normally these cannot be changed (RFC4918),
		// but we want them modifiable through PROPPATCH
		$allowedProperties = ['{DAV:}getetag'];
		$server->protectedProperties
			= \array_diff($server->protectedProperties, $allowedProperties);
		$this->server->xml->classMap['DateTime'] = function (
			Writer $writer,
			\DateTime $value
		) {
			$writer->write(\Sabre\HTTP\toDate($value));
		};

		$this->server->on('propFind', [$this, 'handlePropertiesArchive'], 100);
		$this->server->on('method:LOCK', [$this, 'lockForbidden'], 50);
		$this->server->on('method:UNLOCK', [$this, 'lockForbidden'], 50);
	}

	/**
	 * Handle time properties for non archived files
	 *
	 * @param PropFind $propFind
	 * @param INode $node
	 *
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 *
	 * @return void
	 */
	public function handleProperties(PropFind $propFind, INode $node) {
		if ($node instanceof File) {
			/**
			 * @var Property $uploadTimeProperty
			 */
			$uploadTimeProperty = $this->mapper->findByIdAndPropertyName(
				$node->getId(),
				self::UPLOAD_TIME
			);
			$uploadDate = false;
			if ($uploadTimeProperty !== false) {
				$uploadDate = \DateTimeImmutable::createFromFormat(
					\DateTime::ATOM,
					$uploadTimeProperty->getPropertyvalue()
				);
			}

			// get the owner and check if they are a member of an excluded group
			$owner = $node->getFileInfo()->getOwner();
			if ($this->policy->userExemptFromArchiving($owner)) {
				return;
			}

			// The file is not in the archive - add the time at which it will enter
			/**
			 * @var Property|false $restoredTimeProperty
			 */
			$restoredTimeProperty = $this->mapper->findByIdAndPropertyName(
				$node->getId(),
				self::RESTORED_TIME
			);
			if ($restoredTimeProperty !== false) {
				$restoredDate = \DateTimeImmutable::createFromFormat(
					\DateTime::ATOM,
					$restoredTimeProperty->getPropertyvalue()
				);
			} else {
				// Just to be sure that this is set in code below.
				$restoredDate = false;
			}

			$archivePeriodDays = $this->policy->getArchivePeriod();
			$archiveInterval = \DateInterval::createFromDateString(
				$archivePeriodDays . " days"
			);

			if ($uploadDate !== false) {
				// If the file was restored, use this to calculate the time till archive
				if ($restoredTimeProperty !== false && $restoredDate !== false) {
					// Set the restored time property
					$propFind->handle(
						self::RESTORED_TIME,
						function () use ($restoredDate) {
							return $restoredDate->format(\DateTime::ATOM);
						}
					);
					$archiveDate = $restoredDate->add($archiveInterval);
				} else {
					$archiveDate = $uploadDate->add($archiveInterval);
				}

				$propFind->handle(
					self::ARCHIVING_TIME,
					function () use ($archiveDate) {
						return $archiveDate->format(\DateTime::ATOM);
					}
				);
			}
		}
	}

	/**
	 * @param PropFind $propFind
	 * @param INode $node
	 *
	 * @return void
	 *
	 * @throws NotFound
	 * @throws \Exception
	 */
	public function handlePropertiesArchive(PropFind $propFind, INode $node) {
		if ($node instanceof Node) {
			if (!$node->getFileInfo()->isReadable()) {
				// avoid detecting files through this means
				throw new NotFound();
			}

			$propFind->handle(
				self::FILEID_PROPERTYNAME,
				function () use ($node) {
					return $node->getFileId();
				}
			);
			$propFind->handle(
				self::INTERNAL_FILEID_PROPERTYNAME,
				function () use ($node) {
					return $node->getInternalFileId();
				}
			);
			$propFind->handle(
				self::OWNER_ID_PROPERTYNAME,
				function () use ($node) {
					return $node->getOwner()->getUID();
				}
			);
			$propFind->handle(
				self::OWNER_DISPLAY_NAME_PROPERTYNAME,
				function () use ($node) {
					return $node->getOwner()->getDisplayName();
				}
			);
			$propFind->handle(
				self::SIZE_PROPERTYNAME,
				function () use ($node) {
					return $node->getSize();
				}
			);
		}

		// Handle special addition properties if the file is in the archive
		if ($node instanceof ArchivedFile) {
			$propFind->handle(
				self::CHECKSUMS_PROPERTYNAME,
				function () use ($node) {
					$checksum = $node->getChecksum();
					if ($checksum == null || $checksum === '') {
						return null;
					}
					return new ChecksumList($checksum);
				}
			);

			// Add the time that the file was moved to the archive
			/**
			 * @var Property $archivedTimeProperty
			 */
			$archivedTimeProperty = $this->mapper->findByIdAndPropertyName(
				$node->getId(),
				self::ARCHIVED_TIME
			);
			$archivedDate = \DateTimeImmutable::createFromFormat(
				\DateTime::ATOM,
				$archivedTimeProperty->getPropertyvalue()
			);
			$propFind->handle(
				self::ARCHIVED_TIME,
				function () use ($archivedDate) {
					return $archivedDate->format(\DateTime::ATOM);
				}
			);

			// Add the time that the file will expire
			$expirePeriodDays = $this->policy->getExpirePeriod();
			$expireInterval = \DateInterval::createFromDateString(
				$expirePeriodDays . " days"
			);
			$dateToExpire = $archivedDate->add($expireInterval);
			$propFind->handle(
				self::EXPIRING_TIME,
				function () use ($dateToExpire) {
					return $dateToExpire->format(\DateTime::ATOM);
				}
			);
		}
	}

	/**
	 * @throws BadRequest
	 *
	 * @return void
	 */
	public function lockForbidden() {
		throw new BadRequest('WebDAV Locking forbidden');
	}

	/**
	 * @param \DateTime $startTime
	 * @param \DateTime |null $endTime
	 *
	 * @return \DateInterval
	 *
	 * @throws \Exception
	 */
	public function calculateRemainingTime(
		\DateTime $startTime,
		\DateTime $endTime = null
	) {
		if (!$endTime) {
			$endTime = new \DateTime();
		}
		return $endTime->diff($startTime);
	}
}
