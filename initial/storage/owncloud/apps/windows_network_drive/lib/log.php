<?php
/**
 * ownCloud
 *
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */
namespace OCA\windows_network_drive\lib;

class Log {
	public static function writeLog($message, $level, $from='WND') {
		if (\OC::$server->getConfig()->getSystemValue('wnd.logging.enable', false) === true) {
			\OCP\Util::writeLog($from, $message, $level);
		}
	}
}
