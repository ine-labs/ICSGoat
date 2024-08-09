<?php

/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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

namespace OCA\Market\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;

class PageController extends Controller {

	/**
	 * @NoCSRFRequired
	 *
	 * Required for marketplace login to generate a callbackurl with
	 * hash sign, or else login token will be attached before it resulting
	 * in a broken url.
	 */
	public function indexHash() {
		return $this->index();
	}

	/**
	 * @NoCSRFRequired
	 */
	public function index() {
		$templateResponse = new TemplateResponse($this->appName, 'index', []);
		$policy = new ContentSecurityPolicy();
		// live storage
		$policy->addAllowedImageDomain('https://marketplace-storage.owncloud.com');
		// staging - for internal testing
		$policy->addAllowedImageDomain('https://marketplace-storage.staging.owncloud.services');
		// local dev storage
		$policy->addAllowedImageDomain('http://minio:9000');
		$templateResponse->setContentSecurityPolicy($policy);

		return $templateResponse;
	}
}
