<?php
/**
 * @author Benedikt Kulmann <b@kulmann.biz>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\Activity\Formatter;

use OCP\Activity\IEvent;
use OCP\Util;

class UrlFormatter implements IFormatter {

	/**
	 * Format a list of url parameters into an html a-tag. The parameters have to be provided as json encoded data, i.e. a string.
	 * Allowed values are:
	 * <ul>
	 * <li>url - required value. Needs to be an absolute url, as relative urls can't be used in e.g. emails.</li>
	 * <li>name - optional (fallback is the url). Can't be html (will be sanitized).</li>
	 * <li>target - optional, but has to be out of ['_self', '_blank', '_parent', '_top'].</li>
	 * </ul>
	 *
	 * @param IEvent $event
	 * @param string $parameter The parameter to be formatted. In this case a list of parameters, separated by commas.
	 *
	 * @return string The formatted parameter
	 */
	public function format(IEvent $event, $parameter) {
		$params = \json_decode($parameter, true);
		if (!isset($params['url'])) {
			// we can't work without a url
			return '';
		}
		$url = $params['url'];
		if (\preg_match('#https?://#', $url) !== 1) {
			// we need an absolute url
			return '';
		}
		$name = $url;
		if (isset($params['name'])) {
			$name = Util::sanitizeHTML($params['name']);
		}
		$target = false;
		if (isset($params['target']) && \in_array($params['target'], ['_self', '_blank', '_parent', '_top'])) {
			$target = $params['target'];
		}
		$link = '<a href="' . $url . '"';
		if ($target !== false) {
			$link .= ' target="' . $target . '"';
		}
		$link .= '>' . $name . '</a>';
		return '<parameter>' . $link . '</parameter>';
	}
}
