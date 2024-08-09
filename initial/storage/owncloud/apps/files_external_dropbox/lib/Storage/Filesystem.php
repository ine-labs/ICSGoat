<?php
/**
 * @author Hemant Mann <hemant.mann121@gmail.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH.
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace OCA\Files_external_dropbox\Storage;

use League\Flysystem\Util;
use League\Flysystem\Filesystem as VendorFilesystem;

class Filesystem extends VendorFilesystem {
	const IS_CASE_INSENSITIVE_STORAGE = 'isCaseInsensitiveStorage';

	/**
	 * @inheritdoc
	 */
	public function listContents($directory = '', $recursive = false) {
		$directory = Util::normalizePath($directory);
		$contents = $this->getAdapter()->listContents($directory, $recursive);

		$contentListFormatter = new ContentListingFormatter($directory, $recursive);
		// Make the formatter aware of the storage type i.e. whether it is case insensitive or not
		$contentListFormatter->setIsCaseInsensitiveStorage($this->getConfig()->get(static::IS_CASE_INSENSITIVE_STORAGE, false));

		return $contentListFormatter->formatListing($contents);
	}
}
