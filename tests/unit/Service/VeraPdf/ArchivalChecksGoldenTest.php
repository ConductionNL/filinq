<?php

/**
 * Golden cases of the archival findings a profile and a conformance report give.
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

use OCA\Filinq\Service\DocumentValidationService;
use OCA\Filinq\Service\Validation\ArchivalChecks;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCA\Filinq\Tests\Unit\Support\RecordsCalls;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The calls findings() makes and the findings it returns, compared with JSON
 * recorded from the checks whose finding builder took five parameters.
 *
 * @covers \OCA\Filinq\Service\Validation\ArchivalChecks
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.4
 */
class ArchivalChecksGoldenTest extends TestCase {
	use RecordsCalls;

	private const GOLDEN = __DIR__.'/golden/archival-checks.json';

	/**
	 * The cases: the two severities, the mime type, the validator state and the report.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4?: array<string, mixed>}>
	 */
	public static function cases(): array {
		$rules = [['ruleId' => '6.1.2'], ['ruleId' => '6.2.11'], ['ruleId' => '6.3.5'], ['ruleId' => '6.9']];
		$both  = ['compliant' => false, 'flavour' => '3b', 'failedRuleCount' => '4', 'failedRules' => $rules, 'fontsNotEmbedded' => ['Arial', 'Calibri'], 'guidance' => 'reexport'];
		$clean = ['compliant' => true, 'flavour' => '3b', 'failedRuleCount' => 0, 'failedRules' => [], 'fontsNotEmbedded' => [], 'guidance' => ''];
		$font  = ['compliant' => true, 'flavour' => 2, 'failedRuleCount' => 0, 'failedRules' => [], 'fontsNotEmbedded' => ['Arial'], 'guidance' => ''];
		$one   = ['compliant' => false, 'flavour' => '3b', 'failedRuleCount' => 1, 'failedRules' => [['ruleId' => '6.1.2']], 'fontsNotEmbedded' => [], 'guidance' => ''];
		return [
			'both checks off'                     => ['off', 'off', 'application/pdf', 'available', $both],
			'not a PDF'                           => ['warning', 'warning', 'image/png', 'available', $both],
			'no validator'                        => ['warning', 'off', 'application/pdf', 'missing'],
			'validator switched off'              => ['off', 'blocking', 'application/pdf', 'unavailable'],
			'validator fails'                     => ['blocking', 'blocking', 'application/pdf', 'throws'],
			'both findings'                       => ['blocking', 'warning', 'application/pdf', 'available', $both],
			'conformance only'                    => ['warning', 'off', 'application/pdf', 'available', $both],
			'fonts only'                          => ['off', 'blocking', 'application/pdf', 'available', $both],
			'a clean report'                      => ['blocking', 'blocking', 'application/pdf', 'available', $clean],
			'only fonts fail, without guidance'   => ['blocking', 'warning', 'application/pdf', 'available', $font],
			'one rule fails, without guidance'    => ['warning', 'warning', 'application/pdf', 'available', $one],
		];

	}//end cases()

	/**
	 * The calls and findings match the recorded ones.
	 *
	 * @param string               $conformance The conformance severity.
	 * @param string               $fonts       The font severity.
	 * @param string               $mime        The mime type.
	 * @param string               $validator   missing, unavailable, throws or available.
	 * @param array<string, mixed> $report      The stored report.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testFindings(string $conformance, string $fonts, string $mime, string $validator, array $report=[]): void {
		$service = null;
		if ($validator !== 'missing') {
			$check   = static fn (): array => $report;
			if ($validator === 'throws') {
				$check = static function (): never {
					throw new RuntimeException('veraPDF died');
				};
			}

			$service = $this->recorder(ConformanceService::class, ['isAvailable' => ($validator !== 'unavailable'), 'checkFile' => $check]);
		}

		$profile = [
			'severities' => [
				DocumentValidationService::CHECK_PDFA_CONFORMANCE => $conformance,
				DocumentValidationService::CHECK_PDFA_FONTS       => $fonts,
			],
		];
		$checks  = new ArchivalChecks(conformance: $service);
		$this->traceCall(fn () => $checks->findings(profile: $profile, mime: $mime, file: $this->recorder(File::class, ['getId' => 5])));

		$this->assertTrace(file: self::GOLDEN, name: (string) $this->dataName());

	}//end testFindings()
}//end class
