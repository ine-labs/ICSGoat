<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
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
 */

namespace OCA\ConfigReport\Http;

use OCP\AppFramework\Http\DownloadResponse;

/**
 * @package OCA\ConfigReport\Http
 */
class ReportResponse extends DownloadResponse {

	/**
	 * @var string $data
	 */
	private $data;

	/**
	 * ReportResponse constructor.
	 *
	 * @param string $fileName
	 * @param string $contentType
	 * @param string $data
	 */
	public function __construct($fileName, $contentType, $data) {
		$this->data = $data;

		$fileName = $fileName ? $fileName : 'config_report_' . \date('Ymd') . '.json';
		$contentType = $contentType ? $contentType : 'text/json';

		parent::__construct($fileName, $contentType);
	}

	/**
	 * @return string
	 */
	public function render() {
		return $this->data;
	}
}
