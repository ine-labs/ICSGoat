<?php
/**
 * ownCloud
 *
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

/**
 * SharepointWrapper
 *
 * PHP LIB for manage sharepoint document list items.
 *
 * @author Jesus Macias Portela
 * @version 0.1.0
 *
 * Add backwards compatability for none composer users:
 * Include this file and add `use \sharepoint\SharepointWrapper;` below in order
 * the PHP Sharepoint API as before.
 */

/*
 * PSR-0 Autoloader
 * see: http://zaemis.blogspot.fr/2012/05/writing-minimal-psr-0-autoloader.html
 */

spl_autoload_register(function ($classname) {
    $classname = ltrim($classname, '\\');
    preg_match('/^(.+)?([^\\\\]+)$/U', $classname, $match);
    $classname = 'src/'.str_replace('\\', '/', $match[1])
        . str_replace(array('\\', '_'), '/', $match[2])
        . '.php';
    include_once $classname;
});
