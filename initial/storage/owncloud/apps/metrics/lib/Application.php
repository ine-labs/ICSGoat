<?php
/**
 * @author Benedikt Kulmann <bkulmann@owncloud.com>
 * @copyright (C) 2019 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Metrics;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCA\Metrics\Middleware\SharedSecretMiddleware;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;

class Application extends App {
	public const APPID = 'metrics';

	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(Application::APPID, $urlParams);
		$this->setupSecurityMiddleware();
	}

	/**
	 * Adds the navigation entry if the logged in user has access to the metrics ui.
	 *
	 * @return bool
	 */
	public function addNavigationEntry(): bool {
		$server = $this->getContainer()->getServer();

		$config = $server->getConfig();

		if ($config->getAppValue('metrics', 'disable_dashboard', 'no') === 'yes') {
			return false;
		}

		$user = $server->getUserSession()->getUser();
		if ($user === null) {
			return false;
		}
		if ($server->getGroupManager()->isAdmin($user->getUID()) !== true) {
			return false;
		}
		$server->getNavigationManager()->add(function () {
			return $this->buildNavigationEntry();
		});
		return true;
	}

	/**
	 * Provides data for the nav entry.
	 *
	 * @return array
	 */
	public function buildNavigationEntry(): array {
		$server = $this->getContainer()->getServer();
		$urlGenerator = $server->getURLGenerator();
		return [
			'id' => Application::APPID,
			'order' => 10,
			'href' => $urlGenerator->linkToRoute(Application::APPID . '.Page.get'),
			'icon' => $urlGenerator->imagePath(Application::APPID, Application::APPID . '.svg'),
			'name' => $server->getL10N(Application::APPID)->t('Metrics'),
		];
	}

	/**
	 * Register security middleware.
	 *
	 * @return void
	 */
	private function setupSecurityMiddleware() {
		if ($this->getContainer() !== null) {
			$this->getContainer()->registerService(
				'SharedSecretMiddleware',
				function (IAppContainer $c) {
					return new SharedSecretMiddleware(
						$c->query(IRequest::class),
						$c->query(ControllerMethodReflector::class),
						$c->query(IConfig::class),
						$c->query(ILogger::class)
					);
				}
			);
			$this->getContainer()->registerMiddleware('SharedSecretMiddleware');
		}
	}
}
