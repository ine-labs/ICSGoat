<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2015 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Retention;

use OC\BackgroundJob\TimedJob;
use OCP\AppFramework\App;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Class MetaBackgroundJob
 *
 * This background job just takes care that each retention tag has a background
 * job scheduled, in case something went wrong at some point.
 *
 * @package OCA\Workflow\Retention
 */
class MetaBackgroundJob extends TimedJob {

	/** @var IJobList */
	protected $jobList;

	/** @var IUserManager */
	protected $userManager;

	/** @var IConfig */
	protected $config;

	/** @var Manager */
	protected $retentionManager;

	/** @var ILogger */
	protected $logger;

	/**
	 * BackgroundJob constructor.
	 *
	 * @param IJobList|null $jobList
	 * @param ILogger|null $logger
	 * @param IConfig|null $config
	 * @param IUserManager|null $userManager
	 * @param Manager|null $retentionManager
	 */
	public function __construct(
		IJobList $jobList = null,
		ILogger $logger = null,
		IConfig $config = null,
		IUserManager $userManager = null,
		Manager $retentionManager = null
	) {
		$this->jobList = $jobList;
		$this->logger = $logger;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->retentionManager = $retentionManager;

		if ($jobList === null ||
			$logger === null ||
			$config === null ||
			$userManager === null ||
			$retentionManager === null) {
			$this->fixDependencies();
		}

		$retentionEngine = $this->config->getSystemValue('workflow.retention_engine', 'tagbased');
		if ($retentionEngine === 'tagbased') {
			$this->setInterval(3600);
		} elseif ($retentionEngine === 'userbased') {
			// Since looping over all users might be more time intensive, we only do that once a day
			$this->setInterval(3600 * 24);
		}
	}

	/**
	 * Fill the members with the classes we need
	 */
	protected function fixDependencies() {
		$app = new App('workflow');
		$container = $app->getContainer();

		$this->jobList = $container->getServer()->getJobList();
		$this->logger = $container->getServer()->getLogger();
		$this->config = $container->getServer()->getConfig();
		$this->userManager = $container->getServer()->getUserManager();
		$this->retentionManager = $container->query('OCA\Workflow\Retention\Manager');
	}

	/**
	 * @param mixed $argument
	 */
	protected function run($argument) {
		$retentionEngine = $this->config->getSystemValue('workflow.retention_engine', 'tagbased');
		if ($retentionEngine === 'tagbased') {
			$this->jobList->remove('\OCA\Workflow\Retention\UserBasedRetention');
			$this->createTagJobs();
		} elseif ($retentionEngine === 'userbased') {
			$this->jobList->remove('\OCA\Workflow\Retention\TagBasedRetention');
			$this->createUserJobs();
		} else {
			$this->logger->critical('Unknown retention engine "{engine}". Known engines are: "tagbased"', [
				'engine' => $retentionEngine,
				'app' => 'workflow',
			]);
		}
	}

	protected function createTagJobs() {
		$retentionPeriods = $this->retentionManager->getAll();

		foreach ($retentionPeriods as $retentionPeriod) {
			if (!$this->jobList->has('\OCA\Workflow\Retention\TagBasedRetention', ['tagId' => $retentionPeriod['tagId']])) {
				$this->jobList->add('\OCA\Workflow\Retention\TagBasedRetention', ['tagId' => $retentionPeriod['tagId']]);

				$this->logger->warning('Retention job for tag "{tag}" was missing.', [
					'tag' => $retentionPeriod['tagId'],
					'app' => 'workflow',
				]);
			}
		}
	}

	protected function createUserJobs() {
		$this->userManager->callForAllUsers($this->createClosureForUserIteration($this->jobList));
	}

	/**
	 * @param IJobList $jobList
	 * @return \Closure
	 */
	protected function createClosureForUserIteration(IJobList $jobList) {
		return static function ($user) use ($jobList) {
			if (!($user instanceof IUser)) {
				// In case we get null
				return;
			}
			$jobList->add('\OCA\Workflow\Retention\UserBasedRetention', ['user' => $user->getUID()]);
		};
	}
}
