<?php

/**
 * A soffice process runner for tests that behaves like soffice on disk.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Service\Conversion\SofficeProcessRunner;

/**
 * A runner that writes what soffice would: `<outdir>/<input stem>.pdf`.
 */
class DiskLikeSofficeRunner extends SofficeProcessRunner {

	/**
	 * The argv of each run.
	 *
	 * @var array<int, array<int, string>>
	 */
	public array $runs = [];

	/**
	 * Constructor.
	 *
	 * @param string $pdf The bytes to emit.
	 */
	public function __construct(private readonly string $pdf = '%PDF-1.7 emitted') {
	}//end __construct()

	/**
	 * Emit the PDF under soffice's name for it.
	 *
	 * @param array<int, string> $argv        The argv.
	 * @param int                $timeout     The timeout.
	 * @param string             $tmpDir      The directory.
	 * @param string             $backendName The backend.
	 *
	 * @return int 0
	 */
	public function run(array $argv, int $timeout, string $tmpDir, string $backendName): int {
		$this->runs[] = $argv;
		$input = (string) end($argv);
		$outdir = $argv[(int) array_search('--outdir', $argv, true) + 1];
		file_put_contents($outdir . '/' . pathinfo($input, PATHINFO_FILENAME) . '.pdf', $this->pdf);
		return 0;
	}//end run()
}//end class
