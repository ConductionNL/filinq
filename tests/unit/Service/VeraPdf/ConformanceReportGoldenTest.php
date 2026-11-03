<?php

/**
 * Golden cases of the conformance reports the service builds and stores.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use DateTime;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCA\Filinq\Tests\Unit\Support\RecordsCalls;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The calls checkFile() and checkConversion() make and the report they
 * return, compared with JSON recorded from the service whose report builder
 * took six parameters. The time and the checksum are part of the report.
 *
 * @covers \OCA\Filinq\Service\VeraPdf\ConformanceService
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
 */
class ConformanceReportGoldenTest extends TestCase {
	use RecordsCalls;

	private const GOLDEN = __DIR__.'/golden/conformance-report.json';

	/**
	 * The cases: the call, its arguments, the verdict and whether saving fails.
	 *
	 * @return array<string, array{0: string, 1: array<string, mixed>, 2: array<string, mixed>, 3?: bool}>
	 */
	public static function cases(): array {
		$failed = ['flavour' => '3b', 'compliant' => false, 'failedRuleCount' => '2', 'failedRules' => ['b' => 'r6.1', 'a' => 'r6.2'], 'fontsNotEmbedded' => ['x' => 'Arial'], 'validatorVersion' => 1.27, 'elapsedMs' => '41'];
		$passed = ['flavour' => '3b', 'compliant' => 1, 'failedRuleCount' => 0, 'failedRules' => [], 'fontsNotEmbedded' => [], 'validatorVersion' => '1.26'];
		return [
			'a stored file that fails'                => ['file', ['trigger' => 'manual'], $failed],
			'a stored file that passes'               => ['file', ['trigger' => 'validation'], $passed],
			'a conversion with a source'              => ['conversion', ['origin' => 'rendered', 'source' => 7], $failed],
			'a conversion without a source'           => ['conversion', ['origin' => 'imported', 'source' => null], $passed],
			'a conversion with a zero source'         => ['conversion', ['origin' => 'imported', 'source' => 0], $failed],
			'a conversion whose report is not stored' => ['conversion', ['origin' => 'rendered', 'source' => 7], $passed, true],
		];

	}//end cases()

	/**
	 * The calls and report match the recorded ones.
	 *
	 * @param string               $call      checkFile or checkConversion.
	 * @param array<string, mixed> $args      The arguments.
	 * @param array<string, mixed> $verdict   veraPDF's verdict.
	 * @param bool                 $saveFails Whether storing the report fails.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testReport(string $call, array $args, array $verdict, bool $saveFails=false): void {
		$save    = static fn (array $report): array => $report + ['id' => 'r-1'];
		if ($saveFails === true) {
			$save = static function (): never {
				throw new RuntimeException('register down');
			};
		}

		$service = new ConformanceService(
			veraPdf: $this->recorder(VeraPdfService::class, ['validateBytes' => $verdict]),
			guidance: $this->recorder(
				ConformanceGuidance::class,
				[
					'originOf' => 'imported',
					'for'      => static fn (array $verdict, string $origin): string => $origin.'-'.(int) $verdict['failedRuleCount'],
				]
			),
			reports: $this->recorder(ConformanceReportRepository::class, ['save' => $save]),
			time: $this->recorder(ITimeFactory::class, ['getDateTime' => new DateTime('2026-11-03T10:00:00+00:00')])
		);
		$file    = $this->recorder(File::class, ['getContent' => '%PDF-file', 'getId' => 42]);

		$this->traceCall(
			match ($call) {
				'file'       => fn () => $service->checkFile(file: $file, trigger: $args['trigger']),
				'conversion' => fn () => $service->checkConversion(bytes: '%PDF-conv', origin: $args['origin'], sourceFileId: $args['source']),
			}
		);

		$this->assertTrace(file: self::GOLDEN, name: (string) $this->dataName());

	}//end testReport()
}//end class
