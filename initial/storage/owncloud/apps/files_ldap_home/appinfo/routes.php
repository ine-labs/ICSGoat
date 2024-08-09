<?php
/**
 * ownCloud
 *
 * @author Lukas Reschke <lukas@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

/** @var $this OC\Route\Router */

$this->create('files_ldap_home_ajax_set', 'ajax/set.php')
	->actionInclude('files_ldap_home/ajax/set.php');
