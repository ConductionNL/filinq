<?php

/**
 * Unit tests for DiwooMissingFields
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Publication
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use OCA\Filinq\Service\Publication\DiwooMissingFields;
use PHPUnit\Framework\TestCase;

/**
 * The missing list a record carries, and the seed records the worklist shows.
 *
 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-every-publication-record-says-which-diwoo-fields-it-misses-req-wmw-001
 */
class DiwooMissingFieldsTest extends TestCase {

	/**
	 * A record with every mandatory field.
	 *
	 * @var array<string, mixed>
	 */
	private const COMPLETE = [
		'wooCategory' => 'c_8c840238',
		'documentsoort' => 'advies',
		'publisher' => 'https://identifier.overheid.nl/tooi/id/gemeente/gm0000',
		'officieleTitel' => 'Advies',
		'creatiedatum' => '2025-03-12',
		'publicatiedatum' => '2026-11-20',
	];

	/**
	 * A complete record misses nothing; each emptied field is named, in column order.
	 *
	 * @return void
	 */
	public function testEveryEmptyMandatoryFieldIsNamedInOrder(): void {
		$missing = new DiwooMissingFields();

		$this->assertSame([], $missing->of(record: self::COMPLETE));
		$this->assertSame(
			['documentsoort', 'publisher', 'publicatiedatum'],
			$missing->of(record: ['documentsoort' => '  ', 'publisher' => ['not', 'a', 'value'], 'publicatiedatum' => null] + self::COMPLETE)
		);
		$this->assertSame(DiwooMissingFields::MANDATORY, $missing->of(record: []));

	}//end testEveryEmptyMandatoryFieldIsNamedInOrder()

	/**
	 * Annotating sets the calculated field and keeps everything else.
	 *
	 * @return void
	 */
	public function testAnnotateAddsTheCalculatedField(): void {
		$record = (new DiwooMissingFields())->annotate(record: ['uuid' => 'r1', 'wooCategory' => ''] + self::COMPLETE);

		$this->assertSame('r1', $record['uuid']);
		$this->assertSame(['wooCategory'], $record['diwooMissing']);

	}//end testAnnotateAddsTheCalculatedField()

	/**
	 * The worklist seed: one complete record, one without a Woo category, one
	 * without publisher and documentsoort, and the calculation is declared
	 * with the backend that computes it.
	 *
	 * @return void
	 */
	public function testTheSeedRecordsShowTheWorklist(): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$calculation = $register['components']['schemas']['publicationRecord']['x-openregister-calculations']['diwooMissing'];
		$this->assertSame('filinq.diwoo', $calculation['backend']);
		$this->assertSame(DiwooMissingFields::MANDATORY, $calculation['items']['enum']);

		$bySlug = [];
		foreach ($register['components']['objects'] as $object) {
			if (($object['@self']['schema'] ?? '') === 'publicationRecord') {
				$bySlug[$object['@self']['slug']] = (new DiwooMissingFields())->of(record: $object);
			}
		}

		$this->assertSame([], $bySlug['demostad-woo-2025-031-advies-compleet']);
		$this->assertSame(['wooCategory'], $bySlug['demostad-woo-2025-032-zonder-categorie']);
		$this->assertSame(['documentsoort', 'publisher'], $bySlug['demostad-woo-2025-033-zonder-publisher']);

	}//end testTheSeedRecordsShowTheWorklist()
}//end class
