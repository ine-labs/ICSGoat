<?php
/**
 * @author Jesus Macias Portela <jmacias@solidgear.es>
 *
 * @copyright (C) 2017 ownCloud, Inc.
 * @license OCL
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
use OCA\windows_network_drive\lib\migration\Migration;

$installedVersion = \OC::$server->getConfig()->getAppValue('windows_network_drive', 'installed_version');

// Migration from OC 8.2.11 or earlier to 9.0.11 or later
if (\version_compare($installedVersion, '0.1.5', '<')) {
	$m = new Migration();
	$m->start();
}
