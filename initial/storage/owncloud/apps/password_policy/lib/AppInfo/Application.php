<?php
/**
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\PasswordPolicy\AppInfo;

use OCP\AppFramework\App;
use OCP\IConfig;
use OCP\Notification\Events\RegisterNotifierEvent;
use OCA\PasswordPolicy\Notifier;
use OCA\PasswordPolicy\Capabilities;
use OCA\PasswordPolicy\ConfigProvider;
use OCP\IContainer;

class Application extends App {
	public function __construct(array $urlParams = []) {
		parent::__construct('password_policy', $urlParams);
		$container = $this->getContainer();
		$container->registerService('Capabilities', function (IContainer $c) {
			return new Capabilities(new ConfigProvider($c->query(IConfig::class)));
		});
		$container->registerCapability('Capabilities');
	}

	/**
	 * Registers the notifier
	 */
	public function registerNotifier() {
		$container = $this->getContainer();
		$dispatcher = $container->getServer()->getEventDispatcher();
		$dispatcher->addListener(RegisterNotifierEvent::NAME, function (RegisterNotifierEvent $event) use ($container) {
			$l10n = $container->getServer()->getL10N('password_policy');
			$event->registerNotifier($container->query(Notifier::class), 'password_policy', $l10n->t('Password Policy'));
		});
	}
}
