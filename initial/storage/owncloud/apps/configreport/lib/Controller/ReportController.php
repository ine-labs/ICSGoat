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

namespace OCA\ConfigReport\Controller;

use OCA\ConfigReport\Http\ReportResponse;
use OCA\ConfigReport\ReportDataCollector;
use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;

/**
 * Class ReportController is used to handle report generation on the admin
 * settings page
 *
 * @package OCA\ConfigReport\Controller
 */
class ReportController extends Controller {

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var ReportDataCollector
	 */
	private $reportDataCollector;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param ReportDataCollector $reportDataCollector
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		IConfig $config,
		ReportDataCollector $reportDataCollector
	) {
		$this->config = $config;
		$this->reportDataCollector = $reportDataCollector;
		parent::__construct($AppName, $request);
	}

	/**
	 * AJAX handler for getting the config report
	 *
	 * @NoCSRFRequired
	 * @return ReportResponse with the report
	 */
	public function getReport() {
		return new ReportResponse('', '', $this->reportDataCollector->getReportJson());
	}
}
