<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright (C) 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;

class Logger {

	/** @var ILogger */
	protected $logger;
	/** @var IRequest */
	protected $request;
	/** @var IUserSession */
	protected $session;
	/** @var ISystemTagManager */
	protected $tagManager;
	/** @var ISystemTagObjectMapper */
	protected $tagMapper;
	/** @var IConfig  */
	protected $config;
	/** @var IUserManager  */
	protected $userManager;
	/** @var IGroupManager  */
	protected $groupManager;
	/** @var Helper  */
	protected $helper;

	/**
	 * Logger constructor.
	 *
	 * @param ILogger $logger
	 * @param IRequest $request
	 * @param IUserSession $session
	 * @param ISystemTagManager $tagManager
	 * @param ISystemTagObjectMapper $tagMapper
	 */
	public function __construct(
		ILogger $logger,
		IRequest $request,
		IUserSession $session,
		ISystemTagManager $tagManager,
		ISystemTagObjectMapper $tagMapper,
		IConfig $config,
		IUserManager $userManager,
		IGroupManager $groupManager,
		Helper $helper
	) {
		$this->logger = $logger;
		$this->request = $request;
		$this->session = $session;
		$this->tagManager = $tagManager;
		$this->tagMapper = $tagMapper;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->helper = $helper;
	}

	/**
	 * @return null|IUser
	 */
	public function getSessionUser() {
		return $this->session->getUser();
	}

	/**
	 * @return string
	 */
	public function getUserOrIp() {
		$user = $this->getSessionUser();
		if ($user instanceof IUser) {
			return \sprintf('user <%s>, displayName <%s>', $user->getUID(), $user->getDisplayName());
		} elseif ($this->request->getRemoteAddress() !== '') {
			return 'IP ' . $this->request->getRemoteAddress();
		} elseif ($_SERVER['SCRIPT_NAME'] === 'cron.php') {
			return 'cron';
		} elseif (\OC::$CLI) {
			return 'CLI';
		} else {
			return 'unknown';
		}
	}

	/**
	 * @param string|int $fileId
	 * @param string[] $includeTags
	 * @param string[] $excludeTags
	 * @return string
	 */
	public function getTagStringForFile($fileId, array $includeTags = [], array $excludeTags = []) {
		$mapping = $this->tagMapper->getTagIdsForObjects($fileId, 'files');

		if (empty($mapping[$fileId]) && empty($includeTags)) {
			// No tags
			return '';
		}

		$tagIds = $mapping[$fileId];
		if (!empty($excludeTags)) {
			$tagIds = \array_diff($tagIds, $excludeTags);
		}
		if (!empty($includeTags)) {
			$tagIds = \array_merge($tagIds, $includeTags);
		}

		if (empty($tagIds)) {
			// No tags left
			return '';
		}

		try {
			$tags = $this->tagManager->getTagsByIds($tagIds);
		} catch (TagNotFoundException $e) {
			try {
				// Ignore missing tags
				$tags = $this->tagManager->getTagsByIds(\array_diff($tagIds, $e->getMissingTags()));
			} catch (TagNotFoundException $e) {
				// Again an error, ignore and go back...
				return '';
			}
		}

		$getTagAndPermission = \array_map(function (ISystemTag $tag) {
			$tagAndPermission = \json_decode($this->prepareTagAsParameter($tag), true);
			return \implode('" => "', $tagAndPermission);
		}, $tags);
		return \json_encode(' ["' . \implode('", "', $getTagAndPermission) . '"]');
	}

	/**
	 * @param ISystemTag $tag
	 * @return string JSON-encoded array first index is tag name and second is the type of tag
	 */
	public function prepareTagAsParameter(ISystemTag $tag) {
		if (!$tag->isUserVisible()) {
			return \json_encode([$tag->getName(), 'invisible']);
		} elseif (!$tag->isUserAssignable() && !$tag->isUserEditable()) {
			return \json_encode([$tag->getName(), 'restricted']);
		} elseif ($tag->isUserAssignable() && !$tag->isUserEditable()) {
			return \json_encode([$tag->getName(), 'static']);
		} else {
			return \json_encode([$tag->getName(), 'visible']);
		}
	}

	/**
	 * @param string $message
	 * @param array $parameters
	 * @param array $extraFields
	 */
	public function log($message, array $parameters = [], array $extraFields = []) {

		// Check if we should ignore all CLI created entries
		if ($this->config->getAppValue('admin_audit', 'ignore_cli_events', 'no') === 'yes'
		&& \OC::$CLI === true) {
			return;
		}

		// Add bool value if request was triggered from CLI
		$extraFields['CLI'] = \OC::$CLI;

		$userAgent = $this->request->getHeader('USER_AGENT');
		if ($userAgent === null) {
			$userAgent = 'unknown';
		}

		// Include UA in dedicated field
		$extraFields['userAgent'] = $userAgent;

		// Enhance with correct audit contexts
		$extraFields = $this->helper->handleAuditContext($extraFields);

		$orignalUser = '';
		if (\OCP\App::isEnabled('impersonate')) {
			$orignalUser = \OC::$server->getSession()->get('impersonator');
			if ($orignalUser !== null) {
				$extraFields['impersonator'] = $orignalUser;
				$orignalUser = $this->getUserOrIp() . " ( impersonated by " . $orignalUser . " )";
			}
		}

		if ($orignalUser === '' or $orignalUser === null) {
			$orignalUser = $this->getUserOrIp();
		}

		$extraFields['actor'] = $orignalUser;
		$this->logger->info($message, \array_merge([
			'ip'	=> $this->request->getRemoteAddress(),
			'ua'	=> $userAgent,
			'actor'	=> $orignalUser,
			// FIXME: change back once the formatted messages are streamlined
			'app'	=> 'admin_audit',
			'extraFields' => $extraFields
		], $parameters));
	}

	public function getLogger() {
		return $this->logger;
	}
}
