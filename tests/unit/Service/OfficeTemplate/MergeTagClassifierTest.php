<?php

/**
 * Merge tags are sorted into known fields, text fragments and unknown tags
 * against the bound schema; without a schema nothing is checked or blocked.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use OCA\Filinq\Service\OfficeTemplate\MergeTagClassifier;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\MergeTagClassifier
 */
class MergeTagClassifierTest extends TestCase {

	/**
	 * The properties of a `dossier` schema.
	 *
	 * @return array<string, mixed>
	 */
	private static function properties(): array {
		return [
			'zaaknummer' => ['type' => 'string'],
			'aanvrager' => ['type' => 'object', 'properties' => ['naam' => ['type' => 'string'], 'adres' => ['type' => 'string']]],
			'regels' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['omschrijving' => ['type' => 'string']]]],
			'besluit' => ['type' => 'object', '$ref' => 'besluit'],
		];
	}//end properties()

	/**
	 * Each tag gets its status, and the unknown ones are listed.
	 *
	 * @return void
	 */
	public function testEachTagIsClassifiedAgainstTheSchema(): void {
		$report = (new MergeTagClassifier())->report(
			tags: ['zaaknummer', 'aanvrager.naam', 'aanvraagr.naam', 'aanvrager.telefoon', 'regels.omschrijving', 'besluit.datum', 'fragment:ondertekening'],
			properties: self::properties(),
			severity: 'warning'
		);

		$this->assertSame(
			[
				'validated' => true,
				'severity' => 'warning',
				'tags' => [
					['tag' => 'zaaknummer', 'status' => 'known'],
					['tag' => 'aanvrager.naam', 'status' => 'known'],
					['tag' => 'aanvraagr.naam', 'status' => 'unknown'],
					['tag' => 'aanvrager.telefoon', 'status' => 'unknown'],
					['tag' => 'regels.omschrijving', 'status' => 'known'],
					['tag' => 'besluit.datum', 'status' => 'known'],
					['tag' => 'fragment:ondertekening', 'status' => 'fragment'],
				],
				'unknown' => ['aanvraagr.naam', 'aanvrager.telefoon'],
			],
			$report
		);
	}//end testEachTagIsClassifiedAgainstTheSchema()

	/**
	 * Without a schema nothing is checked, the report says so, and nothing blocks.
	 *
	 * @return void
	 */
	public function testWithoutASchemaNothingIsCheckedOrBlocked(): void {
		$classifier = new MergeTagClassifier();
		$report = $classifier->report(tags: ['wat.dan.ook', 'fragment:x'], properties: null, severity: 'blocking');

		$this->assertFalse($report['validated']);
		$this->assertSame([['tag' => 'wat.dan.ook', 'status' => 'unvalidated'], ['tag' => 'fragment:x', 'status' => 'fragment']], $report['tags']);
		$this->assertSame([], $report['unknown']);
		$this->assertSame('Not validated against a schema: the template is not bound to one.', $report['notice']);
		$this->assertFalse($classifier->blocks(report: $report));
	}//end testWithoutASchemaNothingIsCheckedOrBlocked()

	/**
	 * Unknown tags block only under blocking severity; any other value is a warning.
	 *
	 * @return void
	 */
	public function testOnlyBlockingSeverityBlocksUnknownTags(): void {
		$classifier = new MergeTagClassifier();
		$blocking = $classifier->report(tags: ['onbekend'], properties: self::properties(), severity: ' Blocking ');
		$this->assertSame('blocking', $blocking['severity']);
		$this->assertTrue($classifier->blocks(report: $blocking));

		$this->assertFalse($classifier->blocks(report: $classifier->report(tags: ['zaaknummer'], properties: self::properties(), severity: 'blocking')));
		foreach (['warning', '', 'strict'] as $severity) {
			$report = $classifier->report(tags: ['onbekend'], properties: self::properties(), severity: $severity);
			$this->assertSame('warning', $report['severity']);
			$this->assertFalse($classifier->blocks(report: $report));
		}
	}//end testOnlyBlockingSeverityBlocksUnknownTags()
}//end class
