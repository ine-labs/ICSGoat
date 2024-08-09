<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib\listener;

class ListenerBuffer {
	private $buffer = false;

	/**
	 * Get the lines in the output.
	 * The output will be considered as a splitted stream where some newlines chars could appear
	 * The output might contain a piece of string or several lines.
	 * The function will store incomplete lines until a newline char appears in order to return
	 * some data.
	 * In order to return the last line, use $output = '' and $keepStoring false
	 * Some examples (to be executed in that order; changing the order will return different results):
	 * storeAndGetLines("abcde", true); -> []
	 * storeAndGetLines("fghi\njk", true); -> ["abcdefghi"]
	 * storeAndGetLines("", true); -> []
	 * storeAndGetLines("lmn", true); -> []
	 * storeAndGetLines("opq\nrs\ntu", true); -> ["jklmnopq", "rs"]
	 * storeAndGetLines("", true); -> []
	 * storeAndGetLines("", false); -> ["tu"]
	 * storeAndGetLines("fghi\njk", true); -> ["fghi"]
	 * storeAndGetLines("", false); -> ["jk"]
	 * storeAndGetLines("", false); -> [false]
	 *
	 * @param string $output the piece of the output stream to be checked
	 * @param bool $keepStoring whether to keep storing the output and wait for a newline char, or
	 * return whatever is in the buffer
	 * @return array and array with the lines fetched from the output, including the info stored in
	 * the buffer if it applies. An array with a false boolean will be return if you want the buffer
	 * but there isn't any additional line.
	 */
	public function storeAndGetLines($output, $keepStoring = true) {
		if ($output !== '') {
			$explodedOutput = \explode("\n", $output);
			$numberOfLines = \count($explodedOutput);

			// add the buffer to the first line
			if ($this->buffer !== false) {
				$explodedOutput[0] = $this->buffer . $explodedOutput[0];
			}

			if ($keepStoring) {
				// store the last (incomplete) line in the buffer
				$this->buffer = $explodedOutput[$numberOfLines - 1];

				// return the all the lines except the last one
				\array_splice($explodedOutput, -1);
				return $explodedOutput;
			} else {
				$this->buffer = false;
				return $explodedOutput;
			}
		} else {
			// check if the process is still running to decide to return the buffer or not
			if ($keepStoring) {
				// don't return anything and wait for more output
				return [];
			} else {
				// set the buffer to false and return the contents. This will help to determine when there
				// isn't anything more to read.
				$toBeReturned = $this->buffer;
				$this->buffer = false;
				return [$toBeReturned];
			}
		}
	}

	/**
	 * Reset the buffer
	 */
	public function resetBuffer() {
		$this->buffer = false;
	}
}
