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
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-completeness-validation-gates-overbrenging-req-ddtmm-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\MdtoMappingService;
use OCA\Filinq\Service\MdtoRestrictionProposer;
use OCA\Filinq\Service\MdtoRestrictionSources;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MdtoMappingService::class)]
#[UsesClass(MdtoRestrictionProposer::class)]
#[UsesClass(MdtoRestrictionSources::class)]
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
			'retention' => ['archiefnominatie' => $archiefnominatie, 'classificatie' => '1.1', 'bewaartermijn' => 'P20Y'],
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

		$this->assertSame(['identificatie', 'naam', 'waardering', 'classificatie', 'archiefvormer', 'bewaartermijn'], $verdict['missing']);

	}//end testEveryCoreFieldIsNamedWhenAbsent()

	public function testAnArchiefvormerOverrideStandsInForTheArchiefvormerButNotForTheOrganisationIdentifier(): void {
		$service    = new MdtoMappingService();
		$supplement = ['aggregatieniveau' => 'archiefstuk', 'archiefvormerOverride' => 'gemeente-x'];

		// OpenRegister's generator reads organisation_identifier for the identificatie bron, override or not.
		$this->assertSame(['organisation_identifier'], $service->validateCompleteness(self::record(), $supplement, '')['missing']);
		$this->assertTrue($service->validateCompleteness(self::record(), $supplement, 'org')['complete']);

	}//end testAnArchiefvormerOverrideStandsInForTheArchiefvormerButNotForTheOrganisationIdentifier()

	/**
	 * Each input OpenRegister's MdtoPreconditions refuses (5db8e48b9:
	 * assertGeneratorPreconditions, assertTransferPreconditions), applied to an
	 * otherwise complete record, with the finding the gate must report.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string, 2: array<int, array<string, mixed>>, 3: string}>
	 */
	public static function inputsOpenRegisterRefuses(): array {
		$file = self::file();

		return [
			'no uuid' => [['uuid' => ''], 'org', [], 'identificatie'],
			'no name' => [['title' => ''], 'org', [], 'naam'],
			'no appraisal' => [['retention' => ['classificatie' => '1.1', 'bewaartermijn' => 'P20Y']], 'org', [], 'waardering'],
			'an appraisal the generator does not map' => [['retention' => ['archiefnominatie' => 'misschien', 'classificatie' => '1.1', 'bewaartermijn' => 'P20Y']], 'org', [], 'waardering'],
			'no retention period (transfer)' => [['retention' => ['archiefnominatie' => 'vernietigen', 'classificatie' => '1.1']], 'org', [], 'bewaartermijn'],
			'a retention period that is no xsd:duration' => [['retention' => ['archiefnominatie' => 'vernietigen', 'classificatie' => '1.1', 'bewaartermijn' => '20 jaar']], 'org', [], 'bewaartermijn'],
			'no organisation identifier' => [[], '', [], 'archiefvormer'],
			'a file without a name' => [[], 'org', [array_merge($file, ['name' => ''])], 'file[0].name'],
			'a file without a format' => [[], 'org', [array_merge($file, ['format' => ''])], 'file[0].format'],
			'a file with a negative size' => [[], 'org', [array_merge($file, ['size' => -1])], 'file[0].size'],
			'a file whose checksum is no sha256' => [[], 'org', [array_merge($file, ['checksum' => 'abc'])], 'file[0].checksum'],
			'a file without a checksum date' => [[], 'org', [array_merge($file, ['checksumDate' => '2026-11-14'])], 'file[0].checksumDate'],
		];

	}//end inputsOpenRegisterRefuses()

	/**
	 * Gate implies generator success: whatever OpenRegister would refuse, the gate already calls incomplete.
	 *
	 * @param array<string, mixed>             $override The record fields that break it.
	 * @param string                           $org      The organisation identifier.
	 * @param array<int, array<string, mixed>> $files    The files.
	 * @param string                           $finding  The field the gate must name.
	 *
	 * @return void
	 */
	#[DataProvider('inputsOpenRegisterRefuses')]
	public function testTheGateRefusesWhateverOpenRegisterRefuses(array $override, string $org, array $files, string $finding): void {
		$verdict = (new MdtoMappingService())->validateCompleteness(
			array_merge(self::record(), $override),
			['aggregatieniveau' => 'archiefstuk'],
			$org,
			$files
		);

		$this->assertFalse($verdict['complete']);
		$this->assertContains($finding, $verdict['missing']);

	}//end testTheGateRefusesWhateverOpenRegisterRefuses()

	public function testACompleteRecordWithAWellFormedFilePasses(): void {
		$verdict = (new MdtoMappingService())->validateCompleteness(self::record(), ['aggregatieniveau' => 'archiefstuk'], 'org', [self::file()]);

		$this->assertSame(['complete' => true, 'missing' => []], $verdict);

	}//end testACompleteRecordWithAWellFormedFilePasses()

	public function testAFieldNamedInTheStoredProposalsListIsNotConfirmed(): void {
		$verdict = (new MdtoMappingService())->validateCompleteness(
			self::record(),
			['aggregatieniveau' => 'archiefstuk', 'proposals' => ['aggregatieniveau']],
			'org'
		);

		$this->assertSame(['aggregatieniveau'], $verdict['missing']);

	}//end testAFieldNamedInTheStoredProposalsListIsNotConfirmed()

	public function testAnObjectionProposesAnAvgRestrictionWithTheConsentGround(): void {
		$sources   = new MdtoRestrictionSources(
			consents: [
				['consentStatus' => 'objection_received', 'legalBasis' => 'AVG art. 6 lid 1 sub e'],
				['consentStatus' => 'consent_given', 'legalBasis' => 'AVG art. 6 lid 1 sub a'],
			]
		);
		$proposals = (new MdtoMappingService())->prefill('document', [], [], $sources, '2026-11-14');

		$this->assertSame(
			['value' => [['type' => 'avg-persoonsgegevens', 'grondslag' => 'AVG art. 6 lid 1 sub e', 'proposed' => true, 'source' => 'consent']], 'proposed' => true],
			$proposals['beperkingGebruik']
		);

	}//end testAnObjectionProposesAnAvgRestrictionWithTheConsentGround()

	public function testWithoutRestrictionStateNoRestrictionIsProposed(): void {
		$service = new MdtoMappingService();

		$this->assertArrayNotHasKey('beperkingGebruik', $service->prefill('document', []));
		$this->assertArrayNotHasKey('beperkingGebruik', $service->prefill('document', [], [], new MdtoRestrictionSources(consents: [['consentStatus' => 'consent_given']]), '2026-11-14'));

	}//end testWithoutRestrictionStateNoRestrictionIsProposed()

	/**
	 * A file whose facts OpenRegister's bestand generator accepts.
	 *
	 * @return array<string, mixed>
	 */
	private static function file(): array {
		return [
			'name' => 'besluit.pdf',
			'format' => 'application/pdf',
			'size' => 2048,
			'checksum' => str_repeat('ab', 32),
			'checksumDate' => '2026-11-14T10:00:00+01:00',
		];

	}//end file()
}//end class
