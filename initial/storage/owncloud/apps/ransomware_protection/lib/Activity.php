<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2017 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Ransomware_Protection;

use OC\L10N\Factory;
use OCP\Activity\IExtension;
use OCP\IURLGenerator;

class Activity implements IExtension {
	public const APP_NAME = 'ransomware_protection';

	/**
	 * Filter with all app related activities
	 */
	public const FILTER_RANSOMWARE_PROTECTION = 'ransomware_protection';

	/**
	 * Activity types known to this extension
	 */
	public const TYPE_BLOCKED = 'blocked';
	public const TYPE_LOCKED = 'locked';
	public const TYPE_UNLOCKED = 'unlocked';

	/**
	 * Subject keys for translation of the subjections
	 */
	public const SUBJECT_BLOCKED = 'blocked';
	public const SUBJECT_LOCKED = 'locked';
	public const SUBJECT_UNLOCKED = 'unlocked';

	/** @var Factory */
	protected $languageFactory;

	/** @var IURLGenerator */
	protected $URLGenerator;

	/**
	 * @param Factory $languageFactory
	 * @param IURLGenerator $URLGenerator
	 */
	public function __construct(Factory $languageFactory, IURLGenerator $URLGenerator) {
		$this->languageFactory = $languageFactory;
		$this->URLGenerator = $URLGenerator;
	}

	protected function getL10N($languageCode = null) {
		return $this->languageFactory->get(self::APP_NAME, $languageCode);
	}

	/**
	 * The extension can return an array of additional notification types.
	 * If no additional types are to be added false is to be returned
	 *
	 * @param string $languageCode
	 * @return array|false
	 */
	public function getNotificationTypes($languageCode) {
		$l = $this->getL10N($languageCode);

		return [
				self::TYPE_BLOCKED => (string) $l->t('File was <strong>blocked</strong> by Ransomware Protection app.'),
				self::TYPE_LOCKED => (string) $l->t('Write access <strong>locked</strong> by Ransomware Protection app.'),
				self::TYPE_UNLOCKED => (string) $l->t('Write access <strong>unlocked</strong> by Ransomware Protection app.'),
		];
	}

	/**
	 * For a given method additional types to be displayed in the settings can be returned.
	 * In case no additional types are to be added false is to be returned.
	 *
	 * @param string $method
	 * @return array|false
	 */
	public function getDefaultTypes($method) {
		$defaultTypes = [
			self::TYPE_BLOCKED,
			self::TYPE_LOCKED,
			self::TYPE_UNLOCKED,
		];

		return $defaultTypes;
	}

	/**
	 * A string naming the css class for the icon to be used can be returned.
	 * If no icon is known for the given type false is to be returned.
	 *
	 * @param string $type
	 * @return string|false
	 */
	public function getTypeIcon($type) {
		switch ($type) {
			case self::TYPE_BLOCKED:
				return 'icon-password';
			case self::TYPE_LOCKED:
				return 'icon-password';
			case self::TYPE_UNLOCKED:
				return 'icon-password';
		}
		return false;
	}

	/**
	 * The extension can translate a given message to the requested languages.
	 * If no translation is available false is to be returned.
	 *
	 * @param string $app
	 * @param string $text
	 * @param array $params
	 * @param bool $stripPath
	 * @param bool $highlightParams
	 * @param string $languageCode
	 * @return string|false
	 */
	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		$l = $this->getL10N($languageCode);

		if ($app === self::APP_NAME) {
			switch ($text) {
				case self::SUBJECT_BLOCKED:
					return (string) $l->t('File %2$s was blocked by Ransomware Protection app. Found pattern %1$s', $params);
				case self::SUBJECT_LOCKED:
					if (\count($params) === 2) {
						return (string) $l->t('Account locked by Ransomware Protection app. Found pattern %1$s for path %2$s', $params);
					} else {
						return (string) $l->t('Account locked by Ransomware Protection app. %1$s', $params);
					}
					// no break
				case self::SUBJECT_UNLOCKED:
					return (string) $l->t('Account unlocked: %1$s', $params);
			}
		}

