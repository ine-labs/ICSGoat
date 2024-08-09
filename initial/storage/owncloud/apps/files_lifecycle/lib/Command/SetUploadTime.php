<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle\Command;

use OCA\Files_Lifecycle\Dav\ArchivePlugin;
use OCA\Files_Lifecycle\Entity\Property;
use OCA\Files_Lifecycle\Entity\PropertyMapper;
use OCA\Files_Lifecycle\UploadInsert;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SetUploadTime
 *
 * @package OCA\Files_Lifecycle\Command
 */
class SetUploadTime extends Command {
	/**
	 * @var UploadInsert
	 */
	protected $query;

	/**
	 * @var PropertyMapper
	 */
	protected $mapper;

	/**
	 * SetUploadTime constructor.
	 *
	 * @param UploadInsert $query
	 * @param PropertyMapper $mapper
	 */
	public function __construct(UploadInsert $query, PropertyMapper $mapper) {
		parent::__construct();
		$this->query = $query;
		$this->mapper = $mapper;
	}

	/**
	 * Setup the command
	 *
	 * @return void
	 */
	public function configure() {
		$this
			->setName('lifecycle:set-upload-time')
			->setDescription('Set upload time for files which do not have one.')
			->addArgument(
				'date',
				InputArgument::REQUIRED,
				'Date in format Y-m-d. Example: 2018-07-23'
			)
			->addOption(
				'dry-run',
				'-d',
				InputOption::VALUE_NONE,
				'Dry run mode. Execute the command without changing anything.'
			);
	}

	/**
	 * Execute the command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	public function execute(InputInterface $input, OutputInterface $output) {
		$date = $input->getArgument('date');
		$dryRun = $input->getOption('dry-run');
		$dateTime = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
		if (!$dateTime) {
			$output->writeln('<error>Invalid date format. Check --help');
			return 1;
		}
		$output->writeln('Start searching for files without upload-time set.');
		$output->writeln('Search limit is 10.000.');

		if ($dryRun) {
			$output->writeln(
				'<info> This is a Dry-Run. Nothing will be changed</info>'
			);
			$output->writeln('');
		}

		$statement = $this->query->selectUploadTimeMissing();
		$total = $statement->rowCount();
		$p = new ProgressBar($output);
		$p->start($total);
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		while ($row = $statement->fetch()) {
			$entity = new Property();
			$entity->setFileid($row['fileid']);
			$entity->setPropertyname(ArchivePlugin::UPLOAD_TIME);
			$entity->setPropertyvalue($dateTime->format(\DateTime::ATOM));
			if ($dryRun !== true) {
				$this->mapper->insert($entity);
			}
			$output->writeln(' Changing ' . $row['path']);
			$p->advance();
		}
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$statement->closeCursor();
		$p->finish();
		$output->writeln('');
		$output->writeln('');
		$output->writeln($p->getProgress() . ' Files changed');
		$output->writeln('Finished successfully.');
		return 0;
	}
}
