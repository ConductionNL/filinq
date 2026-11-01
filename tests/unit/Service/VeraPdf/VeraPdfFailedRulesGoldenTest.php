<?php

/**
 * Golden test for the failed rules and fonts VeraPdfReportParser reads from a report.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Service\VeraPdf\VeraPdfReportParser;
use PHPUnit\Framework\TestCase;

/**
 * Every rule summary list through parse(), compared with JSON recorded from
 * the implementation before failedRules() was split.
 *
 * @covers \OCA\Filinq\Service\VeraPdf\VeraPdfReportParser
 */
class VeraPdfFailedRulesGoldenTest extends TestCase {

	/**
	 * The rule summaries, and whether the result itself says compliant.
	 *
	 * @return array<string, array{0: array<int, mixed>, 1: bool}>
	 */
	public static function scenarios(): array {
		$font = static fn (string $clause, array $contexts, string $status = 'failed'): array => [
			'specification' => 'ISO 19005-3:2012',
			'clause' => $clause,
			'testNumber' => 1,
			'status' => $status,
			'failedChecks' => count($contexts),
			'checks' => array_map(static fn (string $context): array => ['context' => $context], $contexts),
		];
		$page = 'root/document[0]/pages[0](4 0 obj PDPage)/contentStream[0]/operators[3]/font[0](ABCDEF+Arial)';
		$imported = 'root/document[0]/pages[0]/contentStream[0]/operators[2]/xObject[0]/contentStream[0]/operators[1]/font[1](Helvetica)';
		return [
			'nothing failed' => [[['clause' => '6.1.2', 'status' => 'passed']], true],
			'no summaries' => [[], false],
			'one font rule' => [[$font('6.2.11.4.1', [$page, $page])], false],
			'the PDF/A-1 font clause' => [[$font('6.3.4', [$page])], false],
			'a font in an imported page' => [[$font('6.2.11.4.1', [$page, $imported])], false],
			'font and other rules' => [[$font('6.2.11.4.1', [$imported]), ['specification' => 'ISO 19005-3:2012', 'clause' => '6.6.2.1', 'testNumber' => 2, 'status' => 'FAILED', 'failedChecks' => 3]], false],
			'only another rule' => [[['specification' => 'ISO 19005-2:2011', 'clause' => '6.1.3', 'testNumber' => 4, 'ruleStatus' => 'failed']], true],
			'the old status key' => [[['clause' => '6.3.4', 'ruleStatus' => 'Failed', 'checks' => [['context' => $page]]]], false],
			'status before ruleStatus' => [[['clause' => '6.3.4', 'status' => 'passed', 'ruleStatus' => 'failed']], false],
			'entries that are not arrays' => [['failed', 7, null, $font('6.3.4', [$page])], false],
			'a context without a font' => [[$font('6.3.4', ['root/document[0]/pages[0]', ''])], false],
			'no checks' => [[['clause' => '6.3.4', 'status' => 'failed']], false],
			'checks without context' => [[['clause' => '6.3.4', 'status' => 'failed', 'checks' => [[], ['context' => null]]]], false],
			'two fonts in one context' => [[$font('6.3.4', ['font[0](ABCDEF+Arial)/x/font[2](GHIJKL+Times)'])], false],
			'the same font in two rules' => [[$font('6.3.4', [$page]), $font('6.2.11.4.1', [$page, 'font[0](Times)'])], false],
			'a lower-case subset prefix' => [[$font('6.3.4', ['font[0](abcdef+Arial)'])], false],
			'a numeric clause' => [[['clause' => 634, 'status' => 'failed', 'testNumber' => '3', 'failedChecks' => '2']], false],
			'many rules' => [array_map(static fn (int $i): array => ['clause' => '6.1.' . $i, 'testNumber' => $i, 'status' => 'failed'], range(1, 30)), false],
		];
	}//end scenarios()

	/**
	 * The verdict matches the recorded one.
	 *
	 * @param array<int, mixed> $summaries The rule summaries.
	 * @param bool $compliant What the result itself says.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testVerdict(array $summaries, bool $compliant): void {
		$report = ['report' => ['jobs' => [[
			'validationResult' => [[
				'profileName' => 'PDF/A-3B validation profile',
				'compliant' => $compliant,
				'details' => ['failedRules' => 1, 'ruleSummaries' => $summaries],
			]],
		]]]];
		$trace = (new VeraPdfReportParser())->parse((string)json_encode($report));

		$path = __DIR__ . '/golden/verapdf-failed-rules.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testVerdict()
}//end class