		return false;
	}

	/**
	 * The extension can define the type of parameters for translation
	 *
	 * Currently known types are:
	 * * file		=> will strip away the path of the file and add a tooltip with it
	 * * username	=> will add the avatar of the user
	 *
	 * @param string $app
	 * @param string $text
	 * @return array|false
	 */
	public function getSpecialParameterList($app, $text) {
		return false;
	}

	/**
	 * The extension can define the parameter grouping by returning the index as integer.
	 * In case no grouping is required false is to be returned.
	 *
	 * @param array $activity
	 * @return integer|false
	 */
	public function getGroupParameter($activity) {
		return false;
	}

	/**
	 * The extension can define additional navigation entries. The array returned has to contain two keys 'top'
	 * and 'apps' which hold arrays with the relevant entries.
	 * If no further entries are to be added false is no be returned.
	 *
	 * @return array|false
	 */
	public function getNavigation() {
		$l = $this->getL10N();
		return [
			'apps' => [
				self::FILTER_RANSOMWARE_PROTECTION => [
					'id' => self::FILTER_RANSOMWARE_PROTECTION,
					'name' => (string) $l->t('Ransomware Protection'),
					'url' => $this->URLGenerator->linkToRoute('activity.Activities.showList', ['filter' => self::FILTER_RANSOMWARE_PROTECTION]),
				],
			],
			'top' => []
		];
	}

	/**
	 * The extension can check if a customer filter (given by a query string like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 * @return bool
	 */
	public function isFilterValid($filterValue) {
		return $filterValue === self::FILTER_RANSOMWARE_PROTECTION;
	}

	/**
	 * The extension can filter the types based on the filter if required.
	 * In case no filter is to be applied false is to be returned unchanged.
	 *
	 * @param array $types
	 * @param string $filter
	 * @return array|false
	 */
	public function filterNotificationTypes($types, $filter) {
		return $filter === self::FILTER_RANSOMWARE_PROTECTION
			? [self::TYPE_BLOCKED, self::TYPE_LOCKED, self::TYPE_UNLOCKED]
			: $types;
	}

	/**
	 * For a given filter the extension can specify the sql query conditions including parameters for that query.
	 * In case the extension does not know the filter false is to be returned.
	 * The query condition and the parameters are to be returned as array with two elements.
	 * E.g. return array('`app` = ? and `message` like ?', array('mail', 'ownCloud%'));
	 *
	 * @param string $filter
	 * @return array|false
	 */
	public function getQueryForFilter($filter) {
		if ($filter === self::FILTER_RANSOMWARE_PROTECTION) {
			return [
				'(`app` = ?)',
				[self::APP_NAME],
			];
		}
		return false;
	}

	/**
	 * Publish a Locker/Blocker event to Activity Manager
	 *
	 * @param string $type
	 * @param string $affected_user
	 * @param string|array $params (optional)
	 */
	public static function publishBlockerEvent($type, $affected_user, $params = []) {
		if (
				$type !== self::TYPE_BLOCKED &&
				$type !== self::TYPE_LOCKED &&
				$type !== self::TYPE_UNLOCKED
			) {
			return;
		}
		$subject = \constant('self::SUBJECT_' . \strtoupper($type));
		$event = \OC::$server->getActivityManager()->generateEvent();
		$event->setApp(self::APP_NAME)
			->setType($type)
			->setAffectedUser($affected_user);
		if (isset($params['pattern'], $params['path'])) {
			$event->setSubject($subject, [$params['pattern'], $params['path']]);
		} elseif (\is_string($params)) {
			$event->setSubject($subject, [$params]);
		} else {
			$event->setSubject($subject);
		}
		\OC::$server->getActivityManager()->publish($event);
	}
}
