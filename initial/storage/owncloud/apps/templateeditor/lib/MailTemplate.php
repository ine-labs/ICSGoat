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

namespace OCA\TemplateEditor;

use OCP\Theme\ITheme;
use OCA\TemplateEditor\Http\MailTemplateResponse;

class MailTemplate extends \OC_Template {

	/** @var string */
	private $path;

	/**
	 * @param ITheme $theme
	 * @param string $path
	 */
	public function __construct(ITheme $theme, $path) {
		$this->theme = $theme;
		$this->path = $path;
	}
	
	/**
	 * @return \OCA\TemplateEditor\Http\MailTemplateResponse
	 */
	public function getResponse() {
		$template = $this->getTemplateDetails();
		return new MailTemplateResponse($template);
	}

	/**
	 * @return string
	 */
	protected function getTemplateDetails() {
		list($app, $filename) = explode('/templates/', $this->path, 2);
		if (\strpos($app, '/') !== false) {
			$app = \basename($app);
		}
		$name = substr($filename, 0, -4);
		$template = $this->findTemplate($this->theme, $app, $name);
		return $template;
	}

	/**
	 * @param string $content
	 * @return int|false
	 * @throws \Exception
	 */
	public function setContent($content) {
		$absolutePath = $this->getAbsoluteTemplatePath();

		// TODO: check what the minimum permissions are.
		if (!is_dir(dirname($absolutePath)) ) {
			if (!mkdir(dirname($absolutePath), 0777, true)){
				throw new \Exception('Could not create directory.', 500);
			}
		}
		if (is_file($absolutePath) ) {
			if (!copy($absolutePath, $absolutePath.'.bak')){
				throw new \Exception('Could not create template backup.', 500);
			}
		}

		return file_put_contents($absolutePath, $content);
	}

	/**
	 * @return bool
	 */
	public function reset() {
		$absolutePath = $this->getAbsoluteTemplatePath();

		if (is_file($absolutePath . '.bak')) {
			if (rename($absolutePath . '.bak', $absolutePath)) {
				return true;
			}
		} else if (unlink($absolutePath)) {
			return true;
		}

		return !file_exists($absolutePath);
	}

	/**
	 * @return string
	 */
	protected function getAbsoluteTemplatePath() {
		return \OC::$SERVERROOT . '/' . $this->theme->getDirectory() . '/' . $this->path;
	}
}
