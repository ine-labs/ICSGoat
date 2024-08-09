<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\windows_network_drive\lib\activity;

use OCP\Activity\IExtension;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\IURLGenerator;

class Extension implements IExtension {
	public const APP_NAME = 'windows_network_drive';

	public const TYPE_FILE_UPDATED = 'wnd_file_updated';
	public const TYPE_FILE_REMOVED = 'wnd_file_removed';
	public const TYPE_FILE_RENAMED = 'wnd_file_renamed';

	public const SUBJECT_FILE_UPDATED = 'file_updated';
	public const SUBJECT_FILE_REMOVED = 'file_removed';
	public const SUBJECT_FILE_RENAMED = 'file_renamed';

	/** @var IFactory */
	private $languageFactory;
	/** @var IURLGenerator */
	private $URLGenerator;

	public function __construct(IFactory $languageFactory, IURLGenerator $URLGenerator) {
		$this->languageFactory = $languageFactory;
		$this->URLGenerator = $URLGenerator;
	}

	/**
	 * @inheritDoc
	 */
	public function getNotificationTypes($languageCode) {
		$l = $this->languageFactory->get(self::APP_NAME, $languageCode);

		return [
			self::TYPE_FILE_UPDATED => (string) $l->t('A file or folder has been updated on your Network Drive storage'),
			self::TYPE_FILE_REMOVED => (string) $l->t('A file or folder has been removed on your Network Drive storage'),
			self::TYPE_FILE_RENAMED => (string) $l->t('A file or folder has been renamed on your Network Drive storage'),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaultTypes($method) {
		// all methods will have all the types
		return [
			self::TYPE_FILE_UPDATED,
			self::TYPE_FILE_REMOVED,
			self::TYPE_FILE_RENAMED,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getTypeIcon($type) {
		switch ($type) {
			case self::TYPE_FILE_UPDATED:
				return 'icon-change';
			case self::TYPE_FILE_REMOVED:
				return 'icon-delete no-permission';
			case self::TYPE_FILE_RENAMED:
				return 'icon-rename';
			default:
				return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		if ($app !== self::APP_NAME) {
			return false;
		}

		$l = $this->languageFactory->get(self::APP_NAME, $languageCode);

		switch ($text) {
			case self::SUBJECT_FILE_UPDATED:
				return (string) $l->t('%1$s has been updated', $params);
			case self::SUBJECT_FILE_REMOVED:
				return (string) $l->t('%1$s has been removed', $params);
			case self::SUBJECT_FILE_RENAMED:
				return (string) $l->t('%1$s has been renamed to %2$s', $params);
			default:
				return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getSpecialParameterList($app, $text) {
		if ($app === self::APP_NAME) {
			switch ($text) {
				case self::SUBJECT_FILE_UPDATED:
					return [0 => 'file'];
				case self::SUBJECT_FILE_REMOVED:  // cannot link the file in FILE_REMOVED
					return [0 => ''];
				case self::SUBJECT_FILE_RENAMED:
					return [0 => '', 1 => 'file'];
			}
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getGroupParameter($activity) {
		if ($activity['app'] === self::APP_NAME) {
			return 0;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getNavigation() {
		$l = $this->languageFactory->get(self::APP_NAME);

		return [
			'apps' => [],
			'top' => [
				self::APP_NAME => [
					'id' => self::APP_NAME,
					'name' => 'WND',  // no translation required
					'url' => $this->URLGenerator->linkToRoute('activity.Activities.showList', ['filter' => self::APP_NAME]),
				],
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function isFilterValid($filterValue) {
		return $filterValue === self::APP_NAME;
	}

	/**
	 * @inheritDoc
	 */
	public function filterNotificationTypes($types, $filter) {
		if ($filter === self::APP_NAME) {
			return [self::TYPE_FILE_UPDATED, self::TYPE_FILE_RENAMED, self::TYPE_FILE_REMOVED];
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getQueryForFilter($filter) {
		return false;
	}
}
