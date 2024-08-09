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

namespace OCA\Files_Lifecycle\Activity;

use OCA\Files_Lifecycle\Application;
use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

/**
 * Class Extension
 *
 * @package OCA\Files_Lifecycle\Activity
 */
class Extension implements IExtension {
	public const FILE_ARCHIVED_SUBJECT = 'lifecycle_file_archived_subject';
	public const FILE_RESTORED_SUBJECT = 'lifecycle_file_restored_subject';
	public const FILE_EXPIRED_SUBJECT = 'lifecycle_file_expired_subject';

	/**
	 * @var IFactory
	 */
	protected $languageFactory;

	/**
	 * @var IManager
	 */
	protected $activityManager;

	/**
	 * @var IURLGenerator
	 */
	protected $URLGenerator;

	/**
	 * @param IFactory $languageFactory
	 * @param IManager $activityManager
	 * @param IURLGenerator $URLGenerator
	 */
	public function __construct(
		IFactory $languageFactory,
		IManager $activityManager,
		IURLGenerator $URLGenerator
	) {
		$this->languageFactory = $languageFactory;
		$this->activityManager = $activityManager;
		$this->URLGenerator = $URLGenerator;
	}

	/**
	 * @param string|null $languageCode
	 *
	 * @return IL10N
	 */
	protected function getL10N($languageCode = null) {
		return $this->languageFactory->get(Application::APPID, $languageCode);
	}

	/**
	 * The extension can return an array of additional notification types.
	 * If no additional types are to be added false is to be returned
	 *
	 * @param string $languageCode
	 *
	 * @return array
	 */
	public function getNotificationTypes($languageCode) {
		$l = $this->getL10N($languageCode);

		return [
			Application::APPID => [
				'desc' => (string) $l->t('<strong>Lifecycle Events</strong> for files <em>(always listed in stream)</em>'),
				'methods' => [self::METHOD_MAIL], // self::METHOD_STREAM is forced true by the default value
			],
		];
	}

	/**
	 * For a given method additional types to be displayed in the settings can
	 * be returned. In case no additional types are to be added false is to be
	 * returned.
	 *
	 * @param string $method
	 *
	 * @return array|false
	 */
	public function getDefaultTypes($method) {
		return $method === self::METHOD_STREAM ? [Application::APPID] : false;
	}

	/**
	 * A string naming the css class for the icon to be used can be returned.
	 * If no icon is known for the given type false is to be returned.
	 *
	 * @param string $type
	 *
	 * @return string|false
	 */
	public function getTypeIcon($type) {
		switch ($type) {
			case Application::APPID:
				return 'icon-archive';
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
	 * @param boolean $stripPath
	 * @param boolean $highlightParams
	 * @param string $languageCode
	 *
	 * @return string|false
	 */
	public function translate(
		$app,
		$text,
		$params,
		$stripPath,
		$highlightParams,
		$languageCode
	) {
		if ($app !== Application::APPID) {
			return false;
		}

		$l = $this->getL10N($languageCode);

		if ($this->activityManager->isFormattingFilteredObject()) {
			$translation = $this->translateShort($text, $l, $params);
			if ($translation !== false) {
				return $translation;
			}
		}

		return $this->translateLong($text, $l, $params);
	}

	/**
	 * @param string $text
	 * @param IL10N $l
	 * @param array $params
	 *
	 * @return bool|string
	 */
	protected function translateShort($text, IL10N $l, array $params) {
		switch ($text) {
			case self::FILE_ARCHIVED_SUBJECT:
				return (string) $l->t(
					'%1$s was moved to the archive',
					$params
				);
			case self::FILE_RESTORED_SUBJECT:
				return (string) $l->t(
					'%1$s was restored',
					$params
				);
			case self::FILE_EXPIRED_SUBJECT:
				return (string) $l->t(
					'%1$s was expired',
					$params
				);
		}

		return false;
	}

	/**
	 * Convert the placeholder text to the real message including translation
	 *
	 * @param string $text
	 * @param IL10N $l
	 * @param array $params
	 *
	 * @return bool|string
	 */
	protected function translateLong($text, IL10N $l, array $params) {
		switch ($text) {
			case self::FILE_ARCHIVED_SUBJECT:
				return (string) $l->t(
					'The file %1$s was moved to the archive',
					$params
				);
			case self::FILE_RESTORED_SUBJECT:
				return (string) $l->t(
					'The file %1$s was restored from the archive',
					$params
				);
			case self::FILE_EXPIRED_SUBJECT:
				return (string) $l->t(
					'The file %1$s was expired from the archive',
					$params
				);
		}

		return false;
	}

	/**
	 * The extension can define the type of parameters for translation
	 *
	 * @param string $app
	 * @param string $text
	 *
	 * @return array|false
	 */
	public function getSpecialParameterList($app, $text) {
		if ($app === Application::APPID) {
			return [
				0 => 'path',
			];
		}

		return false;
	}

	/**
	 * The extension can define the parameter grouping by returning the index
	 * as integer. In case no grouping is required false is to be returned.
	 *
	 * @param array $activity
	 *
	 * @return integer|false
	 */
	public function getGroupParameter($activity) {
		return false;
	}

	/**
	 * The extension can define additional navigation entries. The array
	 * returned has to contain two keys 'top'
	 * and 'apps' which hold arrays with the relevant entries.
	 * If no further entries are to be added false is no be returned.
	 *
	 * @return array|false
	 */
	public function getNavigation() {
		$l = $this->getL10N();
		return [
			'apps' => [],
			'top' => [
				Application::APPID => [
					'id' => Application::APPID,
					'name' => (string) $l->t('File Lifecycle Events'),
					'url' => $this->URLGenerator->linkToRoute(
						'activity.Activities.showList',
						['filter' => Application::APPID]
					),
				],
			],
		];
	}

	/**
	 * The extension can check if a custom filter (given by a query string
	 * like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 *
	 * @return boolean
	 */
	public function isFilterValid($filterValue) {
		return $filterValue === Application::APPID;
	}

	/**
	 * The extension can filter the types based on the filter if required.
	 * In case no filter is to be applied false is to be returned unchanged.
	 *
	 * @param array $types
	 * @param string $filter
	 *
	 * @return array|false
	 */
	public function filterNotificationTypes($types, $filter) {
		if ($filter === Application::APPID) {
			return [Application::APPID];
		}
		if (\in_array($filter, ['all', 'by', 'self', 'filter'])) {
			$types[] = Application::APPID;
			return $types;
		}
		return false;
	}

	/**
	 * For a given filter the extension can specify the sql query conditions
	 * including parameters for that query. In case the extension does not know
	 * the filter false is to be returned. The query condition and the
	 * parameters are to be returned as array with two elements. E.g.
	 * return array('`app` = ? and `message` like ?', array('mail', 'ownCloud%'));
	 *
	 * @param string $filter
	 *
	 * @return array|false
	 */
	public function getQueryForFilter($filter) {
		return false;
	}
}
