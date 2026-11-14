<?php

/**
 * Unit tests for MdtoMappingService: the prefill matrix and the
 * completeness gate in front of overbrenging.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-completeness-validation-gates-overbrenging-req-ddtmm-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\MdtoMappingService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MdtoMappingService::class)]
class MdtoMappingServiceTest extends TestCase {

	/**
	 * A record that carries every core attribute.
	 *
	 * @param string $archiefnominatie The appraisal.
	 *
	 * @return array<string, mixed>
	 */
	private static function record(string $archiefnominatie='vernietigen'): array {
		return [
			'uuid' => '6f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f',
			'title' => 'Omgevingsvergunning Dorpsstraat 12',
			'retention' => ['archiefnominatie' => $archiefnominatie, 'classificatie' => '1.1'],
		];

	}//end record()

	public function testADocumentIsProposedAsAnArchiefstukCoveringItsOwnDates(): void {
		$proposals = (new MdtoMappingService())->prefill('document', ['created' => '2026-03-02T09:00:00+01:00', 'closedAt' => '2026-05-20']);

		$this->assertSame(
			[
				'aggregatieniveau' => ['value' => 'archiefstuk', 'proposed' => true],
				'dekkingInTijdBegin' => ['value' => '2026-03-02', 'proposed' => true],
				'dekkingInTijdEind' => ['value' => '2026-05-20', 'proposed' => true],
			],
			$proposals
		);

	}//end testADocumentIsProposedAsAnArchiefstukCoveringItsOwnDates()

	public function testADossierCoversItsEarliestToItsLatestMember(): void {
		$proposals = (new MdtoMappingService())->prefill(
			'dossier',
			['created' => '2030-01-01'],
			[['created' => '2025-06-01', 'closedAt' => '2025-07-01'], ['created' => '2024-11-15'], ['closedAt' => 'not a date']]
		);

		$this->assertSame('dossier', $proposals['aggregatieniveau']['value']);
		$this->assertSame('2024-11-15', $proposals['dekkingInTijdBegin']['value']);
		$this->assertSame('2025-07-01', $proposals['dekkingInTijdEind']['value']);

	}//end testADossierCoversItsEarliestToItsLatestMember()

	public function testWhatCannotBeDerivedIsLeftOut(): void {
		$this->assertSame([], (new MdtoMappingService())->prefill('note', ['created' => '']));

	}//end testWhatCannotBeDerivedIsLeftOut()

	public function testACompleteRecordPasses(): void {
		$verdict = (new MdtoMappingService())->validateCompleteness(self::record(), ['aggregatieniveau' => 'archiefstuk'], '00000001002220647000');

		$this->assertSame(['complete' => true, 'missing' => []], $verdict);

	}//end testACompleteRecordPasses()

	public function testAPermanentRecordWithoutAggregationOrCoverageIsBlocked(): void {
		$verdict = (new MdtoMappingService())->validateCompleteness(self::record('blijvend_bewaren'), [], '00000001002220647000');

		$this->assertSame(['complete' => false, 'missing' => ['aggregatieniveau', 'dekkingInTijd']], $verdict);

	}//end testAPermanentRecordWithoutAggregationOrCoverageIsBlocked()

	public function testAnUnconfirmedProposalDoesNotCount(): void {
		$service = new MdtoMappingService();
		$proposed = ['aggregatieniveau' => ['value' => 'archiefstuk', 'proposed' => true], 'dekkingInTijdBegin' => ['value' => '2026-03-02', 'proposed' => true]];
		$accepted = ['aggregatieniveau' => ['value' => 'archiefstuk', 'proposed' => false], 'dekkingInTijdBegin' => '2026-03-02'];

		$this->assertSame(['aggregatieniveau', 'dekkingInTijd'], $service->validateCompleteness(self::record('bewaren'), $proposed, 'org')['missing']);
		$this->assertTrue($service->validateCompleteness(self::record('bewaren'), $accepted, 'org')['complete']);

	}//end testAnUnconfirmedProposalDoesNotCount()

	public function testEveryCoreFieldIsNamedWhenAbsent(): void {
		$verdict = (new MdtoMappingService())->validateCompleteness(['title' => '  '], ['aggregatieniveau' => 'dossier'], '');

		$this->assertSame(['identificatie', 'naam', 'waardering', 'classificatie', 'archiefvormer'], $verdict['missing']);

	}//end testEveryCoreFieldIsNamedWhenAbsent()

	public function testAnArchiefvormerOverrideStandsInForTheOrganisation(): void {
		$verdict = (new MdtoMappingService())->validateCompleteness(self::record(), ['aggregatieniveau' => 'archiefstuk', 'archiefvormerOverride' => 'gemeente-x'], '');

		$this->assertTrue($verdict['complete']);

	}//end testAnArchiefvormerOverrideStandsInForTheOrganisation()
}//end class
