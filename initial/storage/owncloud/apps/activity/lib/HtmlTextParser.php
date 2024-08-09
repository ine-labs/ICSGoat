<?php
/**
 * @author Thomas Müller <1005065+DeepDiver1975@users.noreply.github.com>
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

namespace OCA\Activity;

use OCP\IL10N;
use OCP\IURLGenerator;

class HtmlTextParser extends PlainTextParser {

	/**
	 * @var IURLGenerator
	 */
	private $generator;

	public function __construct(IL10N $l, IURLGenerator $generator) {
		parent::__construct($l);
		$this->generator = $generator;
	}

	/**
	 * Display the path for files
	 *
	 * @param string $message
	 * @return string
	 */
	protected function parseFileParameters($message) {
		return \preg_replace_callback('/<file\ link=\"(.*?)\"\ id=\"(.*?)\">(.*?)<\/file>/', function ($match) {
			$privateLink = $this->generator->getAbsoluteURL("/index.php/f/{$match[2]}");
			return "<a href=\"$privateLink\">{$match[3]}</a>";
		}, $message);
	}
}
