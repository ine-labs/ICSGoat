<?php
/**
 * ownCloud - Template Editor
 *
 * @author Jörn Dreyer <jfd@owncloud.com>
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\TemplateEditor\Controller;

use OCA\TemplateEditor\TemplateEditor;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class AdminSettingsController extends ApiController {

	/**
	 * @var TemplateEditor
	 */
	private $templateEditor;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param TemplateEditor $templateEditor
	 */
	public function __construct($appName, IRequest $request, TemplateEditor $templateEditor) {
		parent::__construct($appName, $request);

		$this->templateEditor = $templateEditor;
	}

	/**
	 * @param string $themeName
	 * @param string $template
	 * @return \OCP\AppFramework\Http\Response
	 */
	public function renderTemplate($themeName, $template) {
		try {
			$template = $this->templateEditor->getMailTemplate($themeName, $template);
			return $template->getResponse();
		} catch (\Exception $ex) {
			return new JSONResponse(['message' => $ex->getMessage()], $ex->getCode());
		}
	}

	/**
	 * @param string $theme
	 * @param string $template
	 * @param string $content
	 * @return JSONResponse
	 */
	public function updateTemplate($theme, $template, $content) {
		try {
			$template = $this->templateEditor->getMailTemplate($theme, $template);
			$template->setContent($content);
			return new JSONResponse();
		} catch (\Exception $ex) {
			return new JSONResponse(['message' => $ex->getMessage()], $ex->getCode());
		}
	}

	/**
	 * @param string $theme
	 * @param string $template
	 * @return JSONResponse
	 */
	public function resetTemplate($theme, $template) {
		try {
			$template = $this->templateEditor->getMailTemplate($theme, $template);
			if ($template->reset()) {
				return new JSONResponse();
			} else {
				return new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
			}
		} catch (\Exception $ex) {
			return new JSONResponse(['message' => $ex->getMessage()], $ex->getCode());
		}
	}
}
