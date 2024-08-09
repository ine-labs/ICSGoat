<?php
/**
 * ownCloud
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 * @copyright (C) 2020 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
 
$licenseManager = \OC::$server->getLicenseManager();
$licenseManager->checkLicenseFor('systemtags_management');
