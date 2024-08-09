<?php
/**
 *
 * @author Vincent Petry <pvince81@owncloud.com>
 * @copyright 2018 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;

use OCA\Admin_Audit\Logger;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;

class Log {
	/** @var Logger */
	protected $logger;

	/**
	 * Listener constructor.
	 *
	 * @param Logger $logger
	 */
	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param GenericEvent $event event
	 */
	public function logEvent(GenericEvent $event) {
		$message = $event->getArgument('formattedMessage');
		$context = $event->getArgument('context');
		$extraFields = $event->getArgument('extraFields');
		if (empty($extraFields)) {
			$extraFields = [];
		}
		$extraFields = \array_merge($extraFields, $this->extractInfo($message));

		if (!isset($context['app']) || $context['app'] === 'PHP') {
			// skip PHP warnings to avoid infinite loops
			return;
		}

		if (isset($context['recipients'], $context['subject'])) {
			// mail entry must be logged as well
			$extraFields['from'] = \json_decode($context['from']);
			$extraFields['recipients'] = \json_decode($context['recipients']);
			$extraFields['subject'] = $context['subject'];
		}

		// log either if this was an audit message or if a non-audit message
		// was parsed and has extra fields
		if (!empty($extraFields) || $context['app'] === 'admin_audit_raw') {
			if (isset($context['actor'])) {
				$extraFields['actor'] = $context['actor'];
			}
			$context['extraFields'] = $extraFields;
			// Redirect messages to a different app name for easier filtering
			$context['app'] = 'admin_audit';
			// log with original logger
			$this->logger->getLogger()->info($message, $context);
		}
	}

	/**
	 * Extracts useful information from known log messages
	 * and returns an array of extra JSON fields to log
	 *
	 * @param string $message
	 * @return array extra fields
	 */
	private function extractInfo(&$message) {
		$entry = [];

		// convoluted way to extract information from the message string.
		// START //
		// fileid
		\preg_match("/ \[fid=(\d+)\]| with fileid: (\d+)/", $message, $matches);
		if ($matches) {
			if (isset($matches[2])) {
				$fileId = \strval($matches[2]);
			} else {
				$fileId = \strval($matches[1]);
			}
			$entry[] = 'fileId';
		}
		$message = \preg_replace("/ \[fid=(\d+)\]| with fileid: (\d+)/", "", $message);

		\preg_match("/Login failed: \'(.*?)\' \(Remote/", $message, $matches);
		if ($matches) {
			$action  = "user_login";
			$entry[] = 'action';
			$login   = $matches[1];
			$entry[] = 'login';
			$success = false;
			$entry[] = 'success';
		}
		// unset/set tag.
		\preg_match("/(unassigned|assigned) system tag \"(.*?)\" \"(.*?)\" (to|from) \"(.*?)\"/", $message, $matches);
		if ($matches) {
			$action = "tag_".$matches[1];
			$entry[] = 'action';
			$tagName = $matches[2];
			$entry[] = 'tagName';
			$tagPermission = $matches[3];
			$entry[] = 'tagPermission';
			$path = $matches[5];
			$entry[] = 'path';
			// Try to get the file id
			try {
				$fileId = (string) \OC::$server->getRootFolder()->get($path)->getId();
				$entry[] = 'fileId';
			} catch (NotFoundException $e) {
				// Ignore
			} catch (InvalidPathException $e) {
				// Ignore
			}
		}
		\preg_match("/(\S+) updated user preference of user \"(\S+)\" of app \"(\S+)\" \"(\S+)\" with \"(.*?)\"/", $message, $matches);
		if ($matches) {
			$action = "user_preference_set";
			$entry[] = 'action';
			$targetUser = $matches[2];
			$entry[] = 'targetUser';
			$settingName = $matches[3] . '.' . $matches[4];
			$entry[] = 'settingName';
			$settingValue = $matches[5];
			$entry[] = 'settingValue';
		}

		// END //

		return \compact($entry);
	}
}
