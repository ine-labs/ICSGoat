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

use OC\Files\View;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\Files\FilesHome;
use OCP\Files\Mount\IMountManager;
use OCP\IUserSession;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;
use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\URI;

/**
 * Class RootCollection
 *
 * @package OCA\Files_Lifecycle\Dav
 */
class RootCollection extends AbstractPrincipalCollection {
	/**
	 * @var IUserSession $userSession
	 */
	private $userSession;
	/**
	 * @var IMountManager $mountManager
	 */
	private $mountManager;

	/**
	 * RootCollection constructor.
	 */
	public function __construct(IUserSession $session, IMountManager $mountManager) {
		$this->userSession = $session;
		$this->mountManager = $mountManager;
		$principalPrefix = 'principals/users';
		$userPrincipalBackend = new Principal(
			\OC::$server->getUserManager(),
			\OC::$server->getGroupManager()
		);
		parent::__construct($userPrincipalBackend, $principalPrefix);
	}

	/**
	 * This method returns a node for a principal.
	 *
	 * The passed array contains principal information, and is guaranteed to
	 * at least contain a uri item. Other properties may or may not be
	 * supplied by the authentication backend.
	 *
	 * @param array $principalInfo
	 *
	 * @suppress PhanParamSignatureMismatch
	 *
	 * @return INode
	 * @throws \Exception
	 */
	public function getChildForPrincipal(array $principalInfo) {
		list(, $name) = Uri\split($principalInfo['uri']);
		$user = $this->userSession->getUser();
		if ($user === null || $name !== $user->getUID()) {
			// a user is only allowed to see their own archive contents
			throw new Forbidden('You have no permission to access this resource');
		}

		$view = new View('/' . $name . '/' . $this->getName());
		$home = new FilesHome($principalInfo);
		$rootInfo = $view->getFileInfo('');
		if (!$rootInfo) {
			return new ArchiveCollection($name);
		}
		$rootNode = new ArchiveDirectory($view, $rootInfo, $home);
		$home->init($rootNode, $view, $this->mountManager);

		return $home;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'archive';
	}
}
