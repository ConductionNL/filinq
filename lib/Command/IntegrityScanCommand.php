<?php

/**
 * Filinq integrity scan command
 *
 * `occ filinq:integrity:scan` runs the whole-collection integrity scan now,
 * stores the run, prints the counts and each finding, and exits 1 when it
 * found anything (whole-collection-integrity-scan REQ-DIS-02).
 *
 * @category Command
 * @package  OCA\Filinq\Command
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-2-nightly-job-occ-command-and-notification
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

declare(strict_types=1);

namespace OCA\Filinq\Command;

use OCA\Filinq\Service\IntegrityScanService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `occ filinq:integrity:scan`.
 *
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-2-nightly-job-occ-command-and-notification
 */
class IntegrityScanCommand extends Command {

	/**
	 * Constructor.
	 *
	 * @param IntegrityScanService $scan The scan.
	 */
	public function __construct(
		private readonly IntegrityScanService $scan,
	) {
		parent::__construct();
	}//end __construct()

	/**
	 * Name and describe the command.
	 *
	 * @return void
	 */
	protected function configure(): void {
		$this->setName(name: 'filinq:integrity:scan')
			->setDescription(description: 'Check every dossier, final document and anonymisation link for missing, changed and orphaned files');
	}//end configure()

	/**
	 * Run the scan and print what it found.
	 *
	 * @param InputInterface  $input  The input.
	 * @param OutputInterface $output The output.
	 *
	 * @return int 0 for a clean collection, 1 when the scan found anything.
	 *
	 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-2-nightly-job-occ-command-and-notification
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$run = $this->scan->run(trigger: 'command');

		$output->writeln(
			sprintf(
				'Checked %d records: %d missing, %d changed, %d orphaned.',
				$run['checked'],
				$run['missing'],
				$run['changed'],
				$run['orphaned']
			)
		);
		foreach ($run['findings'] as $finding) {
			$output->writeln(
				sprintf('  %s  %s %s  file %d  %s', $finding['kind'], $finding['schema'], $finding['record'], $finding['fileId'], $finding['name'])
			);
		}

		if ($run['findings'] === []) {
			return Command::SUCCESS;
		}

		return Command::FAILURE;
	}//end execute()
}//end class
